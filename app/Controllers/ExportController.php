<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Models\Project;
use App\Services\Difficulty;
use App\Services\Library;
use App\Services\MissionMatcher;
use App\Services\PrintBundle;
use App\Services\Tiers;

/**
 * Preview, print export and export history.
 *
 *   FR-26: a preview screen where cards can be swapped before exporting.
 *   FR-27: the export follows the required page order (see PrintBundle).
 *   FR-32: product listings for Amazon / Etsy - Publisher plan only.
 */
class ExportController extends Controller
{
    /** FR-26: the product preview screen */
    public function preview(Request $request, array $params): void
    {
        $project  = $this->ownedProject((int) ($params['id'] ?? 0));
        $sections = PrintBundle::build($project, Auth::plan());

        $this->view('exports/preview', [
            'pageTitle'  => 'Preview - ' . $project['title'],
            'project'    => $project,
            'sections'   => $sections,
            'totalPages' => PrintBundle::totalPages($sections),
            'readiness'  => PrintBundle::readiness($project),
            'items'      => Project::libraryItems($project),
            'missions'   => array_slice(MissionMatcher::forProject((int) $project['id']), 0, 9),
            'moveCards'  => PrintBundle::moveCards($project),
            'canListing' => Tiers::canPublishToMarketplace(Auth::plan()),
        ]);
    }

    /**
     * FR-27: the complete print page.
     * The user hits the browser's Print and chooses "Save as PDF" - no PDF
     * library is needed on the server, so it runs on any cPanel host.
     */
    public function print(Request $request, array $params): void
    {
        $project  = $this->ownedProject((int) ($params['id'] ?? 0));
        $sections = PrintBundle::build($project, Auth::plan());

        // Allow printing one section on its own
        $only = $request->str('only');
        if ($only !== '') {
            $keys     = array_map('trim', explode(',', $only));
            $sections = array_values(array_filter($sections, fn($s) => in_array($s['key'], $keys, true)));
        }

        $this->view('print/bundle', [
            'pageTitle'  => $project['title'],
            'project'    => $project,
            'sections'   => $sections,
            'totalPages' => PrintBundle::totalPages($sections),
        ], 'layouts/print');
    }

    /** Records one export in the history */
    public function create(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $format  = $request->str('format', 'print');

        if (!in_array($format, ['print', 'blueprint'], true)) {
            $format = 'print';
        }

        // Export a JSON blueprint that can be imported again later (pairs with FR-06)
        if ($format === 'blueprint') {
            $this->downloadBlueprint($project);
            return;
        }

        $sections = PrintBundle::build($project, Auth::plan());
        $pages    = PrintBundle::totalPages($sections);

        PrintBundle::recordExport(
            $this->userId(),
            (int) $project['id'],
            'print',
            $pages,
            'Full print bundle - ' . $pages . ' pages'
        );

        // Mark the project as ready
        if (in_array($project['status'], ['draft', 'in_progress'], true)) {
            Project::touch((int) $project['id'], ['status' => 'ready']);
        }

        Response::redirect('/print/' . (int) $project['id']);
    }

    /** Downloads the JSON blueprint */
    private function downloadBlueprint(array $project): void
    {
        $missions = [];
        foreach (MissionMatcher::forProject((int) $project['id']) as $m) {
            $missions[] = [
                'question' => $m['question'],
                'answer'   => $m['answer'],
                'subject'  => $m['subject'],
                'sticker'  => $m['sticker'],
            ];
        }

        $blueprint = [
            'gamecraft_version' => GC_VERSION,
            'exported_at'       => date('c'),
            'title'             => $project['title'],
            'theme'             => $project['theme'],
            'difficulty'        => $project['difficulty'],
            'cells'             => (int) $project['cells'],
            'subjects'          => Project::subjects($project),
            'age_min'           => (int) $project['age_min'],
            'age_max'           => (int) $project['age_max'],
            'players_min'       => (int) $project['players_min'],
            'players_max'       => (int) $project['players_max'],
            'hero_name'         => $project['hero_name'],
            'story'             => $project['story'],
            'how_to_play'       => $project['how_to_play'],
            'missions'          => $missions,
        ];

        PrintBundle::recordExport(
            $this->userId(),
            (int) $project['id'],
            'blueprint',
            0,
            'JSON blueprint'
        );

        $json = json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $name = \App\Core\Helper::slug((string) $project['title']) . '-blueprint.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen((string) $json));
        echo $json;
        exit;
    }

    /** The My Exports module: export history */
    public function index(Request $request): void
    {
        $userId = $this->userId();

        $rows = Database::all(
            'SELECT e.*, p.title AS project_title, p.theme, p.cover_seed
             FROM exports e
             LEFT JOIN projects p ON p.id = e.project_id
             WHERE e.user_id = ?
             ORDER BY e.created_at DESC, e.id DESC
             LIMIT 200',
            [$userId]
        );

        $this->view('exports/index', [
            'pageTitle' => 'My exports',
            'exports'   => $rows,
            'total'     => count($rows),
        ]);
    }

    public function destroy(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $row = Database::first('SELECT * FROM exports WHERE id = ? AND user_id = ? LIMIT 1', [$id, $this->userId()]);
        if (!$row) {
            Response::abort(404, 'That record no longer exists.');
        }

        Database::delete('exports', ['id' => $id]);
        Flash::success('Removed from your history.');
        $this->back('/exports');
    }

    // -----------------------------------------------------------------
    //  FR-32: product listings for Amazon / Etsy (Publisher plan only)
    // -----------------------------------------------------------------

    public function listing(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));

        if (!Tiers::canPublishToMarketplace(Auth::plan())) {
            Flash::warning('Sales listings are a Publisher plan feature.');
            Response::redirect('/billing');
            return;
        }

        $existing = Database::first(
            'SELECT * FROM listings WHERE project_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $project['id'], $this->userId()]
        );

        $this->view('exports/listing', [
            'pageTitle' => 'Sales listing - ' . $project['title'],
            'project'   => $project,
            'listing'   => $existing,
            'draft'     => $this->buildListingDraft($project),
        ]);
    }

    public function saveListing(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));

        if (!Tiers::canPublishToMarketplace(Auth::plan())) {
            Response::abort(403, 'This is a Publisher plan feature.');
        }

        $channel = $request->str('channel', 'etsy');
        if (!in_array($channel, ['etsy', 'amazon'], true)) {
            $channel = 'etsy';
        }

        Database::insert('listings', [
            'user_id'       => $this->userId(),
            'project_id'    => (int) $project['id'],
            'channel'       => $channel,
            'title'         => mb_substr(trim($request->str('title')), 0, 200),
            'bullet_points' => mb_substr(trim($request->str('bullet_points')), 0, 2000),
            'description'   => mb_substr(trim($request->str('description')), 0, 5000),
            'tags'          => mb_substr(trim($request->str('tags')), 0, 400),
            'price_cents'   => max(0, (int) round((float) $request->str('price', '0') * 100)),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        PrintBundle::recordExport(
            $this->userId(),
            (int) $project['id'],
            'listing',
            0,
            ucfirst($channel) . ' sales listing'
        );

        Flash::success('Saved the ' . ucfirst($channel) . ' listing.');
        Response::redirect('/listing/' . (int) $project['id']);
    }

    /** Drafts the listing copy from the project's own details */
    private function buildListingDraft(array $project): array
    {
        $diff  = Difficulty::get((string) $project['difficulty']);
        $ages  = \App\Core\Helper::ageRange((int) $project['age_min'], (int) $project['age_max']);
        $count = MissionMatcher::countForProject((int) $project['id']);

        $title = $project['title'] . ' - Printable Adventure Board Game for Kids '
               . $ages . ' | ' . $diff['cells'] . ' Spaces | Instant Download PDF';

        $bullets = [
            'PRINT AT HOME - instant digital download, no shipping, print as many times as you like.',
            'COMPLETE SET - game map, story, rules, ' . Difficulty::MOVE_CARDS_PER_GAME
                . ' move cards, ' . $count . ' mission cards, winner hero card and player tokens.',
            'AGES ' . $ages . ' - ' . $diff['cells'] . ' mission spaces, plays in about '
                . $diff['play_minutes'] . ' minutes with '
                . \App\Core\Helper::playerRange((int) $project['players_min'], (int) $project['players_max']) . '.',
            'LEARNING THROUGH PLAY - every space asks a question, so children practise while they play.',
            'READY TO PRINT - standard A4 and US Letter friendly, cut lines included on every card sheet.',
        ];

        $description = trim((string) $project['story']) . "\n\n"
            . "WHAT YOU GET\n"
            . "1. Game map (1 page)\n"
            . "2. Story page\n"
            . "3. How to play page\n"
            . "4. " . Difficulty::MOVE_CARDS_PER_GAME . " move cards\n"
            . "5. " . $count . " mission cards\n"
            . "6. Winner hero card\n"
            . "7. Player tokens\n\n"
            . "HOW TO USE\n"
            . "Download the PDF, print on A4 or Letter paper, cut along the marked lines and play.\n\n"
            . "This is a digital product. Nothing will be shipped.";

        $tags = implode(', ', [
            'printable board game', 'kids game', 'family game night', 'homeschool',
            'classroom game', 'instant download', 'adventure game', 'educational game',
            $project['theme'] . ' theme', 'ages ' . $ages,
        ]);

        return [
            'title'         => mb_substr($title, 0, 200),
            'bullet_points' => implode("\n", $bullets),
            'description'   => $description,
            'tags'          => $tags,
            'price'         => '4.99',
        ];
    }
}
