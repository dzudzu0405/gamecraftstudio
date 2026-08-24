<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
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
        $intended = Session::pull('_intended', '/');

        Flash::success('Welcome back!');
        Response::redirect(is_string($intended) ? $intended : '/');
    }

    public function showRegister(Request $request): void
    {
        $this->view('auth/register', [
            'pageTitle' => 'Create account',
            'tiers'     => Tiers::all(),
        ], 'layouts/blank');
    }

    public function register(Request $request): void
    {
        $v = new Validator($request->body);
        $v->required('name', 'your name')->max('name', 120, 'your name')
          ->required('email', 'your email address')->email('email')->max('email', 190, 'your email address')
          ->required('password', 'a password')->min('password', 8, 'your password')->max('password', 200, 'your password')
          ->matches('password_confirmation', 'password', 'the confirmation password')
          ->in('plan', Tiers::ORDER, 'plan');

        if ($v->passes() && Auth::emailExists($request->str('email'))) {
            $v->rule('email', false, 'That email address is already registered.');
        }

        if ($v->fails()) {
            $this->backWithErrors($v->errors(), $request->body, '/register');
            return;
        }

        $plan = $request->str('plan', Tiers::STARTER);
        if (!Tiers::exists($plan)) {
            $plan = Tiers::STARTER;
        }

        $userId = Auth::register(
            $request->str('name'),
            $request->str('email'),
            (string) $request->input('password'),
            $plan
        );

        \App\Core\Database::update('users', ['plan_started_at' => date('Y-m-d H:i:s')], ['id' => $userId]);

        Auth::login($userId);
        Flash::success('Account created. Time to build your first game!');
        Response::redirect('/');
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
