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
use App\Services\Lang;
use App\Services\Library;
use App\Services\MissionMatcher;
use App\Services\PrintBundle;
use App\Services\PromptGenerator;
use App\Services\Tiers;
use App\Services\Uploader;

/**
 * The new-game flow - an ASSEMBLER model, exactly as SRS section 2.3 describes.
 *
 * The flow has five steps:
 *
 *   1. Basics        - title, theme, difficulty, question subjects (FR-23)
 *   2. Library picks - map frame and character set, filtered by plan
 *   3. Background & story - copy the prompts (FR-30), bring back the picture and the story
 *   4. Mission cards - matched automatically from the library (FR-24, FR-35)
 *   5. Finish        - review, then move on to the Studio
 */
class CreateController extends Controller
{
    private const LAST_STEP = 5;

    private const STEP_LABELS = [
        1 => 'Basics',
        2 => 'Library picks',
        3 => 'Background & story',
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
          ->in('theme', array_merge(array_keys(Art::THEMES), [Project::THEME_CUSTOM]), 'theme')
          ->in('difficulty', array_keys(Difficulty::all()), 'difficulty')
          ->in('language', array_keys(Lang::LOCALES), 'language')
          ->max('setting_other', 120, 'the adventure you described')
          ->max('rescue_target', 120, 'who the game rescues')
          ->between('players_min', Project::MIN_PLAYERS, Project::MAX_PLAYERS, 'the minimum player count')
          ->between('players_max', Project::MIN_PLAYERS, Project::MAX_PLAYERS, 'the maximum player count');

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

        $choice = $this->readThemeChoice($request);

        $projectId = Project::create($this->userId(), [
            'title'       => $request->str('title'),
            'theme'       => $choice['theme'] ?? 'forest',
            'background_mode' => $choice['background_mode'] ?? Project::BACKGROUND_THEME,
            'difficulty'  => $difficulty,
            'subjects'    => implode(',', $subjects),
            'setting'       => $this->readSetting($request),
            'language'      => $request->str('language'),
            'rescue_target' => mb_substr(trim($request->str('rescue_target')), 0, 120) ?: null,
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

        // A themed game has no background step; send anyone who lands there onward
        $stepsAvailable = $this->stepsFor($project);
        if (!in_array($step, $stepsAvailable, true)) {
            Response::redirect('/create/' . (int) $project['id'] . '/step/' . $this->nextStep($project, $step));
            return;
        }

        $data = [
            'pageTitle' => self::STEP_LABELS[$step] . ' · ' . $project['title'],
            'project'   => $project,
            'step'      => $step,
            'lastStep'  => (int) end($stepsAvailable),
            'labels'    => $this->stepLabelsFor($project),
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
                // withLocked, not forPlan: the tiles a plan has not bought are
                // shown greyed with a padlock rather than left out
                $data['maps'] = Library::withLocked(Library::KIND_MAP, $plan, [
                    'cells' => (int) $project['cells'],
                ]);
                $data['lockedMaps'] = $this->lockedCount(Library::KIND_MAP, $plan, (int) $project['cells']);
                $data['characters'] = Library::withLocked(Library::KIND_CHARACTER, $plan);
                $data['moves']      = Library::withLocked(Library::KIND_MOVE, $plan);
                // the portrait the winner card previews are drawn around
                $data['heroCharacter'] = PrintBundle::characterUrl($project);

                // a game with no move cards picks its mission frame directly
                $data['missionSets']   = Tiers::missionSets($plan);
                $data['missionStyle']  = PrintBundle::cardStyle($project);
                $data['cardStyles']    = PrintBundle::CARD_STYLES;
                break;

            case 3:
                // The art style is fixed: every game is a storybook illustration,
                // so the maps in one buyer's set look like they belong together.
                $style = PromptGenerator::DEFAULT_STYLE;
                $data['style']        = $style;
                $data['prompt']       = PromptGenerator::background($project, $style);
                $data['storyPrompt']  = PromptGenerator::story($project);
                $data['storyWords']   = PrintBundle::STORY_WORDS_PER_SHEET;
                $data['instructions'] = PromptGenerator::instructions();
                $data['background']   = !empty($project['background_id'])
                    ? Database::first('SELECT * FROM user_assets WHERE id = ?', [(int) $project['background_id']])
                    : null;
                break;

            case 4:
                $data['missionCount'] = MissionMatcher::countForProject((int) $project['id']);
                $data['expected']     = Difficulty::missionCount((string) $project['difficulty']);
                $data['subjects']     = MissionMatcher::SUBJECTS;
                $data['chosen']       = Project::subjects($project);
                $data['templates']    = MissionMatcher::matchTemplates(
                    Project::subjects($project),
                    (string) $project['difficulty'],
                    $plan,
                    Lang::of($project)
                );
                $data['variants']     = MissionMatcher::estimateVariants($data['templates']);
                $data['shapes']       = MissionMatcher::estimateShapes($data['templates']);
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

        // saveBasics may have just switched the branch, so re-read the project
        $project = Project::find($id, $this->userId()) ?? $project;

        $next = $this->nextStep($project, $step);
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

        $update['setting']       = $this->readSetting($request);
        $update['language']      = Lang::normalize($request->str('language'));
        $update['rescue_target'] = mb_substr(trim($request->str('rescue_target')), 0, 120) ?: null;

        $choice = $this->readThemeChoice($request);
        if ($choice['theme'] !== null) {
            $update['theme'] = $choice['theme'];
        }
        if ($choice['background_mode'] !== null) {
            $update['background_mode'] = $choice['background_mode'];
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
        $update['players_min'] = max(Project::MIN_PLAYERS, min(Project::MAX_PLAYERS, $min));
        $update['players_max'] = max(Project::MIN_PLAYERS, min(Project::MAX_PLAYERS, $max));

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

                // Dropping to Beginner takes the move-card option away with it
                if (!Project::canChooseMovement($plan)) {
                    $update['movement'] = Project::MOVE_DICE;
                }

                Flash::info('Difficulty changed, so the map frame and mission cards need choosing again.');
            }
        }

        Project::touch($id, $update);
    }

    /** Step 2: save the library picks, checking plan entitlements */
    private function saveLibraryChoices(Request $request, array $project, string $plan): void
    {
        $update = [];

        // reward_item_id is deliberately absent: step 2 no longer offers a hero
        // card picker, and a field the form never posts would be nulled below.
        // The winner card is drawn in CSS, so it is a name rather than a library row
        $heroStyle = $request->str('hero_style');
        if (isset(Project::HERO_STYLES[$heroStyle])) {
            $update['hero_style'] = $heroStyle;
        }

        /*
         * The mission frame, for a game with no move card to pair with. It is
         * artwork rather than a library row, so it is a number - checked
         * against the plan here, because a disabled radio is only a hint.
         */
        $missionStyle = $request->int('mission_style', 0);
        if ($missionStyle > 0) {
            if ($missionStyle <= Tiers::missionSets($plan)) {
                $update['mission_style'] = $missionStyle;
            } else {
                Flash::warning('That mission card design is not on your plan, so it was ignored.');
            }
        }

        $fields = [
            'map_item_id'       => Library::KIND_MAP,
            'character_item_id' => Library::KIND_CHARACTER,
            'move_item_id'      => Library::KIND_MOVE,
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

        /*
         * Dice or move cards. Beginner is dice whatever the form says - the
         * radio is disabled there, and a disabled control is a suggestion, not
         * a guarantee, so the rule is enforced again on this side.
         */
        $movement = $request->str('movement');
        if (in_array($movement, [Project::MOVE_DICE, Project::MOVE_CARDS], true)) {
            $update['movement'] = Project::canChooseMovement($plan)
                ? $movement
                : Project::MOVE_DICE;
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

    /**
     * The story, brought back from wherever the buyer had it written.
     *
     * Saved as typed. It is their words - or their AI tool's, which they have
     * read and accepted - so nothing here rewrites it. The only thing worth
     * saying back is how long it came out, because that decides whether the
     * story runs to a second printed sheet.
     */
    public function saveStory(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $pid     = (int) $project['id'];

        $story = trim($request->str('story'));
        $story = mb_substr($story, 0, 8000);

        Project::touch($pid, ['story' => $story]);

        if ($story === '') {
            Flash::success('Story cleared. The game will print without a story page.');
        } else {
            $words  = str_word_count(strip_tags($story)) ?: (int) ceil(mb_strlen($story) / 6);
            $sheets = count(PrintBundle::storyPages($story));

            Flash::success('Story saved - ' . number_format($words) . ' words, '
                . ($sheets === 1 ? 'one printed page.' : $sheets . ' printed pages.'));
        }

        Response::redirect('/create/' . $pid . '/step/3#story');
    }

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

        $difficulty = (string) $project['difficulty'];
        $cells      = (int) $project['cells'];
        $total      = Difficulty::missionCount($difficulty);

        $source = $request->str('question_source') === Project::QUESTIONS_OWN
            ? Project::QUESTIONS_OWN
            : Project::QUESTIONS_LIBRARY;

        if ($source === Project::QUESTIONS_OWN) {
            /*
             * The buyer's own list. It is kept as they typed it rather than
             * only as dealt cards, so coming back to step 4 shows their list
             * again and they can add to it.
             */
            $text      = $request->str('own_questions');
            $questions = MissionMatcher::parseOwnQuestions($text);

            if (!$questions) {
                Flash::error('Type at least one question, one per line.');
                Project::touch((int) $project['id'], [
                    'question_source' => Project::QUESTIONS_OWN,
                    'own_questions'   => mb_substr($text, 0, 20000),
                ]);
                $this->back('/create/' . (int) $project['id'] . '/step/4');
                return;
            }

            Project::touch((int) $project['id'], [
                'question_source' => Project::QUESTIONS_OWN,
                'own_questions'   => mb_substr($text, 0, 20000),
            ]);

            $cards = MissionMatcher::fromOwnQuestions($questions, $cells, $total);
            $note  = count($questions) >= $total
                ? 'Made ' . count($cards) . ' mission cards from your own questions.'
                : 'Made ' . count($cards) . ' mission cards from your ' . count($questions)
                    . ' questions, repeated to fill the game.';
        } else {
            $subjects = array_values(array_intersect($request->arr('subjects'), MissionMatcher::subjectKeys()));
            if (!$subjects) {
                $subjects = Project::subjects($project);
            }

            $update = ['question_source' => Project::QUESTIONS_LIBRARY];
            if ($subjects) {
                $update['subjects'] = implode(',', $subjects);
            }
            Project::touch((int) $project['id'], $update);

            $cards = MissionMatcher::generate($subjects, $difficulty, $plan, $cells, $total,
                                              null, Lang::of($project),
                                              MissionMatcher::questionsAlreadyUsed(
                                                  $this->userId(), (int) $project['id']));

            if (!$cards) {
                Flash::error('No mission templates match those subjects. Try adding another subject.');
                $this->back('/create/' . (int) $project['id'] . '/step/4');
                return;
            }

            $note = 'Matched ' . count($cards) . ' mission cards from the library.';
        }

        MissionMatcher::saveForProject((int) $project['id'], $cards);

        /*
         * The rules are written here if the game has none yet. The story is
         * not: it comes from the prompt at step 3, in the buyer's own words
         * or their own AI tool, and an invented one would only be in the way.
         */
        $update = ['question_count' => count($cards)];
        if (trim((string) $project['how_to_play']) === '') {
            $update['how_to_play'] = PromptGenerator::rules($project);
        }
        Project::touch((int) $project['id'], $update);

        Flash::success($note);
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

    /**
     * The steps this project actually walks through.
     *
     * Picking a ready-made theme means the background is already drawn, so step 3
     * drops out of the wizard: no prompt to copy, nothing to upload.
     */
    /**
     * Every step is on every game.
     *
     * Step 3 used to be skipped by a game keeping a ready-made theme
     * background, because the background was all it held. It holds the story
     * prompt now, and every game needs a story - so the step stays and the
     * background half of it is what disappears instead.
     */
    private function stepsFor(array $project): array
    {
        return array_keys(self::STEP_LABELS);
    }

    /** The same list as labels, ready for the progress bar */
    private function stepLabelsFor(array $project): array
    {
        $out = [];
        foreach ($this->stepsFor($project) as $n) {
            $out[$n] = self::STEP_LABELS[$n];
        }
        return $out;
    }

    /** The step after $step, or the last one when there is nothing further */
    private function nextStep(array $project, int $step): int
    {
        $steps = $this->stepsFor($project);
        foreach ($steps as $n) {
            if ($n > $step) {
                return $n;
            }
        }
        return (int) end($steps);
    }

    /**
     * Reads the theme radio, which doubles as the background choice.
     *
     * The extra tile posts 'custom', meaning the buyer makes their own background.
     * That is not a theme, so the theme column keeps whatever it had - step 2's map
     * frame is what sets it from then on.
     */
    private function readThemeChoice(Request $request): array
    {
        $choice = $request->str('theme');

        if ($choice === Project::THEME_CUSTOM) {
            return ['theme' => null, 'background_mode' => Project::BACKGROUND_CUSTOM];
        }

        if (isset(Art::THEMES[$choice])) {
            return ['theme' => $choice, 'background_mode' => Project::BACKGROUND_THEME];
        }

        return ['theme' => null, 'background_mode' => null];
    }
    /**
     * Reads the adventure picker: one of the twenty, or the buyer's own words.
     *
     * Both fields are posted every time. The select decides which one counts,
     * except with JavaScript off, where the box is always on screen - so
     * anything typed there wins over a select left on "choose an adventure".
     */
    private function readSetting(Request $request): ?string
    {
        $picked = trim($request->str('setting'));
        $own    = mb_substr(trim($request->str('setting_other')), 0, 120);

        if ($picked === Project::SETTING_OTHER || $picked === '') {
            return $own ?: null;
        }

        return Project::isListedSetting($picked) ? $picked : ($own ?: null);
    }

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
