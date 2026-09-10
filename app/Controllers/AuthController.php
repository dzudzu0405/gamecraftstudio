<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AccessLink;
use App\Services\Entitlements;
use App\Services\LoginCode;
use App\Services\Mailer;
use App\Services\Tiers;

/** Sign in, register and sign out */
class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $this->view('auth/login', ['pageTitle' => 'Sign in'], 'layouts/blank');
    }

    public function login(Request $request): void
    {
        $email    = $request->str('email');
        $password = (string) $request->input('password', '');

        $v = new Validator($request->body);
        $v->required('email', 'your email address')->email('email')
          ->required('password', 'your password');

        if ($v->fails()) {
            $this->backWithErrors($v->errors(), $request->body, '/login');
            return;
        }

        // Throttle repeated password attempts
        if ($this->tooManyAttempts()) {
            Flash::error('Too many failed attempts. Please wait a minute and try again.');
            $this->back('/login');
            return;
        }

        if (!Auth::attempt($email, $password)) {
            $this->recordFailedAttempt();
            Flash::error('That email or password is not correct.');
            $this->backWithErrors(['email' => 'Those sign-in details are not correct.'], ['email' => $email], '/login');
            return;
        }

        Session::forget('_login_attempts');

        // A brand new account still has to prove its address before the app
        // opens up. Anything parked - a WarriorPlus link, the page they were
        // heading for - waits in the session until it has.
        if (LoginCode::isPending(Auth::user())) {
            Response::redirect('/verify');
            return;
        }

        $intended = Session::pull('_intended', '/');

        Flash::success('Welcome back!');
        AccessLink::applyPending(Auth::user(), $request->ip());

        Response::redirect(is_string($intended) ? $intended : '/');
    }

    public function showRegister(Request $request): void
    {
        $this->view('auth/register', [
            'pageTitle' => 'Create account',
        ], 'layouts/blank');
    }

    public function register(Request $request): void
    {
        $v = new Validator($request->body);
        $v->required('name', 'your name')->max('name', 120, 'your name')
          ->required('email', 'your email address')->email('email')->max('email', 190, 'your email address')
          ->required('password', 'a password')->min('password', 8, 'your password')->max('password', 200, 'your password')
          ->matches('password_confirmation', 'password', 'the confirmation password');

        if ($v->passes() && Auth::emailExists($request->str('email'))) {
            $v->rule('email', false, 'That email address is already registered.');
        }

        if ($v->fails()) {
            $this->backWithErrors($v->errors(), $request->body, '/register');
            return;
        }

        /*
         * Every new account starts on Starter, whatever the form said.
         *
         * Plans are paid for on WarriorPlus and arrive through a delivery link
         * (see AccessController). Taking a plan from the registration form would
         * mean anybody willing to edit one hidden field could hand themselves
         * the Publisher tier for nothing.
         */
        $plan = Tiers::STARTER;

        $userId = Auth::register(
            $request->str('name'),
            $request->str('email'),
            (string) $request->input('password'),
            $plan
        );

        \App\Core\Database::update('users', ['plan_started_at' => date('Y-m-d H:i:s')], ['id' => $userId]);

        Entitlements::log($userId, null, $plan, Entitlements::SOURCE_REGISTER, null, null, null, $request->ip());

        $this->sendWelcomeEmail($request->str('name'), $request->str('email'), $plan);

        Auth::login($userId);

        // Nothing opens up until the address is confirmed - a plan can be
        // handed to this account by link, so it has to be an address they hold
        if (LoginCode::isPending(Auth::user())) {
            Flash::success('Account created. One quick check and you are in.');
            Response::redirect('/verify');
            return;
        }

        Flash::success('Account created. Time to build your first game!');
        AccessLink::applyPending(Auth::user(), $request->ip());

        Response::redirect('/');
    }

    /**
     * The welcome email is a nicety, not part of signing up. If the mail server
     * is down or misconfigured the account is still created and the person is
     * still signed in - the failure only goes to the error log.
     */
    private function sendWelcomeEmail(string $name, string $email, string $planKey): void
    {
        if (!Mailer::isConfigured()) {
            return;
        }

        $plan = Tiers::get($planKey);

        $sent = Mailer::sendTemplate($email, $name, 'welcome', [
            'subject'    => 'Welcome to GameCraft Studio',
            'firstName'  => trim(explode(' ', trim($name))[0] ?? '') ?: 'there',
            'planName'   => $plan['name'],
            'maps'       => $plan['maps_total'],
            'characters' => $plan['character_sets'],
        ]);

        if (!$sent) {
            error_log('[GameCraft] Welcome email to ' . $email . ' failed: ' . (Mailer::lastError() ?? 'unknown reason'));
        }
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Response::redirect('/login');
    }

    // -----------------------------------------------------------------
    //  Password attempt throttling
    // -----------------------------------------------------------------

    private function tooManyAttempts(): bool
    {
        $a = Session::get('_login_attempts', ['count' => 0, 'first' => time()]);

        // After 60 seconds the counter starts over
        if (time() - (int) $a['first'] > 60) {
            return false;
        }
        return (int) $a['count'] >= 6;
    }

    private function recordFailedAttempt(): void
    {
        $a = Session::get('_login_attempts', ['count' => 0, 'first' => time()]);

        if (time() - (int) $a['first'] > 60) {
            $a = ['count' => 0, 'first' => time()];
        }
        $a['count'] = (int) $a['count'] + 1;

        Session::put('_login_attempts', $a);
    }
}
