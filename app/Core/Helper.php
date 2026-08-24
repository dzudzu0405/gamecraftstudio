<?php
namespace App\Core;

/** Small helpers used throughout the views */
class Helper
{
    /** Escapes HTML - ALWAYS use this when printing anything a user typed */
    public static function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Escapes a value for embedding in JavaScript or a data- attribute */
    public static function json($value): string
    {
        return htmlspecialchars(
            json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /** "2 hours ago", "yesterday" and so on */
    public static function timeAgo(?string $datetime): string
    {
        if (!$datetime) {
            return '';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }

        $diff = time() - $ts;
        if ($diff < 60)       return 'just now';
        if ($diff < 120)      return 'a minute ago';
        if ($diff < 3600)     return floor($diff / 60) . ' minutes ago';
        if ($diff < 7200)     return 'an hour ago';
        if ($diff < 86400)    return floor($diff / 3600) . ' hours ago';
        if ($diff < 172800)   return 'yesterday';
        if ($diff < 2592000)  return floor($diff / 86400) . ' days ago';
        if ($diff < 5184000)  return 'a month ago';
        if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
        if ($diff < 63072000) return 'a year ago';
        return floor($diff / 31536000) . ' years ago';
    }

    public static function date(?string $datetime, string $format = 'j M Y, H:i'): string
    {
        if (!$datetime) {
            return '';
        }
        $ts = strtotime($datetime);
        return $ts === false ? '' : date($format, $ts);
    }

    /** 1700 -> "$17.00" */
    public static function money(int $cents, string $symbol = '$'): string
    {
        return $symbol . number_format($cents / 100, 2);
    }

    /** Initials from a name, used for the avatar circle */
    public static function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }
        $parts = preg_split('/\s+/u', $name) ?: [];
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }
        $last = end($parts);
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($last, 0, 1));
    }

    /** Builds a URL-friendly slug from a title */
    public static function slug(string $text, int $maxLength = 80): string
    {
        // Fold accented Latin and Vietnamese characters down to plain ASCII
        $map = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậäåā', 'e' => 'éèẻẽẹêếềểễệëē',
            'i' => 'íìỉĩịïî',              'o' => 'óòỏõọôốồổỗộơớờởỡợöø',
            'u' => 'úùủũụưứừửữựüû',        'y' => 'ýỳỷỹỵÿ',
            'd' => 'đð',                   'n' => 'ñ',
            'c' => 'ç',                    's' => 'ß',
        ];

        $text = mb_strtolower(trim($text), 'UTF-8');
        foreach ($map as $ascii => $accented) {
            $chars = preg_split('//u', $accented, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $text = str_replace($chars, $ascii, $text);
        }

        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
        $text = trim($text, '-');
        $text = mb_substr($text, 0, $maxLength);

        return $text !== '' ? $text : 'game-' . substr(md5((string) microtime(true)), 0, 6);
    }

    /** Shortens long text */
    public static function truncate(?string $text, int $length = 120, string $suffix = '...'): string
    {
        $text = trim((string) $text);
        return mb_strlen($text) <= $length ? $text : mb_substr($text, 0, $length) . $suffix;
    }

    /** Status badge for a project (FR-10) */
    public static function statusBadge(string $status): array
    {
        return [
            'draft'       => ['label' => 'Draft',           'class' => 'badge--draft'],
            'in_progress' => ['label' => 'In progress',     'class' => 'badge--progress'],
            'ready'       => ['label' => 'Ready to export', 'class' => 'badge--ready'],
            'published'   => ['label' => 'Published',       'class' => 'badge--published'],
        ][$status] ?? ['label' => ucfirst($status), 'class' => 'badge--draft'];
    }

    public static function statusOptions(): array
    {
        return [
            'draft'       => 'Draft',
            'in_progress' => 'In progress',
            'ready'       => 'Ready to export',
            'published'   => 'Published',
        ];
    }

    /** Marks the navigation item that is currently open */
    public static function isActive(string $currentPath, string $navPath): bool
    {
        if ($navPath === '/') {
            return $currentPath === '/';
        }
        return $currentPath === $navPath || str_starts_with($currentPath, $navPath . '/');
    }

    /** Reads a value out of JSON stored in a text column */
    public static function decode($json, array $default = []): array
    {
        if (is_array($json)) {
            return $json;
        }
        if (!is_string($json) || $json === '') {
            return $default;
        }
        $out = json_decode($json, true);
        return is_array($out) ? $out : $default;
    }

    /** Player count -> "2-4 players" */
    public static function playerRange(?int $min, ?int $max): string
    {
        $min = (int) ($min ?: 2);
        $max = (int) ($max ?: 4);
        if ($min === $max) {
            return $min . ' ' . ($min === 1 ? 'player' : 'players');
        }
        return $min . '-' . $max . ' players';
    }

    /** Age range -> "Ages 6-9" */
    public static function ageRange(?int $min, ?int $max): string
    {
        $min = (int) ($min ?: 6);
        $max = (int) ($max ?: 9);
        return 'Ages ' . $min . '-' . $max;
    }
}
