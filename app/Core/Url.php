<?php
namespace App\Core;

/** Builds paths and URLs, detecting automatically when the app sits in a sub-folder */
class Url
{
    private static ?string $base = null;

    /** The base path, for example '' or '/gamecraft' */
    public static function basePath(): string
    {
        if (self::$base !== null) {
            return self::$base;
        }

        // 1. An explicit value in config.php always wins
        $configured = (string) Config::get('base_url', '');
        if ($configured !== '') {
            $path = parse_url($configured, PHP_URL_PATH) ?: '';
            return self::$base = rtrim($path, '/');
        }

        // 2. SCRIPT_NAME is only trustworthy when it points at an actual .php
        //    file, which is always the case on cPanel's Apache (/gamecraft/index.php).
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script !== '' && str_ends_with($script, '.php')) {
            $dir = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($script)), '/');
            return self::$base = ($dir === '' || $dir === '.') ? '' : $dir;
        }

        // 3. PHP's built-in web server sets SCRIPT_NAME to the requested path
        //    itself, so work the base path back from where the app lives.
        $docRoot = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $appRoot = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', defined('GC_ROOT') ? GC_ROOT : dirname(__DIR__, 2)), '/');

        if ($docRoot !== '' && $appRoot !== $docRoot && str_starts_with($appRoot, $docRoot . '/')) {
            return self::$base = rtrim(substr($appRoot, strlen($docRoot)), '/');
        }

        return self::$base = '';
    }

    /** An absolute path from the web root: to('/projects') => '/gamecraft/projects' */
    public static function to(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        $base = self::basePath();
        $full = $base . ($path === '/' ? '/' : rtrim($path, '/'));
        return $full === '' ? '/' : $full;
    }

    /** A full URL including the host name */
    public static function full(string $path = '/'): string
    {
        $configured = (string) Config::get('base_url', '');
        if ($configured !== '') {
            return rtrim($configured, '/') . '/' . ltrim($path, '/');
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . self::to($path);
    }

    /** Path to a static file under /assets */
    public static function asset(string $path): string
    {
        return self::to('assets/' . ltrim($path, '/'));
    }

    /** Path to a user-uploaded image */
    public static function upload(string $path): string
    {
        return self::to('uploads/' . ltrim($path, '/'));
    }

    /** Adds query parameters to the current URL (used by filters and paging) */
    public static function withQuery(array $params, ?string $path = null): string
    {
        $current = $_GET;
        unset($current['r']);
        $merged = array_merge($current, $params);
        $merged = array_filter($merged, fn($v) => $v !== null && $v !== '');
        $target = $path ?? self::currentPath();
        $qs = http_build_query($merged);
        return self::to($target) . ($qs ? '?' . $qs : '');
    }

    public static function currentPath(): string
    {
        if (!empty($_GET['r'])) {
            return '/' . trim((string) $_GET['r'], '/');
        }
        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = self::basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        return '/' . trim($uri, '/');
    }
}
