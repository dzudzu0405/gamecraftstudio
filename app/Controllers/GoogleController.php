<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\GoogleAuth;
use App\Services\Mailer;
use App\Services\Tiers;

/**
 * Sign in with Google.
 *
 * HOW AN ACCOUNT IS MATCHED, in order:
 *   1. A user already carrying this Google id      -> sign them in
 *   2. A user with the same email address          -> link Google to it
 *   3. Nobody yet                                  -> create an account
 *
 * Step 2 is the one worth thinking about. It means somebody who registered
 * with a password can later press the Google button and land in the same
 * account rather than a duplicate. That is only safe because Google tells us
 * whether it has verified the address, and an unverified one is refused - it
 * would otherwise be a way to walk into an account by claiming its email.
 */
class GoogleController extends Controller
{
    /** Send the visitor off to Google */
    public function start(Request $request): void
    {
        if (!GoogleAuth::isEnabled()) {
            Response::abort(404, 'Google sign-in is not set up on this site.');
        }

        // One-time value that has to survive the round trip, to prove the
        // callback belongs to a sign-in this browser actually started
        $state = bin2hex(random_bytes(24));
        Session::put('_google_state', $state);
        Session::put('_google_state_at', time());

        Response::redirect(GoogleAuth::authUrl($state));
    }

    /** Google sends the visitor back here */
    public function callback(Request $request): void
    {
        if (!GoogleAuth::isEnabled()) {
            Response::abort(404, 'Google sign-in is not set up on this site.');
        }

        // The visitor pressed Cancel on Google's screen
        if ($request->str('error') !== '') {
            Flash::info('Google sign-in was cancelled.');
            Response::redirect('/login');
            return;
        }

        if (!$this->stateIsValid($request->str('state'))) {
            Flash::error('That sign-in attempt could not be verified. Please try again.');
            Response::redirect('/login');
            return;
        }

        $code = $request->str('code');
        if ($code === '') {
            Flash::error('Google did not send back a sign-in code. Please try again.');
            Response::redirect('/login');
            return;
        }

        try {
            $token   = GoogleAuth::exchangeCode($code);
            $profile = GoogleAuth::fetchProfile($token);
        } catch (\Throwable $e) {
            error_log('[GameCraft google] ' . $e->getMessage());

            // The detail only helps whoever runs the site, so hide it in production
            $message = Config::get('debug', false)
                ? $e->getMessage()
                : 'Google sign-in did not work. Please try again, or sign in with your password.';

            Flash::error($message);
            Response::redirect('/login');
            return;
        }

        if (!$profile['verified']) {
            Flash::error('That Google account has not verified its email address, so it cannot be used to sign in.');
            Response::redirect('/login');
            return;
        }

        $userId = $this->resolveAccount($profile);

        if ($userId === null) {
            Flash::error('That account has been disabled. Please contact the site owner.');
            Response::redirect('/login');
            return;
        }

        Auth::login($userId);

        $intended = Session::pull('_intended', '/');
        Response::redirect(is_string($intended) ? $intended : '/');
    }

    // -----------------------------------------------------------------

    /** The state has to match, and not be older than ten minutes */
    private function stateIsValid(string $state): bool
    {
        $expected = Session::pull('_google_state');
        $issuedAt = (int) Session::pull('_google_state_at', 0);

        if (!is_string($expected) || $expected === '' || $state === '') {
            return false;
        }

        if (time() - $issuedAt > 600) {
            return false;
        }

        return hash_equals($expected, $state);
    }

    /**
     * Finds, links or creates the account.
     * Returns null when the matched account has been deactivated.
     */
    private function resolveAccount(array $profile): ?int
    {
        $now = date('Y-m-d H:i:s');

        // 1. Already linked
        $user = Database::first('SELECT * FROM users WHERE google_id = ? LIMIT 1', [$profile['id']]);

        if ($user) {
            if ((int) $user['is_active'] !== 1) {
                return null;
            }
            $this->refreshAvatar((int) $user['id'], $profile, $now);
            return (int) $user['id'];
        }

        // 2. Same email, registered with a password - link the two
        $user = Database::first('SELECT * FROM users WHERE email = ? LIMIT 1', [$profile['email']]);

        if ($user) {
            if ((int) $user['is_active'] !== 1) {
                return null;
            }

            Database::update('users', [
                'google_id'  => $profile['id'],
                'avatar_url' => $profile['picture'] ?: ($user['avatar_url'] ?? null),
                'updated_at' => $now,
            ], ['id' => (int) $user['id']]);

            Flash::success('Your Google account is now linked. You can sign in either way from now on.');
            return (int) $user['id'];
        }

        // 3. Brand new
        return $this->createAccount($profile, $now);
    }

    private function createAccount(array $profile, string $now): int
    {
        /*
         * The password column cannot be empty, so a long random value goes in.
         * Nobody knows it, which is the point - if this person later wants to
         * sign in with a password they use "Forgot password" to set one.
         */
        $userId = Database::insert('users', [
            'name'            => mb_substr($profile['name'], 0, 120),
            'email'           => $profile['email'],
            'password_hash'   => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            'plan'            => Tiers::STARTER,
            'role'            => 'creator',
            'locale'          => 'en',
            'is_active'       => 1,
            'google_id'       => $profile['id'],
            'avatar_url'      => $profile['picture'] ?: null,
            'plan_started_at' => $now,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $this->sendWelcome($profile['email'], $profile['name']);

        Flash::success('Welcome to GameCraft Studio! Your account is ready.');
        return $userId;
    }

    /** Keep the profile picture current, but do not write on every sign-in for nothing */
    private function refreshAvatar(int $userId, array $profile, string $now): void
    {
        if ($profile['picture'] === '') {
            return;
        }

        $current = Database::value('SELECT avatar_url FROM users WHERE id = ?', [$userId]);

        if ((string) $current !== $profile['picture']) {
            Database::update('users', [
                'avatar_url' => $profile['picture'],
                'updated_at' => $now,
            ], ['id' => $userId]);
        }
    }

    /** A failed welcome email must never stop somebody signing in */
    private function sendWelcome(string $email, string $name): void
    {
        if (!Mailer::isConfigured()) {
            return;
        }

        $plan = Tiers::get(Tiers::STARTER);

        Mailer::sendTemplate($email, $name, 'welcome', [
            'subject'    => 'Welcome to GameCraft Studio',
            'firstName'  => trim(explode(' ', trim($name))[0] ?? '') ?: 'there',
            'planName'   => $plan['name'],
            'maps'       => $plan['maps_total'],
            'characters' => $plan['character_sets'],
        ]);
    }
}
