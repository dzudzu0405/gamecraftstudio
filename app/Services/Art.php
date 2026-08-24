<?php
namespace App\Services;

/**
 * Draws placeholder illustrations as SVG directly in PHP.
 *
 * Why: the real artwork library (36 maps, 30 character sets, hundreds of cards -
 * SRS section 11) is produced and uploaded later. In the meantime every library
 * item carries an "art_seed", and this class turns that seed into a stable
 * illustration (same seed = same picture).
 *
 * Once real artwork exists, just fill in the image_path column for that library
 * item - the system prefers real images and ignores the generated ones
 * (see Library::imageFor()).
 */
class Art
{
    /** Scene themes shared by maps, game covers and cards */
    public const THEMES = [
        'forest'    => 'Forest',
        'dino'      => 'Dinosaurs',
        'space'     => 'Outer space',
        'ocean'     => 'Ocean',
        'pirate'    => 'Pirates',
        'magic'     => 'Magic',
        'castle'    => 'Castle',
        'desert'    => 'Desert',
        'arctic'    => 'Arctic',
        'candy'     => 'Candy land',
        'robot'     => 'Robots',
        'farm'      => 'Farm',
    ];

    /** Palette per theme: [sky top, sky bottom, far hills, near hills, ground, accent] */
    private const PALETTES = [
        'forest' => ['#BDE7C8', '#8FD4A8', '#4E9E6A', '#2F7A4E', '#B6E3A0', '#F2C14E'],
        'dino'   => ['#FFE0B2', '#FFC48C', '#7FA86A', '#4E7A46', '#C9A66B', '#E4572E'],
        'space'  => ['#2B2358', '#4A3A85', '#6C4BD6', '#3A2E6E', '#241C4A', '#FFD166'],
        'ocean'  => ['#BEE7F5', '#7FCBE8', '#2E93B8', '#1C6B8C', '#F2E2B6', '#FF8A5B'],
        'pirate' => ['#FCE8C4', '#F6CFA0', '#3F8FA8', '#26657A', '#E8D096', '#C0392B'],
        'magic'  => ['#E8DCFB', '#C9B4F2', '#8A6BD1', '#5F45A3', '#D7C6F5', '#FFD166'],
        'castle' => ['#D9E8F5', '#AFCDE8', '#7E96B8', '#5B7391', '#A8C88F', '#D96C4F'],
        'desert' => ['#FFE9C0', '#FFD08A', '#E0A96D', '#B8794A', '#F0D3A0', '#3FA796'],
        'arctic' => ['#E4F2FA', '#C3E2F2', '#8FBEDB', '#6A9CBF', '#F5FAFD', '#5B8CF2'],
        'candy'  => ['#FFE3F0', '#FFC2DE', '#F58FBB', '#D96AA0', '#FFF0D6', '#7AC3A8'],
        'robot'  => ['#DCE6EE', '#B9CBDC', '#7E93A8', '#5B6E82', '#C8D4DE', '#F2A65A'],
        'farm'   => ['#DCEFC0', '#C0E39A', '#8CBF5E', '#6A9B44', '#E8D9A8', '#E4572E'],
    ];

    /** Colours for the player tokens (SRS section 9) */
    public const TOKEN_COLORS = [
        'red'    => '#E4572E',
        'blue'   => '#3A7BD5',
        'green'  => '#3FA796',
        'yellow' => '#F2C14E',
        'purple' => '#8A6BD1',
        'orange' => '#F2884E',
        'pink'   => '#E86FA8',
        'teal'   => '#2E9CA8',
    ];

    // ---------------------------------------------------------------
    //  Seeded randomness (same seed -> same result)
    // ---------------------------------------------------------------

    private static int $state = 0;

    private static function seed(string $seed): void
    {
        self::$state = abs(crc32($seed)) ?: 1;
    }

    /** Returns an integer in the range [min, max] */
    private static function rand(int $min, int $max): int
    {
        // Simple LCG - good enough for scattering trees and stars
        self::$state = (self::$state * 1103515245 + 12345) & 0x7FFFFFFF;
        $span = $max - $min + 1;
        return $min + (int) (self::$state % max(1, $span));
    }

    private static function pick(array $items)
    {
        return $items[self::rand(0, count($items) - 1)];
    }

    public static function themeKeys(): array
    {
        return array_keys(self::THEMES);
    }

    public static function themeLabel(string $theme): string
    {
        return self::THEMES[$theme] ?? ucfirst($theme);
    }

    public static function palette(string $theme): array
    {
        return self::PALETTES[$theme] ?? self::PALETTES['forest'];
    }

    /** Pick a stable theme from any string */
    public static function themeFromSeed(string $seed): string
    {
        $keys = self::themeKeys();
        return $keys[abs(crc32($seed)) % count($keys)];
    }

    // ---------------------------------------------------------------
    //  Scenery - used for maps and game covers
    // ---------------------------------------------------------------

    /**
     * A complete scene.
     * @param string $theme One of THEMES
     * @param string $seed  Any string - decides where trees, clouds and stars land
     */
    public static function scene(string $theme, string $seed, int $w = 1200, int $h = 800): string
    {
        self::seed($seed . '|' . $theme);
        $p  = self::palette($theme);
        $id = 'g' . substr(md5($seed . $theme), 0, 6);

        $horizon = (int) round($h * 0.62);

        $svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" role="img" aria-label="' . self::esc(self::themeLabel($theme)) . '">';
        $svg .= '<defs>';
        $svg .= '<linearGradient id="sky' . $id . '" x1="0" y1="0" x2="0" y2="1">';
        $svg .= '<stop offset="0%" stop-color="' . $p[0] . '"/><stop offset="100%" stop-color="' . $p[1] . '"/></linearGradient>';
        $svg .= '<linearGradient id="ground' . $id . '" x1="0" y1="0" x2="0" y2="1">';
        $svg .= '<stop offset="0%" stop-color="' . $p[4] . '"/><stop offset="100%" stop-color="' . self::shade($p[4], -14) . '"/></linearGradient>';
        $svg .= '</defs>';

        // Sky
        $svg .= '<rect width="' . $w . '" height="' . $h . '" fill="url(#sky' . $id . ')"/>';

        // Scale factor: every detail size is expressed against a 1200px frame
        $k = $w / 1200;

        // Theme-specific detail in the sky
        $svg .= self::skyDetail($theme, $w, $horizon, $p, $k);

        // Distant hills
        $svg .= self::ridge($w, $horizon, (int) round($h * 0.16), $p[2], 4);
        $svg .= self::ridge($w, $horizon + 14, (int) round($h * 0.12), $p[3], 3);

        // Ground
        $svg .= '<path d="M0 ' . $horizon . ' Q ' . ($w * 0.3) . ' ' . ($horizon - 18) . ' ' . ($w * 0.62) . ' ' . ($horizon + 6)
              . ' T ' . $w . ' ' . ($horizon - 4) . ' L ' . $w . ' ' . $h . ' L 0 ' . $h . ' Z" fill="url(#ground' . $id . ')"/>';

        // Detail on the ground
        $svg .= self::groundDetail($theme, $w, $h, $horizon, $p, $k);

        $svg .= '</svg>';
        return $svg;
    }

    /** Clouds, stars or sun depending on theme. $k = scale factor from canvas width */
    private static function skyDetail(string $theme, int $w, int $horizon, array $p, float $k): string
    {
        $out = '';

        if ($theme === 'space') {
            $stars = (int) max(18, round(60 * $k));
            for ($i = 0; $i < $stars; $i++) {
                $x = self::rand(0, $w);
                $y = self::rand(0, $horizon);
                $r = round(self::rand(1, 3) * max(0.6, $k), 1);
                $o = self::rand(40, 100) / 100;
                $out .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . $r . '" fill="#FFFFFF" opacity="' . $o . '"/>';
            }
            // Planet
            $px = self::rand((int) ($w * 0.6), (int) ($w * 0.85));
            $py = self::rand((int) ($horizon * 0.2), (int) ($horizon * 0.45));
            $pr = round(self::rand(40, 70) * $k, 1);
            $out .= '<circle cx="' . $px . '" cy="' . $py . '" r="' . $pr . '" fill="' . $p[5] . '" opacity="0.9"/>';
            $out .= '<ellipse cx="' . $px . '" cy="' . $py . '" rx="' . round($pr * 1.7, 1) . '" ry="' . round(max(4, $pr * 0.28), 1)
                  . '" fill="none" stroke="#FFFFFF" stroke-width="' . round(max(1.5, 4 * $k), 1) . '" opacity="0.55"/>';
            return $out;
        }

        // Sun
        $sx = self::rand((int) ($w * 0.62), (int) ($w * 0.88));
        $sy = self::rand((int) ($horizon * 0.14), (int) ($horizon * 0.34));
        $out .= '<circle cx="' . $sx . '" cy="' . $sy . '" r="' . round(self::rand(34, 52) * $k, 1) . '" fill="' . $p[5] . '" opacity="0.85"/>';

        // Clouds
        $clouds = self::rand(2, 4);
        for ($i = 0; $i < $clouds; $i++) {
            $cx = self::rand((int) (40 * $k), (int) ($w - 40 * $k));
            $cy = self::rand((int) (30 * $k), (int) ($horizon * 0.55));
            $s  = (self::rand(70, 130) / 100) * $k;
            $out .= self::cloud($cx, $cy, $s);
        }
        return $out;
    }

    private static function cloud(int $cx, int $cy, float $s): string
    {
        $r = 26 * $s;
        return '<g opacity="0.85" fill="#FFFFFF">'
             . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . round($r, 1) . '"/>'
             . '<circle cx="' . round($cx + $r * 0.9, 1) . '" cy="' . round($cy + $r * 0.18, 1) . '" r="' . round($r * 0.78, 1) . '"/>'
             . '<circle cx="' . round($cx - $r * 0.9, 1) . '" cy="' . round($cy + $r * 0.22, 1) . '" r="' . round($r * 0.66, 1) . '"/>'
             . '<rect x="' . round($cx - $r * 1.5, 1) . '" y="' . round($cy + $r * 0.1, 1) . '" width="' . round($r * 3, 1) . '" height="' . round($r * 0.9, 1) . '" rx="' . round($r * 0.45, 1) . '"/>'
             . '</g>';
    }

    /** A jagged ridge line */
    private static function ridge(int $w, int $baseY, int $height, string $color, int $peaks): string
    {
        $step = $w / max(1, $peaks);
        $d = 'M0 ' . $baseY;
        for ($i = 0; $i < $peaks; $i++) {
            $x1 = $step * $i + $step * 0.5;
            $y1 = $baseY - self::rand((int) ($height * 0.55), $height);
            $x2 = $step * ($i + 1);
            $d .= ' L ' . round($x1, 1) . ' ' . round($y1, 1) . ' L ' . round($x2, 1) . ' ' . $baseY;
        }
        $d .= ' L ' . $w . ' ' . ($baseY + 200) . ' L 0 ' . ($baseY + 200) . ' Z';
        return '<path d="' . $d . '" fill="' . $color . '" opacity="0.9"/>';
    }

    /** Trees, rocks and theme-specific detail. $k = scale factor */
    private static function groundDetail(string $theme, int $w, int $h, int $horizon, array $p, float $k): string
    {
        $out = '';
        $dark = self::shade($p[3], -10);
        $band = max(1, $h - $horizon);   // height of the ground band

        switch ($theme) {
            case 'forest':
            case 'farm':
            case 'dino':
                // Draw the dinosaur first so the trees do not hide it
                if ($theme === 'dino') {
                    $out .= self::dinoSilhouette((int) ($w * 0.68), (int) ($horizon + $band * 0.55), 1.2 * $k, $dark);
                }
                $count = (int) max(3, round(self::rand(6, 10) * min(1.0, $k + 0.35)));
                for ($i = 0; $i < $count; $i++) {
                    $x = self::rand((int) (30 * $k), (int) ($w - 30 * $k));
                    $y = self::rand((int) ($horizon + $band * 0.15), (int) ($h - $band * 0.06));
                    $out .= self::tree($x, $y, (self::rand(60, 120) / 100) * $k, $p[3], $dark);
                }
                break;

            case 'ocean':
            case 'pirate':
                // Waves
                for ($i = 0; $i < 5; $i++) {
                    $y = $horizon + $band * 0.18 + $i * ($band / 6);
                    $out .= '<path d="M0 ' . round($y, 1) . ' q ' . round($w / 8, 1) . ' -' . round(12 * $k, 1) . ' ' . round($w / 4, 1) . ' 0 t ' . round($w / 4, 1) . ' 0 t ' . round($w / 4, 1) . ' 0 t ' . round($w / 4, 1) . ' 0"'
                          . ' fill="none" stroke="' . $p[2] . '" stroke-width="' . round(max(1.5, 4 * $k), 1) . '" opacity="0.5" stroke-linecap="round"/>';
                }
                if ($theme === 'pirate') {
                    $out .= self::ship((int) ($w * 0.68), (int) ($horizon + $band * 0.42), 1.0 * $k, $dark, $p[5]);
                }
                break;

            case 'castle':
            case 'magic':
                $out .= self::castle((int) ($w * 0.5), (int) ($horizon + $band * 0.2), 1.2 * $k, $p[3], $dark, $p[5]);
                for ($i = 0; $i < 4; $i++) {
                    $out .= self::tree(
                        self::rand((int) (40 * $k), (int) ($w - 40 * $k)),
                        self::rand((int) ($horizon + $band * 0.3), (int) ($h - $band * 0.05)),
                        0.7 * $k, $p[3], $dark
                    );
                }
                break;

            case 'desert':
                for ($i = 0; $i < self::rand(3, 5); $i++) {
                    $out .= self::cactus(
                        self::rand((int) (40 * $k), (int) ($w - 40 * $k)),
                        self::rand((int) ($horizon + $band * 0.3), (int) ($h - $band * 0.08)),
                        (self::rand(70, 110) / 100) * $k, '#4E9E6A'
                    );
                }
                break;

            case 'arctic':
                for ($i = 0; $i < self::rand(4, 7); $i++) {
                    $x = self::rand((int) (20 * $k), (int) ($w - 20 * $k));
                    $y = self::rand((int) ($horizon + $band * 0.2), (int) ($h - $band * 0.06));
                    $s = (self::rand(50, 110) / 100) * $k;
                    $out .= '<path d="M' . $x . ' ' . round($y - 60 * $s, 1) . ' L ' . round($x + 46 * $s, 1) . ' ' . $y . ' L ' . round($x - 46 * $s, 1) . ' ' . $y . ' Z" fill="#FFFFFF" opacity="0.95"/>';
                }
                break;

            case 'candy':
                for ($i = 0; $i < self::rand(5, 8); $i++) {
                    $x = self::rand((int) (30 * $k), (int) ($w - 30 * $k));
                    $y = self::rand((int) ($horizon + $band * 0.2), (int) ($h - $band * 0.1));
                    $r = round(self::rand(14, 26) * $k, 1);
                    $out .= '<rect x="' . round($x - 2 * $k, 1) . '" y="' . $y . '" width="' . round(4 * $k, 1) . '" height="' . round(self::rand(14, 30) * $k, 1) . '" fill="#FFFFFF" opacity="0.8"/>';
                    $out .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . $r . '" fill="' . self::pick([$p[2], $p[5], '#FFFFFF']) . '" opacity="0.9"/>';
                }
                break;

            case 'robot':
            case 'space':
                for ($i = 0; $i < self::rand(4, 7); $i++) {
                    $x = self::rand((int) (20 * $k), (int) ($w - 20 * $k));
                    $y = self::rand((int) ($horizon + $band * 0.15), (int) ($h - $band * 0.06));
                    $r = round(self::rand(16, 40) * $k, 1);
                    $out .= '<ellipse cx="' . $x . '" cy="' . $y . '" rx="' . $r . '" ry="' . round($r * 0.4, 1) . '" fill="' . $dark . '" opacity="0.5"/>';
                }
                break;
        }

        // A few tufts of grass / small rocks in every scene
        for ($i = 0; $i < self::rand(5, 9); $i++) {
            $x = self::rand((int) (10 * $k), (int) ($w - 10 * $k));
            $y = self::rand((int) ($horizon + $band * 0.12), (int) ($h - $band * 0.04));
            $out .= '<ellipse cx="' . $x . '" cy="' . $y . '" rx="' . round(self::rand(6, 16) * $k, 1) . '" ry="' . round(self::rand(3, 7) * $k, 1) . '" fill="' . $dark . '" opacity="0.25"/>';
        }

        return $out;
    }

    private static function tree(int $x, int $y, float $s, string $leaf, string $trunk): string
    {
        $h = 70 * $s;
        return '<g>'
             . '<rect x="' . round($x - 5 * $s, 1) . '" y="' . round($y - $h * 0.42, 1) . '" width="' . round(10 * $s, 1) . '" height="' . round($h * 0.42, 1) . '" rx="' . round(3 * $s, 1) . '" fill="' . $trunk . '"/>'
             . '<circle cx="' . $x . '" cy="' . round($y - $h * 0.58, 1) . '" r="' . round(26 * $s, 1) . '" fill="' . $leaf . '"/>'
             . '<circle cx="' . round($x - 18 * $s, 1) . '" cy="' . round($y - $h * 0.42, 1) . '" r="' . round(18 * $s, 1) . '" fill="' . $leaf . '"/>'
             . '<circle cx="' . round($x + 18 * $s, 1) . '" cy="' . round($y - $h * 0.44, 1) . '" r="' . round(19 * $s, 1) . '" fill="' . self::shade($leaf, 8) . '"/>'
             . '</g>';
    }

    private static function cactus(int $x, int $y, float $s, string $c): string
    {
        return '<g fill="' . $c . '">'
             . '<rect x="' . round($x - 8 * $s, 1) . '" y="' . round($y - 70 * $s, 1) . '" width="' . round(16 * $s, 1) . '" height="' . round(70 * $s, 1) . '" rx="' . round(8 * $s, 1) . '"/>'
             . '<rect x="' . round($x - 30 * $s, 1) . '" y="' . round($y - 52 * $s, 1) . '" width="' . round(12 * $s, 1) . '" height="' . round(30 * $s, 1) . '" rx="' . round(6 * $s, 1) . '"/>'
             . '<rect x="' . round($x - 30 * $s, 1) . '" y="' . round($y - 52 * $s, 1) . '" width="' . round(24 * $s, 1) . '" height="' . round(11 * $s, 1) . '" rx="' . round(5 * $s, 1) . '"/>'
             . '<rect x="' . round($x + 18 * $s, 1) . '" y="' . round($y - 60 * $s, 1) . '" width="' . round(12 * $s, 1) . '" height="' . round(26 * $s, 1) . '" rx="' . round(6 * $s, 1) . '"/>'
             . '<rect x="' . round($x + 6 * $s, 1) . '" y="' . round($y - 60 * $s, 1) . '" width="' . round(24 * $s, 1) . '" height="' . round(11 * $s, 1) . '" rx="' . round(5 * $s, 1) . '"/>'
             . '</g>';
    }

    private static function castle(int $x, int $y, float $s, string $body, string $dark, string $flag): string
    {
        $w = 150 * $s;
        $h = 110 * $s;
        $left = $x - $w / 2;
        $g  = '<g>';
        $g .= '<rect x="' . round($left, 1) . '" y="' . round($y - $h, 1) . '" width="' . round($w, 1) . '" height="' . round($h, 1) . '" fill="' . $body . '"/>';
        foreach ([0, 0.42, 0.84] as $i => $off) {
            $tx = $left + $w * $off;
            $g .= '<rect x="' . round($tx, 1) . '" y="' . round($y - $h * 1.35, 1) . '" width="' . round($w * 0.16, 1) . '" height="' . round($h * 1.35, 1) . '" fill="' . self::shade($body, 6) . '"/>';
            $g .= '<path d="M' . round($tx - 4, 1) . ' ' . round($y - $h * 1.35, 1) . ' L ' . round($tx + $w * 0.08, 1) . ' ' . round($y - $h * 1.62, 1) . ' L ' . round($tx + $w * 0.16 + 4, 1) . ' ' . round($y - $h * 1.35, 1) . ' Z" fill="' . $dark . '"/>';
            if ($i === 1) {
                $g .= '<rect x="' . round($tx + $w * 0.075, 1) . '" y="' . round($y - $h * 1.86, 1) . '" width="2" height="' . round($h * 0.24, 1) . '" fill="' . $dark . '"/>';
                $g .= '<path d="M' . round($tx + $w * 0.09, 1) . ' ' . round($y - $h * 1.86, 1) . ' l ' . round(26 * $s, 1) . ' ' . round(7 * $s, 1) . ' l -' . round(26 * $s, 1) . ' ' . round(7 * $s, 1) . ' z" fill="' . $flag . '"/>';
            }
        }
        $g .= '<rect x="' . round($x - 14 * $s, 1) . '" y="' . round($y - $h * 0.52, 1) . '" width="' . round(28 * $s, 1) . '" height="' . round($h * 0.52, 1) . '" rx="' . round(14 * $s, 1) . '" fill="' . $dark . '"/>';
        $g .= '</g>';
        return $g;
    }

    private static function ship(int $x, int $y, float $s, string $hull, string $sail): string
    {
        return '<g>'
             . '<path d="M' . round($x - 60 * $s, 1) . ' ' . $y . ' L ' . round($x + 60 * $s, 1) . ' ' . $y
             . ' L ' . round($x + 44 * $s, 1) . ' ' . round($y + 26 * $s, 1) . ' L ' . round($x - 44 * $s, 1) . ' ' . round($y + 26 * $s, 1) . ' Z" fill="' . $hull . '"/>'
             . '<rect x="' . round($x - 2, 1) . '" y="' . round($y - 90 * $s, 1) . '" width="4" height="' . round(90 * $s, 1) . '" fill="' . $hull . '"/>'
             . '<path d="M' . round($x + 4, 1) . ' ' . round($y - 86 * $s, 1) . ' L ' . round($x + 52 * $s, 1) . ' ' . round($y - 40 * $s, 1) . ' L ' . round($x + 4, 1) . ' ' . round($y - 8 * $s, 1) . ' Z" fill="' . $sail . '"/>'
             . '<path d="M' . round($x - 4, 1) . ' ' . round($y - 78 * $s, 1) . ' L ' . round($x - 46 * $s, 1) . ' ' . round($y - 38 * $s, 1) . ' L ' . round($x - 4, 1) . ' ' . round($y - 10 * $s, 1) . ' Z" fill="#FFFFFF" opacity="0.92"/>'
             . '</g>';
    }

    private static function dinoSilhouette(int $x, int $y, float $s, string $c): string
    {
        return '<g fill="' . $c . '" opacity="0.85">'
             . '<path d="M' . round($x - 70 * $s, 1) . ' ' . $y
             . ' q ' . round(18 * $s, 1) . ' -' . round(30 * $s, 1) . ' ' . round(44 * $s, 1) . ' -' . round(34 * $s, 1)
             . ' q ' . round(10 * $s, 1) . ' -' . round(26 * $s, 1) . ' ' . round(34 * $s, 1) . ' -' . round(24 * $s, 1)
             . ' q ' . round(20 * $s, 1) . ' ' . round(2 * $s, 1) . ' ' . round(22 * $s, 1) . ' ' . round(20 * $s, 1)
             . ' l ' . round(14 * $s, 1) . ' ' . round(4 * $s, 1)
             . ' l -' . round(12 * $s, 1) . ' ' . round(10 * $s, 1)
             . ' q -' . round(6 * $s, 1) . ' ' . round(26 * $s, 1) . ' -' . round(40 * $s, 1) . ' ' . round(30 * $s, 1)
             . ' l -' . round(8 * $s, 1) . ' ' . round(18 * $s, 1)
             . ' z" />'
             . '</g>';
    }

    // ---------------------------------------------------------------
    //  Characters
    // ---------------------------------------------------------------

    /**
     * A friendly character, used for character sets and the hero card.
     * @param int $pose 0..7 - each set has several poses (SRS section 9)
     */
    public static function character(string $seed, int $pose = 0, int $size = 240): string
    {
        self::seed($seed . '|char|' . $pose);

        $skins  = ['#F7D7B8', '#E8BC96', '#C98F66', '#8D5D3F', '#F2C6A0'];
        $hairs  = ['#3A2A21', '#6B4226', '#C9873F', '#1F1B18', '#8C4A2F', '#D8A657'];
        $shirts = ['#6C4BD6', '#3FA796', '#E4572E', '#3A7BD5', '#F2C14E', '#E86FA8', '#2E9CA8'];

        $skin  = self::pick($skins);
        $hair  = self::pick($hairs);
        $shirt = self::pick($shirts);
        $tilt  = [-8, -4, 0, 4, 8, -6, 6, 2][$pose % 8];

        $cx = 120;
        $svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" width="' . $size . '" height="' . $size . '" role="img" aria-label="Character">';
        $svg .= '<rect width="240" height="240" rx="28" fill="' . self::shade($shirt, 62) . '"/>';
        $svg .= '<g transform="rotate(' . $tilt . ' 120 130)">';

        // Body
        $svg .= '<path d="M70 236 q0 -62 50 -62 q50 0 50 62 z" fill="' . $shirt . '"/>';
        // Arms
        $armY = 196 - ($pose % 3) * 16;
        $svg .= '<circle cx="64" cy="' . $armY . '" r="15" fill="' . $skin . '"/>';
        $svg .= '<circle cx="176" cy="' . (196 - (($pose + 1) % 3) * 16) . '" r="15" fill="' . $skin . '"/>';
        // Neck
        $svg .= '<rect x="110" y="150" width="20" height="22" fill="' . self::shade($skin, -8) . '"/>';
        // Head
        $svg .= '<circle cx="' . $cx . '" cy="118" r="52" fill="' . $skin . '"/>';
        // Hair
        $svg .= '<path d="M68 116 q0 -56 52 -56 q52 0 52 56 q-14 -22 -52 -22 q-38 0 -52 22 z" fill="' . $hair . '"/>';
        // Eyes
        $eyeY = 118;
        $svg .= '<circle cx="102" cy="' . $eyeY . '" r="6.5" fill="#2B2438"/>';
        $svg .= '<circle cx="138" cy="' . $eyeY . '" r="6.5" fill="#2B2438"/>';
        $svg .= '<circle cx="104" cy="' . ($eyeY - 2) . '" r="2.2" fill="#FFFFFF"/>';
        $svg .= '<circle cx="140" cy="' . ($eyeY - 2) . '" r="2.2" fill="#FFFFFF"/>';
        // Rosy cheeks
        $svg .= '<ellipse cx="88" cy="134" rx="9" ry="6" fill="#F0908F" opacity="0.55"/>';
        $svg .= '<ellipse cx="152" cy="134" rx="9" ry="6" fill="#F0908F" opacity="0.55"/>';
        // Smile
        $svg .= '<path d="M106 138 q14 14 28 0" fill="none" stroke="#2B2438" stroke-width="3.5" stroke-linecap="round"/>';

        $svg .= '</g></svg>';
        return $svg;
    }

    // ---------------------------------------------------------------
    //  Player tokens (SRS section 9)
    // ---------------------------------------------------------------

    public static function token(string $color, string $label = '', int $size = 120): string
    {
        $hex = self::TOKEN_COLORS[$color] ?? $color;
        if ($hex === '' || $hex[0] !== '#') {
            $hex = '#6C4BD6';
        }
        $svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" width="' . $size . '" height="' . $size . '" role="img">';
        $svg .= '<circle cx="60" cy="60" r="56" fill="' . $hex . '"/>';
        $svg .= '<circle cx="60" cy="60" r="46" fill="none" stroke="#FFFFFF" stroke-width="5" opacity="0.9"/>';
        $svg .= '<circle cx="60" cy="60" r="34" fill="' . self::shade($hex, 18) . '"/>';
        if ($label !== '') {
            $svg .= '<text x="60" y="72" text-anchor="middle" font-family="Verdana, sans-serif" font-size="34" font-weight="700" fill="#FFFFFF">'
                  . self::esc(mb_substr($label, 0, 2)) . '</text>';
        }
        $svg .= '</svg>';
        return $svg;
    }

    // ---------------------------------------------------------------
    //  Card backs
    // ---------------------------------------------------------------

    /** @param string $kind move | mission | reward */
    public static function cardBack(string $kind, string $theme, int $w = 300, int $h = 420): string
    {
        $p = self::palette($theme);
        $base = ['move' => $p[2], 'mission' => $p[3], 'reward' => $p[5]][$kind] ?? $p[2];
        $id = 'c' . substr(md5($kind . $theme), 0, 6);

        $svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" role="img">';
        $svg .= '<defs><linearGradient id="bg' . $id . '" x1="0" y1="0" x2="1" y2="1">'
              . '<stop offset="0%" stop-color="' . self::shade($base, 14) . '"/>'
              . '<stop offset="100%" stop-color="' . self::shade($base, -16) . '"/></linearGradient>';
        $svg .= '<pattern id="dots' . $id . '" width="26" height="26" patternUnits="userSpaceOnUse">'
              . '<circle cx="13" cy="13" r="2.6" fill="#FFFFFF" opacity="0.18"/></pattern></defs>';
        $svg .= '<rect width="' . $w . '" height="' . $h . '" rx="22" fill="url(#bg' . $id . ')"/>';
        $svg .= '<rect width="' . $w . '" height="' . $h . '" rx="22" fill="url(#dots' . $id . ')"/>';
        $svg .= '<rect x="14" y="14" width="' . ($w - 28) . '" height="' . ($h - 28) . '" rx="16" fill="none" stroke="#FFFFFF" stroke-width="3" opacity="0.55"/>';

        $icon = ['move' => 'footprint', 'mission' => 'flag', 'reward' => 'trophy'][$kind] ?? 'star';
        $svg .= '<g transform="translate(' . ($w / 2 - 44) . ',' . ($h / 2 - 44) . ')">' . self::icon($icon, 88, '#FFFFFF', 0.92) . '</g>';
        $svg .= '</svg>';
        return $svg;
    }

    // ---------------------------------------------------------------
    //  Stickers for mission cards (FR-25)
    // ---------------------------------------------------------------

    public const STICKERS = [
        'star'       => 'Star',
        'heart'      => 'Heart',
        'rocket'     => 'Rocket',
        'leaf'       => 'Leaf',
        'flag'       => 'Flag',
        'trophy'     => 'Trophy',
        'book'       => 'Book',
        'bulb'       => 'Light bulb',
        'drop'       => 'Water drop',
        'gem'        => 'Gem',
        'key'        => 'Key',
        'footprint'  => 'Footprints',
        'sun'        => 'Sun',
        'moon'       => 'Moon',
        'shield'     => 'Shield',
        'music'      => 'Music note',
    ];

    public static function stickerKeys(): array
    {
        return array_keys(self::STICKERS);
    }

    public static function stickerLabel(string $key): string
    {
        return self::STICKERS[$key] ?? ucfirst($key);
    }

    /** A standalone sticker as a complete SVG file */
    public static function sticker(string $key, string $color = '#6C4BD6', int $size = 64): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="' . $size . '" height="' . $size . '" role="img" aria-label="' . self::esc(self::stickerLabel($key)) . '">'
             . self::icon($key, 64, $color, 1)
             . '</svg>';
    }

    /**
     * Draws an icon in a 64x64 box, scaled to $size.
     * Used internally by stickers and card backs.
     */
    public static function icon(string $key, int $size = 64, string $color = '#6C4BD6', float $opacity = 1): string
    {
        $s = $size / 64;
        $paths = [
            'star'      => 'M32 6 L39.6 24.2 L59 26 L44.4 39 L48.8 58 L32 47.8 L15.2 58 L19.6 39 L5 26 L24.4 24.2 Z',
            'heart'     => 'M32 56 C10 40 6 28 6 21 A13 13 0 0 1 32 16 A13 13 0 0 1 58 21 C58 28 54 40 32 56 Z',
            'rocket'    => 'M32 4 C42 14 46 26 46 36 L46 44 L18 44 L18 36 C18 26 22 14 32 4 Z M18 46 L10 60 L22 54 Z M46 46 L54 60 L42 54 Z',
            'leaf'      => 'M56 8 C24 8 8 24 8 44 C8 52 12 58 12 58 C12 40 26 26 44 22 C30 30 20 42 18 58 C38 58 56 42 56 8 Z',
            'flag'      => 'M14 6 L14 60 M14 8 L52 16 L14 30 Z',
            'trophy'    => 'M18 8 L46 8 L46 22 A14 14 0 0 1 18 22 Z M12 10 L18 10 L18 22 A6 6 0 0 1 12 16 Z M52 10 L46 10 L46 22 A6 6 0 0 0 52 16 Z M28 36 L36 36 L36 48 L28 48 Z M18 50 L46 50 L46 58 L18 58 Z',
            'book'      => 'M10 12 A6 6 0 0 1 16 8 L30 8 L30 54 L16 54 A6 6 0 0 0 10 58 Z M54 12 A6 6 0 0 0 48 8 L34 8 L34 54 L48 54 A6 6 0 0 1 54 58 Z',
            'bulb'      => 'M32 4 A20 20 0 0 1 44 40 L44 46 L20 46 L20 40 A20 20 0 0 1 32 4 Z M22 50 L42 50 L42 55 L22 55 Z M26 58 L38 58 L38 61 L26 61 Z',
            'drop'      => 'M32 4 C32 4 52 28 52 40 A20 20 0 0 1 12 40 C12 28 32 4 32 4 Z',
            'gem'       => 'M16 8 L48 8 L60 24 L32 60 L4 24 Z',
            'key'       => 'M42 6 A16 16 0 1 1 26 22 L26 26 L18 26 L18 34 L10 34 L10 46 L22 46 L22 38 L30 38 L30 30 L38 30 A16 16 0 0 0 42 6 Z M44 16 A5 5 0 1 0 44 17 Z',
            'footprint' => 'M22 10 A9 13 0 0 1 22 36 A9 13 0 0 1 22 10 Z M42 20 A7 10 0 0 1 42 40 A7 10 0 0 1 42 20 Z M18 42 A12 9 0 0 0 42 46 A12 9 0 0 0 18 42 Z',
            'sun'       => 'M32 18 A14 14 0 1 1 32 46 A14 14 0 1 1 32 18 Z M32 2 L32 12 M32 52 L32 62 M2 32 L12 32 M52 32 L62 32 M11 11 L18 18 M46 46 L53 53 M53 11 L46 18 M18 46 L11 53',
            'moon'      => 'M40 4 A28 28 0 1 0 60 40 A22 22 0 0 1 40 4 Z',
            'shield'    => 'M32 4 L56 14 L56 32 C56 46 46 56 32 60 C18 56 8 46 8 32 L8 14 Z',
            'music'     => 'M24 46 A8 7 0 1 1 24 44 L24 14 L52 8 L52 40 A8 7 0 1 1 52 38 L52 18 L28 23 Z',
        ];

        $d = $paths[$key] ?? $paths['star'];
        $strokeOnly = in_array($key, ['flag', 'sun'], true);

        $attrs = $strokeOnly
            ? 'fill="none" stroke="' . $color . '" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"'
            : 'fill="' . $color . '"';

        return '<g transform="scale(' . round($s, 4) . ')" opacity="' . $opacity . '">'
             . '<path d="' . $d . '" ' . $attrs . '/></g>';
    }

    // ---------------------------------------------------------------
    //  Colour helpers
    // ---------------------------------------------------------------

    /** Lighten (percent > 0) or darken (percent < 0) a hex colour */
    public static function shade(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return '#' . $hex;
        }
        $out = '#';
        for ($i = 0; $i < 3; $i++) {
            $c = hexdec(substr($hex, $i * 2, 2));
            $c = (int) round($c + (255 - $c) * ($percent / 100));
            if ($percent < 0) {
                $c = (int) round(hexdec(substr($hex, $i * 2, 2)) * (1 + $percent / 100));
            }
            $out .= str_pad(dechex(max(0, min(255, $c))), 2, '0', STR_PAD_LEFT);
        }
        return $out;
    }

    /** The text colour (black or white) that reads best on $hex */
    public static function readableOn(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#2B2438';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luma = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luma > 0.62 ? '#2B2438' : '#FFFFFF';
    }

    /** Embed an SVG straight into a src / background-image attribute */
    public static function dataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
