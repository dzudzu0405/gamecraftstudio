<?php
namespace App\Services;

/**
 * The language a printed game is written in.
 *
 * A buyer in Lyon prints cards their child can read, so the printed pages -
 * questions, rules, story, every label on a sheet - come out in one of four
 * languages. The Studio itself stays in English: the buyer meets it once while
 * making the game, and the child meets the printed pages every time they play.
 *
 * Strings live in app/lang/<code>.php, one nested array per file, addressed by
 * dotted key. Anything missing falls back to English rather than showing a key,
 * so a half-finished translation still prints a usable game.
 */
class Lang
{
    public const DEFAULT = 'en';

    /** What the picker offers, in the language of the person choosing it */
    public const LOCALES = [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
    ];

    /** @var array<string, array> loaded language files, by code */
    private static array $loaded = [];

    /** Anything unknown - an empty column, an old project - prints English */
    public static function normalize(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        return isset(self::LOCALES[$locale]) ? $locale : self::DEFAULT;
    }

    /** The language one project prints in */
    public static function of(array $project): string
    {
        return self::normalize($project['language'] ?? null);
    }

    /** Is this a language we print? */
    public static function supported(?string $locale): bool
    {
        return isset(self::LOCALES[strtolower(trim((string) $locale))]);
    }

    public static function name(?string $locale): string
    {
        return self::LOCALES[self::normalize($locale)];
    }

    /**
     * One line of text.
     *
     * $replace fills {placeholders}: get('sheet.map_sub', 'fr', ['n' => 12]).
     */
    public static function get(string $key, ?string $locale, array $replace = []): string
    {
        $value = self::lookup($key, self::normalize($locale));

        if (!is_string($value)) {
            $value = self::lookup($key, self::DEFAULT);
        }
        if (!is_string($value)) {
            return $key;   // nobody wrote it in any language
        }

        foreach ($replace as $name => $with) {
            $value = str_replace('{' . $name . '}', (string) $with, $value);
        }

        return $value;
    }

    /**
     * A line that changes with a count.
     *
     * The four languages here all split the same way - one, or not one - so a
     * pair of forms is enough. {n} is filled in for you.
     */
    public static function choose(string $key, int $count, ?string $locale, array $replace = []): string
    {
        $forms = self::raw($key, $locale);

        if (is_array($forms)) {
            $value = $count === 1
                ? ($forms['one'] ?? $forms['other'] ?? $key)
                : ($forms['other'] ?? $forms['one'] ?? $key);
        } else {
            $value = is_string($forms) ? $forms : $key;
        }

        foreach ($replace + ['n' => $count] as $name => $with) {
            $value = str_replace('{' . $name . '}', (string) $with, $value);
        }

        return $value;
    }

    /** A whole list - rule steps, word lists, the scenes behind the adventures */
    public static function all(string $key, ?string $locale): array
    {
        $value = self::lookup($key, self::normalize($locale));

        if (!is_array($value)) {
            $value = self::lookup($key, self::DEFAULT);
        }

        return is_array($value) ? $value : [];
    }

    /** Whatever is under the key, untouched, English if this language lacks it */
    public static function raw(string $key, ?string $locale)
    {
        $value = self::lookup($key, self::normalize($locale));

        return $value ?? self::lookup($key, self::DEFAULT);
    }

    /** Reads one dotted key out of one language file */
    private static function lookup(string $key, string $locale)
    {
        $strings = self::load($locale);

        foreach (explode('.', $key) as $part) {
            if (!is_array($strings) || !array_key_exists($part, $strings)) {
                return null;
            }
            $strings = $strings[$part];
        }

        return $strings;
    }

    private static function load(string $locale): array
    {
        if (isset(self::$loaded[$locale])) {
            return self::$loaded[$locale];
        }

        $file = dirname(__DIR__) . '/lang/' . $locale . '.php';
        $strings = is_file($file) ? require $file : [];

        return self::$loaded[$locale] = is_array($strings) ? $strings : [];
    }
}
