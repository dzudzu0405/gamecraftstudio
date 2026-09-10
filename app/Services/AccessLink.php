<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Session;
use App\Core\Url;

/**
 * WarriorPlus delivery links.
 *
 * After paying, a buyer is handed a link of the form /access/<code>. Opening
 * it while signed in raises the account to that link's plan; opening it as a
 * guest parks the code in the session and picks it up again the moment they
 * finish signing in, so nobody has to click the link twice.
 *
 * WHAT THE LINK PROVES
 * ---------------------------------------------------------------------------
 * On its own: only that whoever opened it was given it. There is no check
 * against WarriorPlus, which is a deliberate choice - it keeps fulfilment to
 * one field in the WarriorPlus product setup and works the moment a sale goes
 * through. The trade is that a link posted somewhere public would hand the
 * plan to anyone who found it, so three things guard against that:
 *
 *   - the code is 24 random hex characters, so it cannot be guessed
 *   - max_uses and expires_at cap a link that has got out
 *   - every redemption is logged with an IP, and rotating the code in the
 *     admin screens kills the old link without touching redeemed accounts
 *
 * A link never lowers a plan (see redeem), so a buyer who upgrades and then
 * re-opens their old Starter link keeps what they paid for.
 */
class AccessLink
{
    /** Where a code waits while a guest signs in */
    private const PENDING_KEY = '_access_code';

    /** A fresh, unguessable link code */
    public static function generateCode(): string
    {
        return bin2hex(random_bytes(12));
    }

    public static function find(string $code): ?array
    {
        $code = strtolower(trim($code));

        if ($code === '' || !preg_match('/^[a-f0-9]{8,64}$/', $code)) {
            return null;
        }

        return Database::first('SELECT * FROM access_links WHERE code = ? LIMIT 1', [$code]);
    }

    public static function findById(int $id): ?array
    {
        return Database::first('SELECT * FROM access_links WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::all('SELECT * FROM access_links ORDER BY is_active DESC, id DESC');
    }

    /** The full address to paste into the WarriorPlus product setup */
    public static function url(array $link): string
    {
        return Url::full('/access/' . $link['code']);
    }

    /**
     * Why this link cannot be used, or null when it can.
     * The wording is shown to the buyer, so it says what to do next.
     */
    public static function problem(array $link): ?string
    {
        if ((int) $link['is_active'] !== 1) {
            return 'This access link has been switched off. Please contact support with your WarriorPlus receipt.';
        }

        $expires = $link['expires_at'] ?? null;
        $expiresAt = $expires ? strtotime((string) $expires) : false;

        if ($expiresAt !== false && $expiresAt < time()) {
            return 'This access link has expired. Please contact support with your WarriorPlus receipt.';
        }

        $max = (int) $link['max_uses'];

        if ($max > 0 && (int) $link['uses'] >= $max) {
            return 'This access link has already been used the maximum number of times. Please contact support with your WarriorPlus receipt.';
        }

        return null;
    }

    /**
     * Hands the plan to a signed-in account.
     *
     * @return array{ok: bool, type: string, message: string}
     */
    public static function redeem(array $link, array $user, ?string $ip = null): array
    {
        $problem = self::problem($link);

        if ($problem !== null) {
            return ['ok' => false, 'type' => 'error', 'message' => $problem];
        }

        $plan    = (string) $link['plan'];
        $current = (string) ($user['plan'] ?? Tiers::STARTER);

        // Never move somebody down. Re-opening an old Starter link after buying
        // Publisher has to be harmless, or fulfilment becomes a way to lose a plan.
        if (Tiers::rank($current) >= Tiers::rank($plan)) {
            return [
                'ok'      => true,
                'type'    => 'info',
                'message' => 'Your account is already on the ' . Tiers::name($current)
                           . ' plan, which includes everything in ' . Tiers::name($plan) . '. Nothing to do.',
            ];
        }

        $changed = Entitlements::setPlan(
            (int) $user['id'],
            $plan,
            Entitlements::SOURCE_LINK,
            null,
            (int) $link['id'],
            (string) $link['label'],
            $ip
        );

        if (!$changed) {
            return ['ok' => false, 'type' => 'error', 'message' => 'That plan could not be applied. Please contact support.'];
        }

        Database::run(
            'UPDATE access_links SET uses = uses + 1, updated_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), (int) $link['id']]
        );

        return [
            'ok'      => true,
            'type'    => 'success',
            'message' => 'Unlocked! Your account is now on the ' . Tiers::name($plan) . ' plan.',
        ];
    }

    // -----------------------------------------------------------------
    //  Holding a code while the buyer signs in
    // -----------------------------------------------------------------

    public static function putPending(string $code): void
    {
        Session::put(self::PENDING_KEY, strtolower($code));
    }

    /** Reads and clears the parked code */
    public static function takePending(): ?string
    {
        $code = Session::pull(self::PENDING_KEY);
        return is_string($code) && $code !== '' ? $code : null;
    }

    public static function peekPending(): ?string
    {
        $code = Session::get(self::PENDING_KEY);
        return is_string($code) && $code !== '' ? $code : null;
    }

    public static function forgetPending(): void
    {
        Session::forget(self::PENDING_KEY);
    }

    /**
     * Applies whatever link was parked before signing in.
     * Flashes the outcome and returns true when a plan actually moved.
     */
    public static function applyPending(array $user, ?string $ip = null): bool
    {
        $code = self::takePending();

        if ($code === null) {
            return false;
        }

        $link = self::find($code);

        if (!$link) {
            Flash::error('That access link is not one of ours. Please check the address in your WarriorPlus receipt.');
            return false;
        }

        $result = self::redeem($link, $user, $ip);

        if ($result['type'] === 'success') {
            Flash::success($result['message']);
        } elseif ($result['type'] === 'info') {
            Flash::info($result['message']);
        } else {
            Flash::error($result['message']);
        }

        return $result['type'] === 'success';
    }

    /** How many accounts each link has raised, for the admin list */
    public static function redemptionCounts(): array
    {
        $rows = Database::all(
            'SELECT link_id, COUNT(*) AS n FROM plan_events WHERE link_id IS NOT NULL GROUP BY link_id'
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['link_id']] = (int) $row['n'];
        }
        return $out;
    }
}
