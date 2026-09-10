<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AccessLink;
use App\Services\LoginCode;

/**
 * Confirming an email address with the six-digit code.
 *
 * Reached only once per account, on the first password sign-in. Google accounts
 * never come through here, and neither does anybody who registered before this
 * step existed - the upgrade marks those addresses as already confirmed rather
 * than locking people out of accounts they have been using for months.
 */
class VerifyController extends Controller
{
    /** Remembers that this session has already had a code sent, so a refresh does not send another */
    private const SENT_KEY = '_verify_sent_for';

    public function show(Request $request): void
    {
        $user = $this->user();

        if (!LoginCode::isPending($user)) {
            Response::redirect('/');
            return;
        }

        // First look at the page sends the code. A reload does not, or a wrong
        // guess followed by F5 would quietly invalidate the code they are holding.
        if (Session::get(self::SENT_KEY) !== (int) $user['id']) {
            $result = LoginCode::issue($user, $request->ip());

            Session::put(self::SENT_KEY, (int) $user['id']);

            if (!$result['ok']) {
                Flash::error($result['message']);
            }
        }

        $pendingLink = AccessLink::peekPending();

        $this->view('auth/verify', [
            'pageTitle'   => 'Confirm your email',
            'email'       => (string) $user['email'],
            'hasPending'  => $pendingLink !== null,
        ], 'layouts/blank');
    }

    public function check(Request $request): void
    {
        $user = $this->user();

        if (!LoginCode::isPending($user)) {
            Response::redirect('/');
            return;
        }

        $result = LoginCode::check((int) $user['id'], (string) $request->input('code', ''));

        if (!$result['ok']) {
            Flash::error($result['message']);
            $this->back('/verify');
            return;
        }

        Auth::refresh();
        Session::forget(self::SENT_KEY);

        Flash::success($result['message']);

        // A buyer who came in on a WarriorPlus link has been waiting for this
        AccessLink::applyPending(Auth::user(), $request->ip());

        $intended = Session::pull('_intended', '/');
        Response::redirect(is_string($intended) && $intended !== '/verify' ? $intended : '/');
    }

    public function resend(Request $request): void
    {
        $user = $this->user();

        if (!LoginCode::isPending($user)) {
            Response::redirect('/');
            return;
        }

        $result = LoginCode::issue($user, $request->ip());

        if ($result['ok']) {
            Flash::success($result['message']);
        } else {
            Flash::error($result['message']);
        }

        $this->back('/verify');
    }
}
