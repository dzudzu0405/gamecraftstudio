<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Services\AccessLink;
use App\Services\LoginCode;
use App\Services\Tiers;

/**
 * The address a WarriorPlus buyer is sent to after paying: /access/<code>
 *
 * Three ways in, all ending in the same place:
 *
 *   signed in, verified   -> the plan is applied, straight to Billing
 *   signed in, unverified -> the code is parked, off to confirm the email
 *   not signed in         -> the code is parked, and they are shown what they
 *                            have bought with a way to sign in or register
 *
 * Parking the code in the session is what makes the round trip work: whichever
 * way they come back - password, Google, a fresh account - the plan is waiting
 * for them and they never have to find the email again.
 */
class AccessController extends Controller
{
    public function show(Request $request, array $params): void
    {
        $code = (string) ($params['code'] ?? '');
        $link = AccessLink::find($code);

        // A wrong address is far more likely to be a mistyped link than an
        // attack, so it says what to check rather than just 404ing
        if (!$link) {
            $this->claimPage(null, 'error', 'That access link was not recognised. Please check it against the one in your WarriorPlus receipt - it is easy to lose a character when copying by hand.');
            return;
        }

        $problem = AccessLink::problem($link);

        if ($problem !== null) {
            $this->claimPage($link, 'error', $problem);
            return;
        }

        AccessLink::putPending((string) $link['code']);

        if (!Auth::check()) {
            $this->claimPage($link, null, null);
            return;
        }

        $user = Auth::user();

        // The link hands out a paid plan, so the address behind the account has
        // to be one this person actually holds
        if (LoginCode::isPending($user)) {
            Flash::info('Confirm your email address and your ' . Tiers::name((string) $link['plan']) . ' plan is applied straight away.');
            Response::redirect('/verify');
            return;
        }

        $result = AccessLink::redeem($link, $user, $request->ip());
        AccessLink::forgetPending();

        if ($result['type'] === 'success') {
            Flash::success($result['message']);
        } elseif ($result['type'] === 'info') {
            Flash::info($result['message']);
        } else {
            Flash::error($result['message']);
        }

        Response::redirect($result['ok'] ? '/billing' : '/');
    }

    /**
     * The page a guest lands on: what they bought, and how to claim it.
     *
     * @param array|null  $link    null when the code matched nothing
     * @param string|null $problem 'error' when $message explains a refusal
     */
    private function claimPage(?array $link, ?string $problem, ?string $message): void
    {
        // Somebody arriving on a dead link should not carry a stale code around
        if ($problem !== null) {
            AccessLink::forgetPending();
        }

        $plan = $link ? Tiers::get((string) $link['plan']) : null;

        $this->view('access/claim', [
            'pageTitle' => $plan ? ('Unlock ' . $plan['name']) : 'Access link',
            'link'      => $link,
            'plan'      => $plan,
            'error'     => $problem === 'error' ? $message : null,
            'signedIn'  => Auth::check(),
            'googleOn'  => \App\Services\GoogleAuth::isEnabled(),
        ], 'layouts/blank');
    }
}
