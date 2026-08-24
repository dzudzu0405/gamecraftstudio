<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Services\Art;
use App\Services\Library;
use App\Services\Tiers;
use App\Services\Uploader;

/**
 * Asset library.
 *
 * Two halves:
 *   1. THE SYSTEM LIBRARY - ready-made maps, characters and cards. This screen
 *      shows which items already have REAL ARTWORK and which are still using a
 *      placeholder, measured against the production targets in SRS section 11 (FR-34).
 *   2. THE USER'S OWN IMAGES - backgrounds uploaded for composing maps.
 */
class AssetController extends Controller
{
    private const KINDS = [
        Library::KIND_MAP       => 'Maps',
        Library::KIND_CHARACTER => 'Characters',
        Library::KIND_MOVE      => 'Move cards',
        Library::KIND_REWARD    => 'Hero cards',
    ];

    public function index(Request $request): void
    {
        $userId = $this->userId();
        $plan   = Auth::plan();

        $kind = $request->str('kind', Library::KIND_MAP);
        if (!isset(self::KINDS[$kind])) {
            $kind = Library::KIND_MAP;
        }

        $tab = $request->str('tab', 'library');
        if (!in_array($tab, ['library', 'uploads'], true)) {
            $tab = 'library';
        }

        $items = Library::allOfKind($kind, [
            'theme'  => $request->str('theme'),
            'tier'   => $request->str('tier'),
            'search' => $request->str('q'),
        ]);

        $uploads = Database::all(
            'SELECT a.*, p.title AS project_title
             FROM user_assets a
             LEFT JOIN projects p ON p.id = a.project_id
             WHERE a.user_id = ?
             ORDER BY a.created_at DESC
             LIMIT 200',
            [$userId]
        );

        $this->view('assets/index', [
            'pageTitle'  => 'Asset library',
            'kind'       => $kind,
            'kinds'      => self::KINDS,
            'tab'        => $tab,
            'items'      => $items,
            'themes'     => Art::THEMES,
            'theme'      => $request->str('theme'),
            'tier'       => $request->str('tier'),
            'search'     => $request->str('q'),
            'planKey'    => $plan,
            'uploads'    => $uploads,
            'usage'      => Uploader::usageBytes($userId),
            'progress'   => Library::artworkProgress(),
            'isAdmin'    => Auth::isAdmin(),
        ]);
    }

    /** Uploads a background straight into the asset library, not tied to a project */
    public function upload(Request $request): void
    {
        $file = $request->file('image');

        if (!$file) {
            Flash::error('No image was selected.');
            $this->back('/assets?tab=uploads');
            return;
        }

        $result = Uploader::handle($file, $this->userId(), null, 'backgrounds');

        if (!$result['ok']) {
            Flash::error($result['message']);
        } else {
            Flash::success('Image added to your asset library.');
        }

        Response::redirect('/assets?tab=uploads');
    }

    public function destroy(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if (!Uploader::delete($id, $this->userId())) {
            Response::abort(404, 'That image no longer exists.');
        }

        Flash::success('Image deleted.');
        Response::redirect('/assets?tab=uploads');
    }
}
