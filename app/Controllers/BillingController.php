<?php
namespace App\Controllers;

use App\Core\Auth;
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
 * SCOPE: SRS section 7 places "the detailed payment flow" out of scope, so this
 * screen only switches plans and shows the matching entitlements. No payment
 * gateway is wired up. For a real integration, replace changePlan() with a
 * redirect to the gateway and wait for its webhook to confirm.
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
        ]);
    }

    public function changePlan(Request $request): void
    {
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
