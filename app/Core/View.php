<?php
namespace App\Core;

/** Renders PHP view files, with layout support */
class View
{
    private static string $dir = '';
    private static array  $shared = [];

    public static function setDirectory(string $dir): void
    {
        self::$dir = rtrim($dir, "/\\");
    }

    /** Variables shared with every view (the signed-in user, config and so on) */
    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::capture($view, $data);

        if ($layout === null) {
            return $content;
        }

        return self::capture($layout, array_merge($data, ['content' => $content]));
    }

    public static function display(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        echo self::render($view, $data, $layout);
    }

    /** Embeds one view inside another */
    public static function partial(string $view, array $data = []): string
    {
        return self::capture($view, $data);
    }

    private static function capture(string $view, array $data): string
    {
        // Block directory traversal
        $safe = str_replace("\\", '/', $view);
        $safe = str_replace('..', '', $safe);
        $file = self::$dir . '/' . ltrim($safe, '/') . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$view} ({$file})");
        }

        extract(self::$shared, EXTR_SKIP);
        extract($data, EXTR_OVERWRITE);

        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
