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
use App\Services\Library;
use App\Services\MissionMatcher;
use App\Services\PromptGenerator;
use App\Services\Tiers;

/** Ready-made game templates - FR-17, FR-18 */
class TemplateController extends Controller
{
    public function index(Request $request): void
    {
        $theme      = $request->str('theme');
        $difficulty = $request->str('difficulty');
        $search     = $request->str('q');

        $sql    = 'SELECT * FROM game_templates WHERE is_active = 1';
        $params = [];

        if ($theme !== '' && isset(Art::THEMES[$theme])) {
            $sql .= ' AND theme = ?';
            $params[] = $theme;
        }
        if ($difficulty !== '' && Difficulty::exists($difficulty)) {
            $sql .= ' AND difficulty = ?';
            $params[] = $difficulty;
        }
        if ($search !== '') {
            $sql .= ' AND name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY uses_count DESC, id ASC';

        $this->view('templates/index', [
            'pageTitle'    => 'Game templates',
            'templates'    => Database::all($sql, $params),
            'total'        => Database::count('SELECT COUNT(*) FROM game_templates WHERE is_active = 1'),
            'themes'       => Art::THEMES,
            'difficulties' => Difficulty::all(),
            'theme'        => $theme,
            'difficulty'   => $difficulty,
            'search'       => $search,
            'planKey'      => Auth::plan(),
        ]);
    }

    /** Creates a new project from a ready-made template */
    public function use(Request $request, array $params): void
    {
        $id  = (int) ($params['id'] ?? 0);
        $tpl = Database::first('SELECT * FROM game_templates WHERE id = ? AND is_active = 1 LIMIT 1', [$id]);

        if (!$tpl) {
            Response::abort(404, 'That template no longer exists.');
        }

        $plan = Auth::plan();

        if (!Tiers::atLeast($plan, (string) $tpl['tier'])) {
            Flash::warning('This template belongs to the ' . Tiers::name((string) $tpl['tier']) . ' plan. Upgrade to use it.');
            Response::redirect('/billing');
            return;
        }

        if (!Project::canCreate($this->userId(), $plan)) {
            Flash::warning('You have reached the project limit on your current plan.');
            Response::redirect('/billing');
            return;
        }

        $difficulty = (string) $tpl['difficulty'];
        if (!Tiers::allowsDifficulty($plan, $difficulty)) {
            Flash::warning('The ' . Difficulty::name($difficulty) . ' level requires the Pro plan or higher.');
            Response::redirect('/billing');
            return;
        }

        $cfg = Difficulty::get($difficulty);

        // Pick a map with the same theme and the right space count
        $tiers = Tiers::unlockedTiers($plan);
        $map = Database::first(
            'SELECT * FROM library_items WHERE kind = ? AND cells = ? AND theme = ? AND tier IN ('
            . implode(',', array_fill(0, count($tiers), '?')) . ') ORDER BY id ASC LIMIT 1',
            array_merge(['map', (int) $cfg['cells'], (string) $tpl['theme']], $tiers)
        );

        $seed = PromptGenerator::storySeed([
            'title' => $tpl['name'],
            'theme' => $tpl['theme'],
            'cells' => (int) $cfg['cells'],
        ]);

        $projectId = Project::create($this->userId(), [
            'title'       => (string) $tpl['name'],
            'theme'       => (string) $tpl['theme'],
            'difficulty'  => $difficulty,
            'subjects'    => (string) ($tpl['subjects'] ?? 'math,nature'),
            'players_min' => (int) $tpl['players_min'],
            'players_max' => (int) $tpl['players_max'],
            'age_min'     => (int) $tpl['age_min'],
            'age_max'     => (int) $tpl['age_max'],
            'map_item_id' => $map['id'] ?? null,
            'story'       => $seed['story'],
            'how_to_play' => $seed['how_to_play'],
        ]);

        // Generate the mission cards so the project is usable right away
        $cards = MissionMatcher::generate(
            array_filter(explode(',', (string) $tpl['subjects'])),
            $difficulty,
            $plan,
            (int) $cfg['cells'],
            (int) $cfg['mission_cards']
        );
        if ($cards) {
            MissionMatcher::saveForProject($projectId, $cards);
        }

        Project::touch($projectId, ['wizard_step' => 3, 'status' => 'in_progress']);
        Database::run('UPDATE game_templates SET uses_count = uses_count + 1 WHERE id = ?', [$id]);

        Flash::success('Created a project from "' . $tpl['name'] . '". Next step: upload a background.');
        Response::redirect('/create/' . $projectId . '/step/3');
    }
}
