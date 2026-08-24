<?php
namespace App\Core;

/** Sign-in, registration and permissions */
class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;

    public static function attempt(string $email, string $password): bool
    {
        $row = Database::first('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => strtolower($email)]);

        if (!$row || !password_verify($password, (string) $row['password_hash'])) {
            return false;
        }

        if ((int) $row['is_active'] !== 1) {
            return false;
        }

        // Upgrade the hash if PHP's default algorithm has moved on
        if (password_needs_rehash((string) $row['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], ['id' => $row['id']]);
        }

        self::login((int) $row['id']);
        return true;
    }

    public static function login(int $userId): void
    {
        Session::regenerate();
        Session::put('user_id', $userId);
        self::$user = null;
        self::$resolved = false;
        Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], ['id' => $userId]);
    }

    public static function logout(): void
    {
        self::$user = null;
        self::$resolved = false;
        Session::destroy();
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return is_numeric($id) ? (int) $id : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = self::id();
        if ($id === null) {
            return self::$user = null;
        }

        $row = Database::first('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
        if (!$row || (int) $row['is_active'] !== 1) {
            Session::forget('user_id');
            return self::$user = null;
        }

        return self::$user = $row;
    }

    /** Reloads the user record after an update */
    public static function refresh(): void
    {
        self::$user = null;
        self::$resolved = false;
    }

    /** Current plan: starter | pro | publisher */
    public static function plan(): string
    {
        $u = self::user();
        return $u['plan'] ?? 'starter';
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return ($u['role'] ?? 'creator') === 'admin';
    }

    public static function register(string $name, string $email, string $password, string $plan = 'starter'): int
    {
        return Database::insert('users', [
            'name'          => $name,
            'email'         => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'plan'          => $plan,
            'role'          => 'creator',
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public static function emailExists(string $email): bool
    {
        return Database::count('SELECT COUNT(*) FROM users WHERE email = :e', ['e' => strtolower($email)]) > 0;
    }

    /** Middleware: requires a signed-in user */
    public static function requireLogin(Request $request): bool
    {
        if (self::check()) {
            return true;
        }
        Session::put('_intended', $request->path);
        Flash::error('Please sign in to continue.');
        Response::redirect('/login');
        return false;
    }

    /** Middleware: guests only (the sign-in and registration pages) */
    public static function requireGuest(Request $request): bool
    {
        if (self::guest()) {
            return true;
        }
        Response::redirect('/');
        return false;
    }

    /** Middleware: administrators only */
    public static function requireAdmin(Request $request): bool
    {
        if (!self::check()) {
            return self::requireLogin($request);
        }
        if (!self::isAdmin()) {
            Response::abort(403, 'You do not have permission to view this.');
            return false;
        }
        return true;
    }
}
