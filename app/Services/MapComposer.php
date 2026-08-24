<?php
namespace App\Services;

/**
 * Composes the finished game map (FR-31).
 *
 * Inputs:
 *   - The background image uploaded for the project, or a generated
 *     placeholder scene if there is none yet.
 *   - A map frame chosen from the library (12 / 18 / 24 spaces).
 *
 * Output: a complete SVG containing the background, the trail, the numbered
 * mission spaces and the START and FINISH markers.
 *
 * SVG rather than a bitmap because:
 *   - It prints sharp at any size and never pixelates.
 *   - It needs no external graphics library, so it runs on any cPanel host.
 *   - It stays small and can be adjusted at any time.
 */
class MapComposer
{
    /** Standard map size - landscape, sized for A3/A4 in landscape */
    public const WIDTH  = 1600;
    public const HEIGHT = 1100;

    /** Column count per space total - chosen so the grid stays balanced */
    private const GRID = [
        12 => ['cols' => 4, 'rows' => 3],
        18 => ['cols' => 6, 'rows' => 3],
        24 => ['cols' => 6, 'rows' => 4],
    ];

    /**
     * @param array       $project       Project record
     * @param string|null $backgroundUrl Background image URL. null = flat colour only
     * @param array       $options       showNumbers, showPath, showTitle, width, height
     */
    public static function render(array $project, ?string $backgroundUrl = null, array $options = []): string
    {
        $cells  = self::normalizeCells((int) ($project['cells'] ?? 18));
        $theme  = (string) ($project['theme'] ?? 'forest');
        $title  = (string) ($project['title'] ?? 'Adventure Map');

        $w = (int) ($options['width']  ?? self::WIDTH);
        $h = (int) ($options['height'] ?? self::HEIGHT);

        $showNumbers = $options['showNumbers'] ?? true;
        $showPath    = $options['showPath']    ?? true;
        $showTitle   = $options['showTitle']   ?? true;

        $palette = Art::palette($theme);
        $points  = self::cellPositions($cells, $w, $h);

        $id = 'm' . substr(md5($theme . $cells . $title), 0, 6);

        $svg  = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"';
        $svg .= ' viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '"';
        $svg .= ' role="img" aria-label="' . self::esc($title) . '">';

        $svg .= '<defs>';
        $svg .= '<clipPath id="frame' . $id . '"><rect x="0" y="0" width="' . $w . '" height="' . $h . '" rx="34"/></clipPath>';
        // A soft veil so text and spaces stand out against the background
        $svg .= '<linearGradient id="veil' . $id . '" x1="0" y1="0" x2="0" y2="1">';
        $svg .= '<stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.30"/>';
        $svg .= '<stop offset="55%" stop-color="#FFFFFF" stop-opacity="0.10"/>';
        $svg .= '<stop offset="100%" stop-color="#FFFFFF" stop-opacity="0.34"/>';
        $svg .= '</linearGradient>';
        $svg .= '</defs>';

        $svg .= '<g clip-path="url(#frame' . $id . ')">';

        // --- Layer 1: background image ---
        if ($backgroundUrl !== null && $backgroundUrl !== '') {
            // preserveAspectRatio="xMidYMid slice" = fill the frame, crop the overflow
            $svg .= '<image href="' . self::esc($backgroundUrl) . '" xlink:href="' . self::esc($backgroundUrl) . '"';
            $svg .= ' x="0" y="0" width="' . $w . '" height="' . $h . '" preserveAspectRatio="xMidYMid slice"/>';
        } else {
            $svg .= '<rect width="' . $w . '" height="' . $h . '" fill="' . $palette[1] . '"/>';
        }

        $svg .= '<rect width="' . $w . '" height="' . $h . '" fill="url(#veil' . $id . ')"/>';

        // --- Layer 2: the trail joining the spaces ---
        if ($showPath) {
            $svg .= self::pathLayer($points, $palette);
        }

        // --- Layer 3: the mission spaces ---
        $svg .= self::cellsLayer($points, $palette, $showNumbers);

        // --- Layer 4: the game title ---
        if ($showTitle) {
            $svg .= self::titleLayer($title, $w, $palette);
        }

        $svg .= '</g>';

        // Outer border
        $svg .= '<rect x="6" y="6" width="' . ($w - 12) . '" height="' . ($h - 12) . '" rx="30" fill="none" stroke="'
              . $palette[3] . '" stroke-width="8" opacity="0.75"/>';

        $svg .= '</svg>';
        return $svg;
    }

    /** The trail joining the spaces, drawn dashed like an adventure map */
    private static function pathLayer(array $points, array $palette): string
    {
        if (count($points) < 2) {
            return '';
        }

        $d = 'M ' . round($points[0]['x'], 1) . ' ' . round($points[0]['y'], 1);
        for ($i = 1; $i < count($points); $i++) {
            $prev = $points[$i - 1];
            $cur  = $points[$i];
            // Round the turns slightly so the trail flows
            $mx = ($prev['x'] + $cur['x']) / 2;
            $my = ($prev['y'] + $cur['y']) / 2;
            $d .= ' Q ' . round($prev['x'], 1) . ' ' . round($cur['y'], 1)
                . ' ' . round($mx, 1) . ' ' . round($my, 1)
                . ' T ' . round($cur['x'], 1) . ' ' . round($cur['y'], 1);
        }

        return '<g fill="none" stroke-linecap="round" stroke-linejoin="round">'
             . '<path d="' . $d . '" stroke="#FFFFFF" stroke-width="30" opacity="0.55"/>'
             . '<path d="' . $d . '" stroke="' . $palette[3] . '" stroke-width="15" opacity="0.85"/>'
             . '<path d="' . $d . '" stroke="#FFFFFF" stroke-width="5" stroke-dasharray="16 18" opacity="0.9"/>'
             . '</g>';
    }

    /** Numbered round spaces, including START and FINISH */
    private static function cellsLayer(array $points, array $palette, bool $showNumbers): string
    {
        $out   = '';
        $total = count($points);
        $r     = 46;

        foreach ($points as $i => $pt) {
            $no      = $i + 1;
            $isStart = ($i === 0);
            $isEnd   = ($i === $total - 1);

            $fill = $isStart ? '#3FA796' : ($isEnd ? $palette[5] : '#FFFFFF');
            $ring = $isStart ? '#2F7A6E' : ($isEnd ? Art::shade($palette[5], -22) : $palette[3]);
            $text = ($isStart || $isEnd) ? '#FFFFFF' : '#2B2438';

            $out .= '<g>';
            $out .= '<circle cx="' . round($pt['x'], 1) . '" cy="' . round($pt['y'] + 5, 1) . '" r="' . $r . '" fill="#000000" opacity="0.16"/>';
            $out .= '<circle cx="' . round($pt['x'], 1) . '" cy="' . round($pt['y'], 1) . '" r="' . $r . '" fill="' . $fill . '"/>';
            $out .= '<circle cx="' . round($pt['x'], 1) . '" cy="' . round($pt['y'], 1) . '" r="' . ($r - 6) . '" fill="none" stroke="' . $ring . '" stroke-width="5"/>';

            if ($showNumbers) {
                $label = $no;
                $size  = 34;
                if ($isStart) { $label = 'GO'; $size = 26; }
                if ($isEnd)   { $label = 'WIN'; $size = 22; }

                $out .= '<text x="' . round($pt['x'], 1) . '" y="' . round($pt['y'] + $size * 0.35, 1) . '"';
                $out .= ' text-anchor="middle" font-family="Verdana, Geneva, sans-serif" font-size="' . $size . '"';
                $out .= ' font-weight="700" fill="' . $text . '">' . self::esc((string) $label) . '</text>';
            }

            $out .= '</g>';
        }

        return $out;
    }

    private static function titleLayer(string $title, int $w, array $palette): string
    {
        $title = mb_strtoupper(mb_substr($title, 0, 42));
        $bw    = min($w - 160, max(420, mb_strlen($title) * 26 + 120));
        $x     = ($w - $bw) / 2;

        return '<g>'
             . '<rect x="' . round($x, 1) . '" y="26" width="' . round($bw, 1) . '" height="86" rx="43" fill="#FFFFFF" opacity="0.94"/>'
             . '<rect x="' . round($x + 8, 1) . '" y="34" width="' . round($bw - 16, 1) . '" height="70" rx="35" fill="none" stroke="' . $palette[3] . '" stroke-width="4"/>'
             . '<text x="' . round($w / 2, 1) . '" y="82" text-anchor="middle" font-family="Verdana, Geneva, sans-serif"'
             . ' font-size="38" font-weight="700" letter-spacing="1.5" fill="' . $palette[3] . '">' . self::esc($title) . '</text>'
             . '</g>';
    }

    /**
     * Centre point of each space, laid out in a serpentine (left to right, then
     * right to left). This keeps the trail continuous and never crossing itself,
     * exactly like a real board game.
     */
    public static function cellPositions(int $cells, int $w = self::WIDTH, int $h = self::HEIGHT): array
    {
        $cells = self::normalizeCells($cells);
        $grid  = self::GRID[$cells];
        $cols  = $grid['cols'];
        $rows  = $grid['rows'];

        // Margins: leave room for the title along the top
        $padX = 130;
        $padTop = 190;
        $padBottom = 110;

        $usableW = $w - $padX * 2;
        $usableH = $h - $padTop - $padBottom;

        $stepX = $cols > 1 ? $usableW / ($cols - 1) : 0;
        $stepY = $rows > 1 ? $usableH / ($rows - 1) : 0;

        $points = [];
        for ($row = 0; $row < $rows; $row++) {
            for ($c = 0; $c < $cols; $c++) {
                // Odd rows run backwards -> serpentine
                $col = ($row % 2 === 0) ? $c : ($cols - 1 - $c);

                // Nudge up and down a little so it does not look rigid
                $wobble = (($col + $row) % 2 === 0) ? -16 : 16;

                $points[] = [
                    'x' => $padX + $col * $stepX,
                    'y' => $padTop + $row * $stepY + $wobble,
                ];
            }
        }

        return array_slice($points, 0, $cells);
    }

    /** Only 12, 18 and 24 are valid (SRS section 10) */
    public static function normalizeCells(int $cells): int
    {
        return isset(self::GRID[$cells]) ? $cells : 18;
    }

    public static function availableCells(): array
    {
        return array_keys(self::GRID);
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
