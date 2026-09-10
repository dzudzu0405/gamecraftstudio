<?php
namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * Which plan an account is on, and how it got there.
 *
 * Plans are bought on WarriorPlus, so nothing the buyer does inside the app
 * moves their own plan. It changes in exactly two ways:
 *
 *   1. redeeming a delivery link  -> AccessLink::redeem()
 *   2. an administrator moving it -> the admin screens
 *
 * Both come through setPlan(), which writes a row to plan_events. That log is
 * the only record of who granted what, and refunds are worked out from it by
 * hand, so nothing here may change a plan without going through setPlan().
 */
class Entitlements
{
    public const SOURCE_REGISTER = 'register';
    public const SOURCE_GOOGLE   = 'google';
    public const SOURCE_LINK     = 'link';
    public const SOURCE_ADMIN    = 'admin';

    /**
     * Moves an account onto a plan and records why.
     *
     * Returns false when the plan is unknown or the account is already on it -
     * writing an event saying nothing changed would only make the log harder
     * to read.
     */
    public static function setPlan(
        int $userId,
        string $plan,
        string $source,
        ?int $actorId = null,
        ?int $linkId = null,
        ?string $note = null,
        ?string $ip = null
    ): bool {
        if (!Tiers::exists($plan)) {
            return false;
        }

        $current = (string) Database::value('SELECT plan FROM users WHERE id = ?', [$userId]);

        if ($current === $plan) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        Database::update('users', [
            'plan'            => $plan,
            'plan_started_at' => $now,
            'updated_at'      => $now,
        ], ['id' => $userId]);

        self::log($userId, $current, $plan, $source, $actorId, $linkId, $note, $ip);

        // The signed-in user's own row is cached for the request
        if (Auth::id() === $userId) {
            Auth::refresh();
        }

        return true;
    }

    /**
     * Writes one row to the plan history.
     *
     * A failure here is logged and swallowed on purpose. The table arrives with
     * install/upgrade.php, and somebody who uploads the new files and forgets to
     * run it would otherwise find registration itself broken - a missing audit
     * row is a far smaller problem than a buyer who cannot create an account.
     * The empty log in the admin screens is the visible symptom.
     */
    public static function log(
        int $userId,
        ?string $from,
        string $to,
        string $source,
        ?int $actorId = null,
        ?int $linkId = null,
        ?string $note = null,
        ?string $ip = null
    ): void {
        try {
            Database::insert('plan_events', [
                'user_id'    => $userId,
                'plan_from'  => ($from !== null && $from !== '') ? $from : null,
                'plan_to'    => $to,
                'source'     => $source,
                'link_id'    => $linkId,
                'actor_id'   => $actorId,
                'note'       => $note !== null ? mb_substr($note, 0, 255) : null,
                'ip'         => $ip,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('[GameCraft] Could not record a plan change (run install/upgrade.php): ' . $e->getMessage());
        }
    }

    /** The plan history for one account, newest first */
    public static function historyFor(int $userId, int $limit = 30): array
    {
        return Database::all(
            'SELECT e.*, l.label AS link_label, a.name AS actor_name
               FROM plan_events e
          LEFT JOIN access_links l ON l.id = e.link_id
          LEFT JOIN users a        ON a.id = e.actor_id
              WHERE e.user_id = ?
           ORDER BY e.created_at DESC, e.id DESC
              LIMIT ' . max(1, $limit),
            [$userId]
        );
    }

    /** How a plan change reads in the activity log */
    public static function sourceLabel(string $source): string
    {
        return [
            self::SOURCE_REGISTER => 'Signed up',
            self::SOURCE_GOOGLE   => 'Google sign-in',
            self::SOURCE_LINK     => 'Delivery link',
            self::SOURCE_ADMIN    => 'Administrator',
        ][$source] ?? ucfirst($source);
    }
}
