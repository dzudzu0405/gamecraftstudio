<?php
namespace App\Core;

/** One-shot notifications shown after saving, deleting, errors and so on */
class Flash
{
    public static function add(string $type, string $message): void
    {
        $all = Session::get('_flash', []);
        $all[] = ['type' => $type, 'message' => $message];
        Session::put('_flash', $all);
    }

    public static function success(string $message): void { self::add('success', $message); }
    public static function error(string $message): void   { self::add('error', $message); }
    public static function info(string $message): void    { self::add('info', $message); }
    public static function warning(string $message): void { self::add('warning', $message); }

    /** Take every message and clear them from the session */
    public static function take(): array
    {
        return Session::pull('_flash', []);
    }

    /** Keeps what the user typed when validation fails */
    public static function keepOld(array $data): void
    {
        unset($data['_token'], $data['_method'], $data['password'], $data['password_confirmation']);
        Session::put('_old', $data);
    }

    public static function old(string $key, $default = '')
    {
        $old = Session::get('_old', []);
        return $old[$key] ?? $default;
    }

    public static function clearOld(): void
    {
        Session::forget('_old');
    }

    public static function putErrors(array $errors): void
    {
        Session::put('_errors', $errors);
    }

    public static function errors(): array
    {
        return Session::get('_errors', []);
    }

    public static function clearErrors(): void
    {
        Session::forget('_errors');
    }
}
