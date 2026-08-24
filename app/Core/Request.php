<?php
namespace App\Core;

/** Wraps the data of one HTTP request */
class Request
{
    public string $method;
    public string $path;
    public array  $query;
    public array  $body;
    public array  $files;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->query  = $_GET;
        $this->body   = $_POST;
        $this->files  = $_FILES;
        $this->path   = $this->resolvePath();

        // Method spoofing support: <input name="_method" value="DELETE">
        if ($this->method === 'POST' && !empty($this->body['_method'])) {
            $spoof = strtoupper((string) $this->body['_method']);
            if (in_array($spoof, ['PUT', 'PATCH', 'DELETE'], true)) {
                $this->method = $spoof;
            }
        }
    }

    private function resolvePath(): string
    {
        // The ?r= parameter wins - it is the fallback when mod_rewrite is off
        if (!empty($_GET['r'])) {
            return '/' . trim((string) $_GET['r'], '/');
        }

        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = Url::basePath();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . trim(rawurldecode($uri), '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        return is_scalar($v) ? trim((string) $v) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public function arr(string $key): array
    {
        $v = $this->input($key, []);
        return is_array($v) ? $v : [];
    }

    public function bool(string $key): bool
    {
        $v = $this->input($key);
        return in_array($v, [1, '1', true, 'true', 'on', 'yes'], true);
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $f;
    }

    public function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }
}
