<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Project;
use App\Services\Art;
use App\Services\Difficulty;
use App\Services\Library;
use App\Services\MissionMatcher;
use App\Services\PromptGenerator;
use App\Services\Tiers;
use App\Services\Uploader;

/**
 * The new-game flow - an ASSEMBLER model, exactly as SRS section 2.3 describes.
 *
 * There is NO real-time AI chat step (SRS section 7: out of scope).
 * Instead there are five steps:
 *
 *   1. Basics        - title, theme, difficulty, question subjects (FR-23)
 *   2. Library picks - map frame and character set, filtered by plan
 *   3. Map background - copy the prompt (FR-30), generate it elsewhere, upload it (FR-31)
 *   4. Mission cards - matched automatically from the library (FR-24, FR-35)
 *   5. Finish        - review, then move on to the Studio
 */
class CreateController extends Controller
{
    private const LAST_STEP = 5;

    private const STEP_LABELS = [
        1 => 'Basics',
        2 => 'Library picks',
        3 => 'Map background',
        4 => 'Mission cards',
        5 => 'Finish',
    ];

    // -----------------------------------------------------------------
    //  Starting a new project
    // -----------------------------------------------------------------

    public function start(Request $request): void
    {
        $plan = Auth::plan();

        if (!Project::canCreate($this->userId(), $plan)) {
            Flash::warning('The ' . Tiers::name($plan) . ' plan is limited to '
                . Tiers::projectLimit($plan) . ' projects. Upgrade, or delete an older one.');
            Response::redirect('/billing');
            return;
        }

        $this->view('create/start', [
            'pageTitle'    => 'New game',
            'themes'       => Art::THEMES,
            'difficulties' => Difficulty::all(),
            'allowed'      => Tiers::difficulties($plan),
            'subjects'     => MissionMatcher::SUBJECTS,
            'plan'         => Tiers::get($plan),
        ]);
    }

    /** Creates the project record and moves on to step 2 */
    public function store(Request $request): void
    {
        $plan = Auth::plan();

        if (!Project::canCreate($this->userId(), $plan)) {
            Flash::warning('You have reached the project limit on your current plan.');
            Response::redirect('/billing');
            return;
        }

        $v = new Validator($request->body);
        $v->required('title', 'a game title')->max('title', 160, 'the game title')
          ->in('theme', array_keys(Art::THEMES), 'theme')
          ->in('difficulty', array_keys(Difficulty::all()), 'difficulty')
          ->between('players_min', 1, 8, 'the minimum player count')
          ->between('players_max', 1, 8, 'the maximum player count');

        $difficulty = $request->str('difficulty', Difficulty::STANDARD);

        // Entitlement check: the Starter plan cannot use Advanced (SRS section 9)
        if (!Tiers::allowsDifficulty($plan, $difficulty)) {
            $v->rule('difficulty', false,
                'The ' . Difficulty::name($difficulty) . ' level requires the Pro plan or higher.');
        }

        $subjects = array_values(array_intersect(
            $request->arr('subjects'),
            MissionMatcher::subjectKeys()
        ));
        if (!$subjects) {
            $v->rule('subjects', false, 'Please choose at least one question subject.');
        }

        if ($v->fails()) {
            Flash::error($v->firstError() ?? 'Please check the details you entered.');
            $this->backWithErrors($v->errors(), $request->body, '/create');
            return;
        }

        $min = $request->int('players_min', 2);
        $max = $request->int('players_max', 4);
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        $projectId = Project::create($this->userId(), [
            'title'       => $request->str('title'),
            'theme'       => $request->str('theme', 'forest'),
            'difficulty'  => $difficulty,
            'subjects'    => implode(',', $subjects),
            'players_min' => $min,
            'players_max' => $max,
        ]);

        Project::touch($projectId, ['wizard_step' => 2]);
        Response::redirect('/create/' . $projectId . '/step/2');
    }

    // -----------------------------------------------------------------
    //  The remaining steps
    // -----------------------------------------------------------------

    public function step(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $step    = $this->clampStep((int) ($params['step'] ?? 1));
        $plan    = Auth::plan();

        $data = [
            'pageTitle' => self::STEP_LABELS[$step] . ' · ' . $project['title'],
            'project'   => $project,
            'step'      => $step,
            'lastStep'  => self::LAST_STEP,
            'labels'    => self::STEP_LABELS,
            'plan'      => Tiers::get($plan),
            'planKey'   => $plan,
        ];

        switch ($step) {
            case 1:
                // Coming back to edit the basics of an existing project
                $data['themes']       = Art::THEMES;
                $data['difficulties'] = Difficulty::all();
                $data['allowed']      = Tiers::difficulties($plan);
                $data['subjects']     = MissionMatcher::SUBJECTS;
                $data['chosen']       = Project::subjects($project);
                $data['missionCount'] = MissionMatcher::countForProject((int) $project['id']);
                break;

            case 2:
                $data['maps'] = Library::forPlan(Library::KIND_MAP, $plan, [
                    'cells' => (int) $project['cells'],
                ]);
                $data['lockedMaps'] = $this->lockedCount(Library::KIND_MAP, $plan, (int) $project['cells']);
                $data['characters'] = Library::forPlan(Library::KIND_CHARACTER, $plan);
                $data['moves']      = Library::forPlan(Library::KIND_MOVE, $plan);
                $data['rewards']    = Library::forPlan(Library::KIND_REWARD, $plan);
                break;

            case 3:
                $style = $request->str('style', 'storybook');
                if (!isset(PromptGenerator::STYLES[$style])) {
                    $style = 'storybook';
                }
                $data['style']        = $style;
                $data['styles']       = PromptGenerator::STYLES;
                $data['prompt']       = PromptGenerator::background($project, $style);
                $data['instructions'] = PromptGenerator::instructions();
                $data['background']   = !empty($project['background_id'])
                    ? Database::first('SELECT * FROM user_assets WHERE id = ?', [(int) $project['background_id']])
                    : null;
                break;

            case 4:
                $data['missionCount'] = MissionMatcher::countForProject((int) $project['id']);
                $data['expected']     = Difficulty::missionCount((string) $project['difficulty']);
                $data['perCell']      = Difficulty::MISSIONS_PER_CELL;
                $data['subjects']     = MissionMatcher::SUBJECTS;
                $data['chosen']       = Project::subjects($project);
                $data['templates']    = MissionMatcher::matchTemplates(
                    Project::subjects($project),
                    (string) $project['difficulty'],
                    $plan
                );
                $data['variants']     = MissionMatcher::estimateVariants($data['templates']);
                $data['sample']       = array_slice(MissionMatcher::forProject((int) $project['id']), 0, 6);
                break;

            case 5:
                $data['missionCount'] = MissionMatcher::countForProject((int) $project['id']);
                $data['expected']     = Difficulty::missionCount((string) $project['difficulty']);
                $data['items']        = Project::libraryItems($project);
                $data['progress']     = Project::progress($project);
                break;
        }

        $this->view('create/step' . $step, $data);
    }

    public function saveStep(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $step    = $this->clampStep((int) ($params['step'] ?? 1));
        $id      = (int) $project['id'];
        $plan    = Auth::plan();

        switch ($step) {
            case 1:
                $this->saveBasics($request, $project, $plan);
                break;

            case 2:
                $this->saveLibraryChoices($request, $project, $plan);
                break;

            case 4:
                // Allow changing the subjects, then regenerating
                $subjects = array_values(array_intersect($request->arr('subjects'), MissionMatcher::subjectKeys()));
                if ($subjects) {
                    Project::touch($id, ['subjects' => implode(',', $subjects)]);
                }
                break;

            case 5:
                Project::touch($id, [
                    'status'      => 'in_progress',
                    'wizard_step' => self::LAST_STEP,
                ]);
                Flash::success('Your game is ready. You can fine-tune it in the Studio now.');
                Response::redirect('/studio/' . $id);
                return;
        }

        $next = min(self::LAST_STEP, $step + 1);
        Project::touch($id, ['wizard_step' => max((int) $project['wizard_step'], $next)]);
        Response::redirect('/create/' . $id . '/step/' . $next);
    }

    /**
     * Step 1 when revisited: update the basics.
     *
     * Changing the difficulty changes both the map size and the mission card
     * count, so the old map frame has to be cleared and the generated cards
     * deleted - otherwise an 18-space map would ship with 24-space card sets.
     */
    private function saveBasics(Request $request, array $project, string $plan): void
    {
        $id     = (int) $project['id'];
        $update = [];

        $title = trim($request->str('title'));
        if ($title !== '') {
            $update['title'] = mb_substr($title, 0, 160);
        }

        $theme = $request->str('theme');
        if (isset(Art::THEMES[$theme])) {
            $update['theme'] = $theme;
        }

        $subjects = array_values(array_intersect($request->arr('subjects'), MissionMatcher::subjectKeys()));
        if ($subjects) {
            $update['subjects'] = implode(',', $subjects);
        }

        $min = $request->int('players_min', (int) $project['players_min']);
        $max = $request->int('players_max', (int) $project['players_max']);
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }
        $update['players_min'] = max(1, min(8, $min));
        $update['players_max'] = max(1, min(8, $max));

        // Difficulty change
        $difficulty = $request->str('difficulty', (string) $project['difficulty']);
        if (Difficulty::exists($difficulty) && $difficulty !== $project['difficulty']) {
            if (!Tiers::allowsDifficulty($plan, $difficulty)) {
                Flash::warning('The ' . Difficulty::name($difficulty) . ' level requires the Pro plan or higher, so the level was left unchanged.');
            } else {
                $cfg = Difficulty::get($difficulty);
                $update['difficulty']     = $difficulty;
                $update['cells']          = (int) $cfg['cells'];
                $update['question_count'] = (int) $cfg['mission_cards'];
                $update['age_min']        = (int) $cfg['age_min'];
                $update['age_max']        = (int) $cfg['age_max'];

                // The space count changed, so the old frame and cards no longer fit
                $update['map_item_id'] = null;
                Database::delete('project_missions', ['project_id' => $id]);

                Flash::info('Difficulty changed, so the map frame and mission cards need choosing again.');
            }
        }

        Project::touch($id, $update);
    }

    /** Step 2: save the library picks, checking plan entitlements */
    private function saveLibraryChoices(Request $request, array $project, string $plan): void
    {
        $update = [];

        $fields = [
            'map_item_id'       => Library::KIND_MAP,
            'character_item_id' => Library::KIND_CHARACTER,
            'move_item_id'      => Library::KIND_MOVE,
            'reward_item_id'    => Library::KIND_REWARD,
        ];

        foreach ($fields as $column => $kind) {
            $itemId = $request->int($column, 0);

            if ($itemId <= 0) {
                $update[$column] = null;
                continue;
            }

            $item = Library::find($itemId);

            // Unknown item, wrong kind, or above the plan's tier -> ignore it
            if (!$item || $item['kind'] !== $kind || !Library::unlocked($item, $plan)) {
                Flash::warning('One of your selections was not valid and was ignored.');
                continue;
            }

            // The map must have the space count the difficulty requires
            if ($kind === Library::KIND_MAP && (int) $item['cells'] !== (int) $project['cells']) {
                Flash::warning('The map frame must have exactly ' . (int) $project['cells'] . ' spaces.');
                continue;
            }

            $update[$column] = $itemId;
        }

        // Follow the chosen map's theme so the colours stay consistent
        if (!empty($update['map_item_id'])) {
            $map = Library::find((int) $update['map_item_id']);
            if ($map && !empty($map['theme'])) {
                $update['theme'] = $map['theme'];
            }
        }

        if ($update) {
            Project::touch((int) $project['id'], $update);
        }
    }

    // -----------------------------------------------------------------
    //  Step 3: upload the background (FR-31)
    // -----------------------------------------------------------------

    public function upload(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $file    = $request->file('background');

        if (!$file) {
            Flash::error('No image was selected.');
            $this->back('/create/' . (int) $project['id'] . '/step/3');
            return;
        }

        $result = Uploader::handle($file, $this->userId(), (int) $project['id'], 'backgrounds');

        if (!$result['ok']) {
            Flash::error($result['message']);
            $this->back('/create/' . (int) $project['id'] . '/step/3');
            return;
        }

        Project::touch((int) $project['id'], [
            'background_id' => $result['asset_id'],
            'status'        => $project['status'] === 'draft' ? 'in_progress' : $project['status'],
        ]);

        Flash::success('Background uploaded and composed into the map.');
        Response::redirect('/create/' . (int) $project['id'] . '/step/3');
    }

    // -----------------------------------------------------------------
    //  Step 4: auto-match and generate mission cards (FR-24, FR-35)
    // -----------------------------------------------------------------

    public function generateMissions(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $plan    = Auth::plan();

        $subjects = array_values(array_intersect($request->arr('subjects'), MissionMatcher::subjectKeys()));
        if (!$subjects) {
            $subjects = Project::subjects($project);
        }
        if ($subjects) {
            Project::touch((int) $project['id'], ['subjects' => implode(',', $subjects)]);
        }

        $difficulty = (string) $project['difficulty'];
        $cells      = (int) $project['cells'];
        $total      = Difficulty::missionCount($difficulty);

        $cards = MissionMatcher::generate($subjects, $difficulty, $plan, $cells, $total);

        if (!$cards) {
            Flash::error('No mission templates match those subjects. Try adding another subject.');
            $this->back('/create/' . (int) $project['id'] . '/step/4');
            return;
        }

        MissionMatcher::saveForProject((int) $project['id'], $cards);

        // Also write the story and rules if they are still empty
        $update = ['question_count' => count($cards)];
        if (trim((string) $project['story']) === '' || trim((string) $project['how_to_play']) === '') {
            $seed = PromptGenerator::storySeed($project);
            if (trim((string) $project['story']) === '')       { $update['story']       = $seed['story']; }
            if (trim((string) $project['how_to_play']) === '') { $update['how_to_play'] = $seed['how_to_play']; }
        }
        Project::touch((int) $project['id'], $update);

        Flash::success('Matched ' . count($cards) . ' mission cards from the library.');
        Response::redirect('/create/' . (int) $project['id'] . '/step/4');
    }

    // -----------------------------------------------------------------
    //  FR-06: import an existing blueprint
    // -----------------------------------------------------------------

    public function importForm(Request $request): void
    {
        $this->view('create/import', ['pageTitle' => 'Import blueprint']);
    }

    public function import(Request $request): void
    {
        $file = $request->file('blueprint');

        if (!$file) {
            Flash::error('No file was selected.');
            $this->back('/import');
            return;
        }

        if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
            Flash::error('A blueprint file cannot be larger than 3 MB.');
            $this->back('/import');
            return;
        }

        $raw  = @file_get_contents($file['tmp_name'] ?? '');
        $data = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($data) || empty($data['title'])) {
            Flash::error('That is not a valid GameCraft blueprint (.json) file.');
            $this->back('/import');
            return;
        }

        if (!Project::canCreate($this->userId(), Auth::plan())) {
            Flash::warning('You have reached the project limit on your current plan.');
            Response::redirect('/billing');
            return;
        }

        $difficulty = $data['difficulty'] ?? Difficulty::STANDARD;
        if (!Difficulty::exists($difficulty) || !Tiers::allowsDifficulty(Auth::plan(), $difficulty)) {
            $difficulty = Difficulty::STANDARD;
        }

        $theme = $data['theme'] ?? 'forest';
        if (!isset(Art::THEMES[$theme])) {
            $theme = 'forest';
        }

        $projectId = Project::create($this->userId(), [
            'title'       => mb_substr((string) $data['title'], 0, 160),
            'theme'       => $theme,
            'difficulty'  => $difficulty,
            'subjects'    => is_array($data['subjects'] ?? null) ? implode(',', $data['subjects']) : 'math,nature',
            'story'       => isset($data['story']) ? mb_substr((string) $data['story'], 0, 4000) : null,
            'how_to_play' => isset($data['how_to_play']) ? mb_substr((string) $data['how_to_play'], 0, 4000) : null,
        ]);

        // Import the mission cards too, if the file has any
        if (!empty($data['missions']) && is_array($data['missions'])) {
            $cards = [];
            $cells = Difficulty::cells($difficulty);
            foreach (array_slice($data['missions'], 0, Difficulty::missionCount($difficulty)) as $i => $m) {
                if (empty($m['question'])) {
                    continue;
                }
                $cards[] = [
                    'template_id' => null,
                    'subject'     => $m['subject'] ?? null,
                    'sticker'     => isset(Art::STICKERS[$m['sticker'] ?? '']) ? $m['sticker'] : 'star',
                    'question'    => mb_substr((string) $m['question'], 0, 500),
                    'answer'      => mb_substr((string) ($m['answer'] ?? ''), 0, 500),
                    'cell_no'     => (int) floor($i / Difficulty::MISSIONS_PER_CELL) % $cells + 1,
                    'slot_no'     => $i % Difficulty::MISSIONS_PER_CELL + 1,
                ];
            }
            if ($cards) {
                MissionMatcher::saveForProject($projectId, $cards);
            }
        }

        Flash::success('Imported the blueprint "' . $data['title'] . '".');
        Response::redirect('/create/' . $projectId . '/step/2');
    }

    // -----------------------------------------------------------------
    //  Helpers
    // -----------------------------------------------------------------

    private function clampStep(int $step): int
    {
        return max(1, min(self::LAST_STEP, $step));
    }

    /** Counts locked items, so the UI can invite an upgrade */
    private function lockedCount(string $kind, string $plan, ?int $cells = null): int
    {
        $filters = $cells ? ['cells' => $cells] : [];
        return count(Library::allOfKind($kind, $filters)) - count(Library::forPlan($kind, $plan, $filters));
    }
}
