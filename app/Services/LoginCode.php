<?php
namespace App\Services;

use App\Core\Database;

/**
 * The six-digit code emailed to prove an address belongs to whoever typed it.
 *
 * WHEN IT IS ASKED FOR
 * ---------------------------------------------------------------------------
 * Once, on the first password sign-in of a new account. After that the address
 * is marked verified and the person signs in with their password as normal.
 * Signing in with Google never asks for one - Google has already verified the
 * address, and says so, which is the whole reason the app trusts it.
 *
 * WHY IT MATTERS HERE
 * ---------------------------------------------------------------------------
 * The delivery link raises whichever account redeems it, so an account has to
 * be an address somebody actually holds. Without this, a buyer could register
 * as anyone and the plan history would point at an address that does not exist.
 *
 * Only a hash of the code is stored, so a leaked database still cannot be used
 * to walk into an account. The code is short, so the brakes matter: it lasts
 * fifteen minutes, allows six wrong guesses, and five sends an hour.
 */
class LoginCode
{
    public const PURPOSE_VERIFY = 'verify';

    /** How long a code stays good, in minutes */
    private const LIFETIME_MINUTES = 15;

    /** Wrong guesses allowed against one code before it is burned */
    private const MAX_ATTEMPTS = 6;

    /** Codes that may be sent to one account within the hour */
    private const MAX_PER_HOUR = 5;

    /**
     * Is the verification step switched on at all?
     *
     * It depends entirely on email working. If config.php has no mail set up,
     * asking for a code would lock everybody out of their own site - including
     * whoever is trying to configure the mail - so the step is skipped instead.
     */
    public static function isAvailable(): bool
    {
        return Mailer::isConfigured();
    }

    /** Does this account still have to prove its address? */
    public static function isPending(?array $user): bool
    {
        if (!$user || !self::isAvailable()) {
            return false;
        }

        /*
         * No such column means the files have been uploaded but install/upgrade.php
         * has not been run yet. Treating that as "not confirmed" would send every
         * account on the site to a code screen backed by a table that does not
         * exist, so the step stays off until the upgrade has actually happened.
         */
        if (!array_key_exists('email_verified_at', $user)) {
            return false;
        }

        return empty($user['email_verified_at']);
    }

    /**
     * Generates a code, stores its hash and emails it.
     *
     * @return array{ok: bool, message: string}
     */
    public static function issue(array $user, ?string $ip = null): array
    {
        $userId = (int) $user['id'];

        if (!self::isAvailable()) {
            return ['ok' => false, 'message' => 'Email is not set up on this site, so no code can be sent.'];
        }

        if (self::sentTooMany($userId)) {
            return ['ok' => false, 'message' => 'That is a lot of codes in one hour. Please wait a while before asking for another.'];
        }

        // Any earlier code stops working the moment a new one goes out, so two
        // emails in the inbox can never both be valid
        Database::run(
            'UPDATE login_codes SET used_at = ? WHERE user_id = ? AND used_at IS NULL',
            [date('Y-m-d H:i:s'), $userId]
        );

        $code = self::randomCode();

        Database::insert('login_codes', [
            'user_id'    => $userId,
            'code_hash'  => self::hash($code),
            'purpose'    => self::PURPOSE_VERIFY,
            'expires_at' => date('Y-m-d H:i:s', time() + self::LIFETIME_MINUTES * 60),
            'attempts'   => 0,
            'request_ip' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $sent = Mailer::sendTemplate((string) $user['email'], (string) $user['name'], 'login-code', [
            'subject'   => $code . ' is your GameCraft Studio sign-in code',
            'firstName' => self::firstName((string) $user['name']),
            'code'      => $code,
            'minutes'   => self::LIFETIME_MINUTES,
        ]);

        if (!$sent) {
            error_log('[GameCraft] Sign-in code to ' . $user['email'] . ' failed: ' . (Mailer::lastError() ?? 'unknown reason'));
            return ['ok' => false, 'message' => 'The code could not be emailed just now. Please try again in a moment.'];
        }

        return ['ok' => true, 'message' => 'We have emailed a six-digit code to ' . $user['email'] . '.'];
    }

    /**
     * Checks a typed code and, when it matches, marks the address verified.
     *
     * @return array{ok: bool, message: string}
     */
    public static function check(int $userId, string $typed): array
    {
        $typed = preg_replace('/\D+/', '', $typed) ?? '';

        if (strlen($typed) !== 6) {
            return ['ok' => false, 'message' => 'Please enter the six-digit code from the email.'];
        }

        $row = Database::first(
            'SELECT * FROM login_codes
              WHERE user_id = ? AND used_at IS NULL AND expires_at > ?
           ORDER BY id DESC LIMIT 1',
            [$userId, date('Y-m-d H:i:s')]
        );

        if (!$row) {
            return ['ok' => false, 'message' => 'That code has expired. Ask for a new one below.'];
        }

        if ((int) $row['attempts'] >= self::MAX_ATTEMPTS) {
            Database::update('login_codes', ['used_at' => date('Y-m-d H:i:s')], ['id' => (int) $row['id']]);
            return ['ok' => false, 'message' => 'Too many wrong codes. Ask for a new one below.'];
        }

        if (!hash_equals((string) $row['code_hash'], self::hash($typed))) {
            Database::run('UPDATE login_codes SET attempts = attempts + 1 WHERE id = ?', [(int) $row['id']]);

            $left = self::MAX_ATTEMPTS - ((int) $row['attempts'] + 1);

            return [
                'ok'      => false,
                'message' => $left > 0
                    ? 'That code is not right. ' . $left . ' ' . ($left === 1 ? 'try' : 'tries') . ' left.'
                    : 'That code is not right, and that was the last try. Ask for a new one below.',
            ];
        }

        $now = date('Y-m-d H:i:s');

        Database::update('login_codes', ['used_at' => $now], ['id' => (int) $row['id']]);
        self::markVerified($userId);

        return ['ok' => true, 'message' => 'Thanks - your email address is confirmed.'];
    }

    /** Stamps the account as verified. Also used by Google sign-in and the admin screens. */
    public static function markVerified(int $userId): void
    {
        $now = date('Y-m-d H:i:s');

        Database::update('users', [
            'email_verified_at' => $now,
            'updated_at'        => $now,
        ], ['id' => $userId]);
    }

    // -----------------------------------------------------------------

    private static function randomCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private static function hash(string $code): string
    {
        return hash('sha256', $code);
    }

    private static function sentTooMany(int $userId): bool
    {
        $since = date('Y-m-d H:i:s', time() - 3600);

        return Database::count(
            'SELECT COUNT(*) FROM login_codes WHERE user_id = ? AND created_at > ?',
            [$userId, $since]
        ) >= self::MAX_PER_HOUR;
    }

    private static function firstName(string $name): string
    {
        return trim(explode(' ', trim($name))[0] ?? '') ?: 'there';
    }
}
