<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Models\Project;
use App\Services\Art;
use App\Services\Difficulty;
use App\Services\MissionMatcher;
use App\Services\PrintBundle;
use App\Services\PromptGenerator;

/**
 * The Studio - fine-tuning a game's content (step 3, "Customize in Studio",
 * of SRS section 2.2).
 *
 *   FR-25: users write their own questions and pick a sticker for each card.
 *   FR-26: swap a card you are not happy with before the final export.
 */
class StudioController extends Controller
{
    public function index(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $pid     = (int) $project['id'];

        $missions = MissionMatcher::forProject($pid);

        // Group the cards by map space so they are easy to scan
        $byCell = [];
        foreach ($missions as $m) {
            $byCell[(int) $m['cell_no']][] = $m;
        }
        ksort($byCell);

        // Show one space at a time - a game can hold up to 120 cards
        $cells       = array_keys($byCell);
        $currentCell = $request->int('cell', $cells[0] ?? 1);
        if (!in_array($currentCell, $cells, true)) {
            $currentCell = $cells[0] ?? 1;
        }

        $players = Database::all(
            'SELECT * FROM project_players WHERE project_id = ? ORDER BY sort_order ASC, id ASC',
            [$pid]
        );
        if (!$players) {
            $players = PrintBundle::defaultPlayers((int) $project['players_max']);
        }

        $this->view('studio/index', [
            'pageTitle'    => $project['title'],
            'project'      => $project,
            'items'        => Project::libraryItems($project),
            'missions'     => $byCell[$currentCell] ?? [],
            'cells'        => $cells,
            'currentCell'  => $currentCell,
            'missionCount' => count($missions),
            'expected'     => Difficulty::missionCount((string) $project['difficulty']),
            'progress'     => Project::progress($project),
            'players'      => $players,
            'stickers'     => Art::STICKERS,
            'tokenColors'  => Art::TOKEN_COLORS,
            'readiness'    => PrintBundle::readiness($project),
            'subjects'     => Project::subjects($project),
        ]);
    }

    /** FR-25: the user edits the question and picks a sticker */
    public function saveMission(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $mission = $this->ownedMission((int) ($params['mid'] ?? 0), (int) $project['id']);

        $question = trim($request->str('question'));
        if ($question === '') {
            Flash::error('The question cannot be empty.');
            $this->back('/studio/' . (int) $project['id']);
            return;
        }

        $sticker = $request->str('sticker', 'star');
        if (!isset(Art::STICKERS[$sticker])) {
            $sticker = 'star';
        }

        Database::update('project_missions', [
            'question' => mb_substr($question, 0, 500),
            'answer'   => mb_substr(trim($request->str('answer')), 0, 500),
            'sticker'  => $sticker,
            'source'   => 'custom',      // marks it as written by the user
        ], ['id' => (int) $mission['id']]);

        Project::touch((int) $project['id']);

        Flash::success('Mission card saved.');
        Response::redirect('/studio/' . (int) $project['id'] . '?cell=' . (int) $mission['cell_no']);
    }

    /** FR-26: swap the card for another variation of the same template */
    public function rerollMission(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $mission = $this->ownedMission((int) ($params['mid'] ?? 0), (int) $project['id']);

        $new = MissionMatcher::reroll((int) $mission['id'], Auth::plan());

        if ($request->isAjax()) {
            if (!$new) {
                $this->json(['ok' => false, 'message' => 'No base template is available to swap this card.'], 422);
                return;
            }
            Project::touch((int) $project['id']);
            $this->json([
                'ok'       => true,
                'question' => $new['question'],
                'answer'   => $new['answer'],
                'sticker'  => $new['sticker'],
            ]);
            return;
        }

        if (!$new) {
            Flash::error('This card cannot be swapped - you wrote it yourself, or its template is gone.');
        } else {
            Project::touch((int) $project['id']);
            Flash::success('Swapped in a different question.');
        }

        Response::redirect('/studio/' . (int) $project['id'] . '?cell=' . (int) $mission['cell_no']);
    }

    /** Regenerates every mission card */
    public function regenerate(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));

        $cards = MissionMatcher::generate(
            Project::subjects($project),
            (string) $project['difficulty'],
            Auth::plan(),
            (int) $project['cells'],
            Difficulty::missionCount((string) $project['difficulty'])
        );

        if (!$cards) {
            Flash::error('No cards could be generated. Please review the question subjects.');
            $this->back('/studio/' . (int) $project['id']);
            return;
        }

        MissionMatcher::saveForProject((int) $project['id'], $cards);
        Project::touch((int) $project['id'], ['question_count' => count($cards)]);

        Flash::success('Regenerated ' . count($cards) . ' mission cards.');
        Response::redirect('/studio/' . (int) $project['id']);
    }

    /** Saves the story, the rules and the hero name */
    public function saveContent(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $pid     = (int) $project['id'];

        $update = [
            'story'       => mb_substr(trim($request->str('story')), 0, 4000),
            'how_to_play' => mb_substr(trim($request->str('how_to_play')), 0, 4000),
            'hero_name'   => mb_substr(trim($request->str('hero_name')), 0, 120),
        ];

        $title = trim($request->str('title'));
        if ($title !== '') {
            $update['title'] = mb_substr($title, 0, 160);
        }

        // The "use the suggested text" button
        if ($request->bool('use_suggestion')) {
            $seed = PromptGenerator::storySeed(array_merge($project, ['hero_name' => $update['hero_name']]));
            $update['story']       = $seed['story'];
            $update['how_to_play'] = $seed['how_to_play'];
        }

        Project::touch($pid, $update);

        Flash::success('Game content saved.');
        Response::redirect('/studio/' . $pid . '#content');
    }

    /** The player list and their token colours */
    public function savePlayers(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $pid     = (int) $project['id'];

        $names  = $request->arr('player_name');
        $colors = $request->arr('player_color');

        Database::transaction(function () use ($pid, $names, $colors) {
            Database::delete('project_players', ['project_id' => $pid]);

            $order = 0;
            foreach ($names as $i => $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }
                $color = (string) ($colors[$i] ?? 'red');
                if (!isset(Art::TOKEN_COLORS[$color])) {
                    $color = 'red';
                }
                Database::insert('project_players', [
                    'project_id' => $pid,
                    'name'       => mb_substr($name, 0, 60),
                    'color'      => $color,
                    'sort_order' => $order++,
                ]);
                if ($order >= Project::MAX_PLAYERS) {
                    break;
                }
            }
        });

        Project::touch($pid);
        Flash::success('Player list saved.');
        Response::redirect('/studio/' . $pid . '#players');
    }

    // -----------------------------------------------------------------

    /** Loads a mission card, making sure it belongs to the user's project */
    private function ownedMission(int $missionId, int $projectId): array
    {
        $row = Database::first(
            'SELECT * FROM project_missions WHERE id = ? AND project_id = ? LIMIT 1',
            [$missionId, $projectId]
        );
        if (!$row) {
            Response::abort(404, 'That mission card no longer exists.');
        }
        return $row;
    }
}
