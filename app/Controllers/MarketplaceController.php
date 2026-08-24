<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\Art;
use App\Services\Tiers;

/** Marketplace - buying, selling and sharing game templates and assets */
class MarketplaceController extends Controller
{
    public function index(Request $request): void
    {
        $kind   = $request->str('kind');
        $theme  = $request->str('theme');
        $search = $request->str('q');

        $sql    = 'SELECT * FROM marketplace_items WHERE is_active = 1';
        $params = [];

        if (in_array($kind, ['template', 'asset_pack', 'bundle'], true)) {
            $sql .= ' AND kind = ?';
            $params[] = $kind;
        }
        if ($theme !== '' && isset(Art::THEMES[$theme])) {
            $sql .= ' AND theme = ?';
            $params[] = $theme;
        }
        if ($search !== '') {
            $sql .= ' AND title LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY sales DESC, id ASC';

        $this->view('marketplace/index', [
            'pageTitle' => 'Marketplace',
            'items'     => Database::all($sql, $params),
            'themes'    => Art::THEMES,
            'kind'      => $kind,
            'theme'     => $theme,
            'search'    => $search,
            'canSell'   => Tiers::canPublishToMarketplace(Auth::plan()),
        ]);
    }
}
