<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Project;

/**
 * Game library - the user's finished and published games.
 * Unlike My Projects, this only lists games that are ready or published.
 */
class LibraryController extends Controller
{
    public function index(Request $request): void
    {
        $userId = $this->userId();
        $search = $request->str('q');

        $sql    = "SELECT * FROM projects WHERE user_id = ? AND status IN ('ready', 'published')";
        $params = [$userId];

        if ($search !== '') {
            $sql .= ' AND title LIKE ?';
            $params[] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY updated_at DESC';

        $projects = Database::all($sql, $params);

        // Most recent export for each project
        $lastExport = [];
        foreach (Database::all(
            'SELECT project_id, MAX(created_at) AS last_at, COUNT(*) AS times
             FROM exports WHERE user_id = ? AND project_id IS NOT NULL GROUP BY project_id',
            [$userId]
        ) as $row) {
            $lastExport[(int) $row['project_id']] = $row;
        }

        $this->view('library/index', [
            'pageTitle'   => 'Game library',
            'projects'    => $projects,
            'search'      => $search,
            'lastExport'  => $lastExport,
            'totalReady'  => Project::countForUser($userId, 'ready'),
            'totalPub'    => Project::countForUser($userId, 'published'),
            'totalAll'    => Project::countForUser($userId),
        ]);
    }
}
