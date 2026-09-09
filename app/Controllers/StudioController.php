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
use App\Services\Lang;
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
    /**
     * How many cards one Studio page lists.
     *
     * The pile is not split up in the game - this is only so a browser is not
     * asked to hold 120 edit forms at once.
     */
    private const CARDS_PER_PAGE = 30;

    /** Which page a card is listed on, so editing it comes back to the same place */
    private static function pageOf(int $projectId, int $missionId): int
    {
        foreach (array_values(MissionMatcher::forProject($projectId)) as $i => $m) {
            if ((int) $m['id'] === $missionId) {
                return intdiv($i, self::CARDS_PER_PAGE) + 1;
            }
        }

        return 1;
    }

    public function index(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $pid     = (int) $project['id'];

        $missions = MissionMatcher::forProject($pid);

        /*
         * The cards are one pile. They do not belong to a space on the board:
         * a star space just means "take the top card", so they are listed in
         * the order they print, numbered from 1.
         *
         * Still shown a block at a time, because a game can hold 120 cards and
         * each one carries an edit form.
         */
        $pages = array_chunk($missions, self::CARDS_PER_PAGE, true);
        $page  = $request->int('page', 1);
        $page  = max(1, min($page, max(1, count($pages))));
        $shown = $pages[$page - 1] ?? [];

        $players = Database::all(
            'SELECT * FROM project_players WHERE project_id = ? ORDER BY sort_order ASC, id ASC',
            [$pid]
        );
        if (!$players) {
            $players = PrintBundle::defaultPlayers((int) $project['players_max'], Lang::of($project));
        }

        $this->view('studio/index', [
            'pageTitle'    => $project['title'],
            'project'      => $project,
            'items'        => Project::libraryItems($project),
            'missions'     => $shown,
            'pageCount'    => count($pages),
            'currentPage'  => $page,
            'perPage'      => self::CARDS_PER_PAGE,
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

        /*
         * A card the buyer added themselves belongs to the box they typed it
         * in: editing it here would be overwritten the next time they save
         * that box, so they are sent to the one place that holds the words.
         */
        if (($mission['source'] ?? '') === 'extra') {
            Flash::error('That is one of your own questions - edit it in the box below the cards.');
            Response::redirect('/studio/' . (int) $project['id'] . '#own-questions');
            return;
        }

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
        Response::redirect('/studio/' . (int) $project['id']
            . '?page=' . self::pageOf((int) $project['id'], (int) $mission['id']) . '#missions');
    }

    /** FR-26: swap the card for another variation of the same template */
    public function rerollMission(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $mission = $this->ownedMission((int) ($params['mid'] ?? 0), (int) $project['id']);

        $new = ($mission['source'] ?? '') === 'extra'
            ? null      // one of theirs - the library has nothing to swap in for it
            : MissionMatcher::reroll((int) $mission['id'], Auth::plan());

        // There is nothing to swap for on a game built from the buyer's own list
        if (($mission['source'] ?? '') === 'extra') {
            $why = 'That is one of your own questions - change it in the box below the cards.';
        } elseif (Project::usesOwnQuestions($project)) {
            $why = 'This game uses your own questions, so there is nothing to swap in. Edit the card instead.';
        } else {
            $why = 'No base template is available to swap this card.';
        }

        if ($request->isAjax()) {
            if (!$new) {
                $this->json(['ok' => false, 'message' => $why], 422);
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
            Flash::error($why);
        } else {
            Project::touch((int) $project['id']);
            Flash::success('Swapped in a different question.');
        }

        Response::redirect('/studio/' . (int) $project['id']
            . '?page=' . self::pageOf((int) $project['id'], (int) $mission['id']) . '#missions');
    }

    /** Regenerates every mission card */
    public function regenerate(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));

        $total = Difficulty::missionCount((string) $project['difficulty']);

        /*
         * A game built on the buyer's own questions is dealt again from that
         * same list. Regenerating it out of the library would throw away work
         * they typed themselves, which is not what the button says it does.
         */
        if (Project::usesOwnQuestions($project)) {
            $cards = MissionMatcher::fromOwnQuestions(
                MissionMatcher::parseOwnQuestions((string) ($project['own_questions'] ?? '')),
                (int) $project['cells'],
                $total
            );
        } else {
            $cards = MissionMatcher::generate(
                Project::subjects($project),
                (string) $project['difficulty'],
                Auth::plan(),
                (int) $project['cells'],
                $total,
                null,
                Lang::of($project),
                MissionMatcher::questionsAlreadyUsed($this->userId(), (int) $project['id'])
            );
        }

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

    /**
     * The questions the buyer adds themselves, from the two boxes in the
     * Studio: questions in one, answers in the other, line for line.
     *
     * These are added to the pile rather than dealt in place of anything, so
     * saving here leaves every other card exactly as it was.
     */
    public function saveQuestions(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $pid     = (int) $project['id'];

        $questions = mb_substr(trim($request->str('extra_questions')), 0, 20000);
        $answers   = mb_substr(trim($request->str('extra_answers')), 0, 20000);

        Project::touch($pid, [
            'extra_questions' => $questions,
            'extra_answers'   => $answers,
        ]);

        $added = MissionMatcher::applyExtras(array_merge($project, [
            'extra_questions' => $questions,
            'extra_answers'   => $answers,
        ]));

        if ($added === 0) {
            Flash::success('Your own questions have been cleared from the pile.');
        } else {
            Flash::success($added === 1
                ? 'Your question was added to the mission pile.'
                : 'Your ' . $added . ' questions were added to the mission pile.');
        }

        // Say so rather than silently keeping the first two hundred of a long paste
        $typed = count(array_filter(
            preg_split('/\r\n|\r|\n/', $questions) ?: [],
            fn($line) => trim($line) !== ''
        ));

        if ($typed > $added) {
            Flash::warning('Only the first ' . $added . ' were used - a game takes at most '
                . MissionMatcher::MAX_EXTRA_QUESTIONS . ' questions of your own.');
        }

        Response::redirect('/studio/' . $pid . '#own-questions');
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
