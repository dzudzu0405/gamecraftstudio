<?php
namespace App\Core;

class Response
{
    public static function redirect(string $path, int $status = 302): void
    {
        $target = str_starts_with($path, 'http') ? $path : Url::to($path);
        header('Location: ' . $target, true, $status);
        exit;
    }

    /** Go back to the previous page */
    public static function back(string $fallback = '/'): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref !== '' && str_contains($ref, (string) ($_SERVER['HTTP_HOST'] ?? ''))) {
            header('Location: ' . $ref, true, 302);
            exit;
        }
        self::redirect($fallback);
    }

    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function abort(int $status = 404, string $message = ''): void
    {
        http_response_code($status);
        $titles = [
            403 => 'Access denied',
            404 => 'Page not found',
            405 => 'Method not allowed',
            500 => 'Something went wrong',
        ];
        echo View::render('errors/error', [
            'status'  => $status,
            'title'   => $titles[$status] ?? 'An error occurred',
            'message' => $message,
        ], Auth::check() ? 'layouts/app' : 'layouts/blank');
        exit;
    }

    /** Sends a file to the browser as a download */
    public static function download(string $filePath, string $downloadName, string $mime = 'application/octet-stream'): void
    {
        if (!is_file($filePath)) {
            self::abort(404, 'File not found.');
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        readfile($filePath);
        exit;
    }

    /** Writes content straight out, for example a generated SVG */
    public static function raw(string $body, string $contentType, int $cacheSeconds = 0): void
    {
        header('Content-Type: ' . $contentType);
        if ($cacheSeconds > 0) {
            header('Cache-Control: public, max-age=' . $cacheSeconds);
        }
        echo $body;
        exit;
    }
}
