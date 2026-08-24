<?php
namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Project;
use App\Services\Library;
use App\Services\Tiers;

/**
 * Home / dashboard - FR-04 to FR-08 and FR-19 to FR-22.
 * The layout follows the reference screenshot.
 */
class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $userId = $this->userId();
        $plan   = \App\Core\Auth::plan();

        $this->view('dashboard/index', [
            'pageTitle'     => 'Dashboard',
            'projects'      => Project::forUser($userId, ['sort' => 'recent', 'limit' => 4]),
            'projectCount'  => Project::countForUser($userId),
            'plan'          => Tiers::get($plan),
            'planKey'       => $plan,

            // FR-17: how many ready-made templates exist
            'templateCount' => Database::count('SELECT COUNT(*) FROM game_templates WHERE is_active = 1'),
            'templates'     => Database::all(
                'SELECT * FROM game_templates WHERE is_active = 1 ORDER BY uses_count DESC LIMIT 4'
            ),

            // FR-19: community inspiration. Empty while Discover is switched off,
            // which also hides the card on the dashboard.
            'community'     => Config::get('discover_enabled', false)
                ? Database::all(
                    'SELECT * FROM community_posts ORDER BY is_featured DESC, likes DESC LIMIT 3'
                )
                : [],

            // Quick library counts for the current plan
            'libraryStats'  => [
                'maps'       => Library::countForPlan(Library::KIND_MAP, $plan),
                'characters' => Library::countForPlan(Library::KIND_CHARACTER, $plan),
                'moves'      => Library::countForPlan(Library::KIND_MOVE, $plan),
                'rewards'    => Library::countForPlan(Library::KIND_REWARD, $plan),
            ],

            'exportCount'   => Database::count('SELECT COUNT(*) FROM exports WHERE user_id = ?', [$userId]),
        ]);
    }
}
