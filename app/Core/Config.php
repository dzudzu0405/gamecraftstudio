<?php
namespace App\Core;

/**
 * Holds the settings returned by config.php.
 *
 * The file is a plain PHP array, so there is nothing to parse. Values are read
 * with dot notation: Config::get('database.driver'). A missing config.php is
 * not an error - the installer needs the app to boot without one.
 */
class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    private static bool $loaded = false;

    /** Reads config.php. Missing or malformed files leave the settings empty. */
    public static function load(string $file): void
    {
        self::$loaded = false;
        self::$items  = [];

        if (!is_file($file)) {
            return;
        }

        $data = require $file;

        if (is_array($data)) {
            self::$items  = $data;
            self::$loaded = true;
        }
    }

    /** True once a valid config.php has been read */
    public static function loaded(): bool
    {
        return self::$loaded;
    }

    /**
     * Reads a setting. Nested values use dots: 'database.host'.
     *
     * @param mixed $default Returned when the key is not present
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** True when the key exists, even if its value is null */
    public static function has(string $key): bool
    {
        return self::get($key, '__gc_missing__') !== '__gc_missing__';
    }

    /** Overrides a setting for this request only. Nothing is written to disk. */
    public static function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $target   = &self::$items;

        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target[array_shift($segments)] = $value;
    }

    /** @return array<string, mixed> Every setting */
    public static function all(): array
    {
        return self::$items;
    }
}
