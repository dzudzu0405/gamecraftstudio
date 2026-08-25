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
use App\Core\Url;
use App\Core\Validator;
use App\Services\Mailer;

/**
 * Forgotten password, by email.
 *
 * HOW THE TOKEN IS KEPT SAFE
 * ---------------------------------------------------------------------------
 * The link contains a 64-character random token. What lands in the database is
 * only its SHA-256 hash, so somebody who reads the users' rows still cannot
 * build a working reset link. Each token expires after an hour, works once,
 * and any older tokens for that account are cancelled the moment a new one is
 * requested.
 *
 * WHY THE PAGE NEVER SAYS "NO SUCH ACCOUNT"
 * ---------------------------------------------------------------------------
 * Whatever address is submitted, the reply is identical. Telling a stranger
 * which addresses have accounts is a free list of targets, so the wording is
 * always "if that address has an account, the email is on its way".
 */
class PasswordController extends Controller
{
    /** A person can only ask for so many links before we make them wait */
    private const MAX_REQUESTS_PER_HOUR = 5;

    // -----------------------------------------------------------------
    //  Step 1 - ask for a link
    // -----------------------------------------------------------------

    public function showForgot(Request $request): void
    {
        $this->view('auth/forgot', [
            'pageTitle'    => 'Reset your password',
            'mailWorking'  => Mailer::isConfigured(),
        ], 'layouts/blank');
    }

    public function sendLink(Request $request): void
    {
        $email = strtolower(trim($request->str('email')));

        $v = new Validator($request->body);
        $v->required('email', 'your email address')->email('email');

        if ($v->fails()) {
            $this->backWithErrors($v->errors(), ['email' => $email], '/forgot');
            return;
        }

        if (!Mailer::isConfigured()) {
            Flash::error('Email has not been set up on this site yet, so reset links cannot be sent. Please contact the site owner.');
            $this->back('/forgot');
            return;
        }

        if ($this->tooManyRequests()) {
            Flash::error('That is a lot of reset requests. Please wait an hour before trying again.');
            $this->back('/forgot');
            return;
        }

        $this->recordRequest();

        $user = Database::first(
            'SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
            [$email]
        );

        // Only really send when the account exists - but say the same thing either way
        if ($user) {
            $this->issueToken($user, $request->ip());
        }

        Flash::success('If that address has an account, a reset link is on its way. It expires in '
            . $this->expiryMinutes() . ' minutes.');

        Response::redirect('/login');
    }

    /** Creates the token, stores its hash, emails the link */
    private function issueToken(array $user, string $ip): void
    {
        $userId = (int) $user['id'];

        // Any link sent earlier is now void
        Database::run(
            'UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL',
            [date('Y-m-d H:i:s'), $userId]
        );

        $token   = bin2hex(random_bytes(32));
        $minutes = $this->expiryMinutes();

        Database::insert('password_resets', [
            'user_id'    => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + $minutes * 60),
            'used_at'    => null,
            'request_ip' => mb_substr($ip, 0, 45),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $sent = Mailer::sendTemplate(
            (string) $user['email'],
            (string) $user['name'],
            'password-reset',
            [
                'subject'          => 'Reset your GameCraft Studio password',
                'firstName'        => $this->firstName((string) $user['name']),
                'resetUrl'         => Url::full('/reset/' . $token),
                'expiresInMinutes' => $minutes,
            ]
        );

        if (!$sent) {
            error_log('[GameCraft] Could not send a reset email to ' . $user['email'] . ': ' . (Mailer::lastError() ?? 'unknown reason'));
        }
    }

    // -----------------------------------------------------------------
    //  Step 2 - choose a new password
    // -----------------------------------------------------------------

    public function showReset(Request $request, array $params): void
    {
        $token = (string) ($params['token'] ?? '');
        $row   = $this->findValidToken($token);

        if (!$row) {
            Flash::error('That reset link has expired or has already been used. Please request a new one.');
            Response::redirect('/forgot');
            return;
        }

        $this->view('auth/reset', [
            'pageTitle' => 'Choose a new password',
            'token'     => $token,
        ], 'layouts/blank');
    }

    public function saveReset(Request $request, array $params): void
    {
        $token = (string) ($params['token'] ?? '');
        $row   = $this->findValidToken($token);

        if (!$row) {
            Flash::error('That reset link has expired or has already been used. Please request a new one.');
            Response::redirect('/forgot');
            return;
        }

        $v = new Validator($request->body);
        $v->required('password', 'a new password')->min('password', 8, 'your new password')->max('password', 200, 'your new password')
          ->matches('password_confirmation', 'password', 'the confirmation password');

        if ($v->fails()) {
            Flash::error($v->firstError() ?? 'Please check the details you entered.');
            $this->backWithErrors($v->errors(), [], '/reset/' . $token);
            return;
        }

        $userId   = (int) $row['user_id'];
        $password = (string) $request->input('password');

        Database::transaction(function () use ($userId, $password, $row) {
            Database::update('users', [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'updated_at'    => date('Y-m-d H:i:s'),
            ], ['id' => $userId]);

            // Burn this token, and any other outstanding one for the account
            Database::run(
                'UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL',
                [date('Y-m-d H:i:s'), $userId]
            );
        });

        // A fresh session id, so anyone holding the old one is thrown out
        Session::regenerate();
        Auth::login($userId);

        Flash::success('Your password has been changed. You are signed in.');
        Response::redirect('/');
    }

    // -----------------------------------------------------------------
    //  Helpers
    // -----------------------------------------------------------------

    /** Returns the reset row if the token is real, unused and still in date */
    private function findValidToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        return Database::first(
            'SELECT * FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?
             LIMIT 1',
            [hash('sha256', $token), date('Y-m-d H:i:s')]
        );
    }

    private function expiryMinutes(): int
    {
        $minutes = (int) Config::get('password_reset_minutes', 60);
        return max(5, min(1440, $minutes));
    }

    private function firstName(string $name): string
    {
        $first = trim(explode(' ', trim($name))[0] ?? '');
        return $first !== '' ? $first : 'there';
    }

    private function tooManyRequests(): bool
    {
        $log = Session::get('_reset_requests', []);
        $log = array_filter((array) $log, fn($t) => $t > time() - 3600);
        return count($log) >= self::MAX_REQUESTS_PER_HOUR;
    }

    private function recordRequest(): void
    {
        $log = array_filter((array) Session::get('_reset_requests', []), fn($t) => $t > time() - 3600);
        $log[] = time();
        Session::put('_reset_requests', array_values($log));
    }
}
