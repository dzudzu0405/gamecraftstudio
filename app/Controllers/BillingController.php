<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Models\Project;
use App\Services\Library;
use App\Services\Tiers;

/**
 * Plans and billing - FR-21, FR-22, FR-28, FR-29.
 *
 * Plans are sold on WarriorPlus, which takes the payment. Nothing in the app may
 * change a plan on its own, so changePlan() is closed to everyone but an
 * administrator - see the guard there. To fulfil purchases automatically, run the
 * same update from a WarriorPlus notification rather than reopening this screen.
 */
class BillingController extends Controller
{
    public function index(Request $request): void
    {
        $user    = $this->user();
        $userId  = $this->userId();
        $current = Auth::plan();

        // Real counts from the database for what each tier unlocks
        $unlocked = [];
        foreach (Tiers::ORDER as $tier) {
            $unlocked[$tier] = [
                'maps'       => Library::countForPlan(Library::KIND_MAP, $tier),
                'characters' => Library::countForPlan(Library::KIND_CHARACTER, $tier),
                'moves'      => Library::countForPlan(Library::KIND_MOVE, $tier),
                'rewards'    => Library::countForPlan(Library::KIND_REWARD, $tier),
                'missions'   => Database::count(
                    'SELECT COUNT(*) FROM mission_templates WHERE is_active = 1 AND tier IN ('
                    . implode(',', array_fill(0, count(Tiers::unlockedTiers($tier)), '?')) . ')',
                    Tiers::unlockedTiers($tier)
                ),
            ];
        }

        $this->view('billing/index', [
            'pageTitle'    => 'Plans',
            'tiers'        => Tiers::all(),
            'current'      => $current,
            'unlocked'     => $unlocked,
            'projectCount' => Project::countForUser($userId),
            'limit'        => Tiers::projectLimit($current),
            'startedAt'    => $user['plan_started_at'] ?? null,

            // Only an administrator may move a plan; everyone else buys on WarriorPlus
            'canSwitchPlan' => Auth::isAdmin(),
            'purchaseUrl'   => trim((string) Config::get('purchase_url', '')),
        ]);
    }

    public function changePlan(Request $request): void
    {
        // Plans are bought on WarriorPlus. Without this guard any signed-in user
        // could POST here and hand themselves the Publisher plan for nothing.
        if (!Auth::isAdmin()) {
            Flash::warning('Plans are sold through WarriorPlus. Your access updates once a purchase is confirmed.');
            $this->back('/billing');
            return;
        }

        $plan = $request->str('plan');

        if (!Tiers::exists($plan)) {
            Flash::error('That is not a valid plan.');
            $this->back('/billing');
            return;
        }

        $current = Auth::plan();

        if ($plan === $current) {
            Flash::info('You are already on the ' . Tiers::name($plan) . ' plan.');
            $this->back('/billing');
            return;
        }

        // Downgrading: refuse if the user is over the lower plan's project limit
        if (Tiers::rank($plan) < Tiers::rank($current)) {
            $limit = Tiers::projectLimit($plan);
            $count = Project::countForUser($this->userId());

            if ($limit > 0 && $count > $limit) {
                Flash::error('You have ' . $count . ' projects, which is over the ' . $limit
                    . '-project limit on the ' . Tiers::name($plan) . ' plan. Please delete some first.');
                $this->back('/billing');
                return;
            }
        }

        Database::update('users', [
            'plan'            => $plan,
            'plan_started_at' => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ], ['id' => $this->userId()]);

        Auth::refresh();

        $up = Tiers::rank($plan) > Tiers::rank($current);
        Flash::success(($up ? 'Upgraded to the ' : 'Moved to the ') . Tiers::name($plan) . ' plan.');

        Response::redirect('/billing');
    }
}
