<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\Entitlements;
use App\Services\Tiers;

/**
 * Every plan change the site has ever made, filterable.
 *
 * This is the record a refund is checked against: it says which link an
 * account came in on, at what time and from which address, and who moved it
 * afterwards. Nothing here can be edited - a log that could be tidied up is
 * not worth keeping.
 */
class ActivityController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): void
    {
        $source = $request->str('source');
        $plan   = $request->str('plan');
        $page   = max(1, $request->int('page', 1));

        $where  = [];
        $params = [];

        $sources = [
            Entitlements::SOURCE_LINK,
            Entitlements::SOURCE_ADMIN,
            Entitlements::SOURCE_REGISTER,
            Entitlements::SOURCE_GOOGLE,
        ];

        if (in_array($source, $sources, true)) {
            $where[]  = 'e.source = ?';
            $params[] = $source;
        }

        if (Tiers::exists($plan)) {
            $where[]  = 'e.plan_to = ?';
            $params[] = $plan;
        }

        $sql   = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $total = Database::count('SELECT COUNT(*) FROM plan_events e' . $sql, $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        $rows = Database::all(
            'SELECT e.*, u.name AS user_name, u.email AS user_email,
                    l.label AS link_label, a.name AS actor_name
               FROM plan_events e
          LEFT JOIN users u        ON u.id = e.user_id
          LEFT JOIN access_links l ON l.id = e.link_id
          LEFT JOIN users a        ON a.id = e.actor_id'
            . $sql
            . ' ORDER BY e.created_at DESC, e.id DESC LIMIT ' . self::PER_PAGE
            . ' OFFSET ' . (($page - 1) * self::PER_PAGE),
            $params
        );

        $this->view('admin/activity', [
            'pageTitle' => 'Plan activity',
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'filters'   => ['source' => $source, 'plan' => $plan],
        ]);
    }
}
