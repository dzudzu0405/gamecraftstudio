<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\AccessLink;
use App\Services\Tiers;

/**
 * The administrator's first screen: how many accounts, on which plans, and
 * what has moved lately.
 *
 * Refunds are handled by hand here rather than by a WarriorPlus notification,
 * so the two lists at the bottom are the working surface: recent plan changes
 * with the link each came in on, and the newest accounts. A link redeemed far
 * more often than the product sold shows up as a number out of place in the
 * links screen, and that is the intended way of spotting one that has leaked.
 */
class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $planCounts = [];
        foreach (Tiers::ORDER as $tier) {
            $planCounts[$tier] = 0;
        }

        foreach (Database::all('SELECT plan, COUNT(*) AS n FROM users GROUP BY plan') as $row) {
            $planCounts[(string) $row['plan']] = (int) $row['n'];
        }

        $weekAgo = date('Y-m-d H:i:s', time() - 7 * 86400);

        $recentEvents = Database::all(
            'SELECT e.*, u.name AS user_name, u.email AS user_email,
                    l.label AS link_label, a.name AS actor_name
               FROM plan_events e
          LEFT JOIN users u        ON u.id = e.user_id
          LEFT JOIN access_links l ON l.id = e.link_id
          LEFT JOIN users a        ON a.id = e.actor_id
              WHERE e.source <> ?
           ORDER BY e.created_at DESC, e.id DESC
              LIMIT 12',
            ['register']
        );

        $newUsers = Database::all(
            'SELECT id, name, email, plan, created_at, email_verified_at, google_id
               FROM users ORDER BY id DESC LIMIT 8'
        );

        $this->view('admin/dashboard', [
            'pageTitle'  => 'Admin',
            'stats'      => [
                'users'      => Database::count('SELECT COUNT(*) FROM users'),
                'newWeek'    => Database::count('SELECT COUNT(*) FROM users WHERE created_at > ?', [$weekAgo]),
                'paid'       => Database::count('SELECT COUNT(*) FROM users WHERE plan <> ?', [Tiers::STARTER]),
                'unverified' => Database::count('SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL'),
                'disabled'   => Database::count('SELECT COUNT(*) FROM users WHERE is_active = 0'),
                'projects'   => Database::count('SELECT COUNT(*) FROM projects'),
                'exports'    => Database::count('SELECT COUNT(*) FROM exports'),
                'links'      => Database::count('SELECT COUNT(*) FROM access_links WHERE is_active = 1'),
                'redeemed'   => Database::count('SELECT COUNT(*) FROM plan_events WHERE link_id IS NOT NULL'),
            ],
            'planCounts' => $planCounts,
            'events'     => $recentEvents,
            'newUsers'   => $newUsers,
            'links'      => AccessLink::all(),
        ]);
    }
}
