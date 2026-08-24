<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\Art;
use App\Services\MapComposer;
use App\Services\PrintBundle;

/**
 * Serves the generated SVG placeholder artwork.
 *
 * These are stand-ins until real artwork exists. Once files are dropped into
 * uploads/library/..., Library::imageFor() points straight at them and these
 * /art/... routes stop being used.
 */
class ArtController extends Controller
{
    /** Cache for 30 days - the artwork comes from a seed, so it never changes */
    private const CACHE_SECONDS = 2592000;

    /** Scenery: /art/scene/{theme}/{seed}.svg */
    public function scene(Request $request, array $params): void
    {
        $theme = $this->safeTheme($params['theme'] ?? 'forest');
        $seed  = $this->safeSeed($params['seed'] ?? 'default');

        $w = max(120, min(2400, $request->int('w', 800)));
        $h = max(80,  min(1600, $request->int('h', 560)));

        Response::raw(Art::scene($theme, $seed, $w, $h), 'image/svg+xml; charset=utf-8', self::CACHE_SECONDS);
    }

    /** Character: /art/character/{seed}/{pose}.svg */
    public function character(Request $request, array $params): void
    {
        $seed = $this->safeSeed($params['seed'] ?? 'hero');
        $pose = max(1, min(8, (int) ($params['pose'] ?? 1)));
        $size = max(48, min(800, $request->int('size', 240)));

        Response::raw(Art::character($seed, $pose - 1, $size), 'image/svg+xml; charset=utf-8', self::CACHE_SECONDS);
    }

    /** Card back: /art/card/{kind}/{theme}.svg */
    public function card(Request $request, array $params): void
    {
        $kind  = in_array($params['kind'] ?? '', ['move', 'mission', 'reward'], true) ? $params['kind'] : 'move';
        $theme = $this->safeTheme($params['theme'] ?? 'forest');

        $w = max(80,  min(900,  $request->int('w', 300)));
        $h = max(100, min(1200, $request->int('h', 420)));

        Response::raw(Art::cardBack($kind, $theme, $w, $h), 'image/svg+xml; charset=utf-8', self::CACHE_SECONDS);
    }

    /** Player token: /art/token/{color}.svg */
    public function token(Request $request, array $params): void
    {
        $color = (string) ($params['color'] ?? 'red');
        if (!isset(Art::TOKEN_COLORS[$color])) {
            $color = 'red';
        }
        $label = mb_substr($request->str('label', ''), 0, 2);
        $size  = max(24, min(400, $request->int('size', 120)));

        Response::raw(Art::token($color, $label, $size), 'image/svg+xml; charset=utf-8', self::CACHE_SECONDS);
    }

    /** Mission card sticker: /art/sticker/{key}.svg */
    public function sticker(Request $request, array $params): void
    {
        $key = (string) ($params['key'] ?? 'star');
        if (!isset(Art::STICKERS[$key])) {
            $key = 'star';
        }
        $color = $request->str('color', '6C4BD6');
        $color = preg_match('/^[0-9A-Fa-f]{6}$/', $color) ? '#' . $color : '#6C4BD6';
        $size  = max(16, min(256, $request->int('size', 64)));

        Response::raw(Art::sticker($key, $color, $size), 'image/svg+xml; charset=utf-8', self::CACHE_SECONDS);
    }

    /**
     * A project's finished map: /art/map/{id}.svg
     * This is the background composed with the map frame (FR-31).
     */
    public function map(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));

        $svg = MapComposer::render(
            $project,
            PrintBundle::backgroundUrl($project),
            [
                'width'       => max(400, min(2400, $request->int('w', MapComposer::WIDTH))),
                'height'      => max(280, min(1600, $request->int('h', MapComposer::HEIGHT))),
                'showTitle'   => !$request->has('notitle'),
                'showNumbers' => !$request->has('nonumbers'),
            ]
        );

        // No caching - the map changes whenever the project does
        Response::raw($svg, 'image/svg+xml; charset=utf-8', 0);
    }

    private function safeTheme(string $theme): string
    {
        return isset(Art::THEMES[$theme]) ? $theme : 'forest';
    }

    /** Strips the seed down to safe characters */
    private function safeSeed(string $seed): string
    {
        $seed = preg_replace('/[^A-Za-z0-9_\-]/', '', $seed) ?? '';
        return $seed !== '' ? substr($seed, 0, 80) : 'default';
    }
}
