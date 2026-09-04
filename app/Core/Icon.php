<?php
namespace App\Core;

/**
 * Stroke-drawn SVG icon set, kept consistent across the app.
 * Use it in a view:  <?= Icon::get('home', 18) ?>
 */
class Icon
{
    private const PATHS = [
        // Navigation
        'home'      => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'folder'    => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2.5h8a2 2 0 0 1 2 2V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
        'wand'      => '<path d="m4 20 10-10"/><path d="m14 10 2-2"/><path d="M17 3v4M15 5h4M18 13v3M16.5 14.5h3"/><path d="m12 6 1.5 1.5"/>',
        'template'  => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11"/>',
        'book'      => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2Z"/><path d="M4 19a2 2 0 0 1 2-2h13"/>',
        'image'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 16-4.5-4.5L7 21"/>',
        'download'  => '<path d="M12 3v12"/><path d="m7.5 10.5 4.5 4.5 4.5-4.5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'cart'      => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h2.5l2.2 11.2a1.6 1.6 0 0 0 1.6 1.3h8.4a1.6 1.6 0 0 0 1.6-1.3L20 7H5"/>',
        'users'     => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6.1"/><path d="M18 20a6.4 6.4 0 0 0-2.2-4.8"/>',
        'card'      => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19"/><path d="M6 15h4"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 14.5a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.03 1.56V21a2 2 0 1 1-4 0v-.11a1.7 1.7 0 0 0-1.1-1.56 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 14.5a1.7 1.7 0 0 0-1.56-1.03H3a2 2 0 1 1 0-4h.11A1.7 1.7 0 0 0 4.6 8.5a1.7 1.7 0 0 0-.34-1.87l-.06-.06A2 2 0 1 1 7.03 3.74l.06.06a1.7 1.7 0 0 0 1.87.34H9a1.7 1.7 0 0 0 1-1.56V3a2 2 0 1 1 4 0v.11a1.7 1.7 0 0 0 1.03 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87V9a1.7 1.7 0 0 0 1.56 1H21a2 2 0 1 1 0 4h-.11a1.7 1.7 0 0 0-1.49 1.03Z"/>',

        // Actions
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>',
        'bell'      => '<path d="M18 9a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6"/><path d="M13.7 20a2 2 0 0 1-3.4 0"/>',
        'grid'      => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'list'      => '<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'more'      => '<circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>',
        'chevron-right' => '<path d="m9 5 7 7-7 7"/>',
        'chevron-left'  => '<path d="m15 5-7 7 7 7"/>',
        'chevron-down'  => '<path d="m5 9 7 7 7-7"/>',
        'arrow-right'   => '<path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>',
        'arrow-left'    => '<path d="M20 12H5"/><path d="m11 6-6 6 6 6"/>',
        'check'     => '<path d="m4 12.5 5.5 5.5L20 7"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.2 2.7 2.7L16 9.5"/>',
        'x'         => '<path d="M6 6l12 12M18 6 6 18"/>',
        'copy'      => '<rect x="8.5" y="8.5" width="12" height="12" rx="2"/><path d="M15.5 5.5a2 2 0 0 0-2-2h-7a3 3 0 0 0-3 3v7a2 2 0 0 0 2 2"/>',
        'trash'     => '<path d="M4 7h16"/><path d="M9.5 7V5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2"/><path d="M6.5 7 7.5 20a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1L17.5 7"/><path d="M10.5 11v6M13.5 11v6"/>',
        'edit'      => '<path d="M12.5 5.5H6a2 2 0 0 0-2 2V18a2 2 0 0 0 2 2h10.5a2 2 0 0 0 2-2v-6.5"/><path d="M17 3.5a2.1 2.1 0 0 1 3 3L12 14.5l-4 1 1-4Z"/>',
        'upload'    => '<path d="M12 16V4"/><path d="m7.5 8.5 4.5-4.5 4.5 4.5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'printer'   => '<path d="M7 9V4h10v5"/><rect x="3.5" y="9" width="17" height="7" rx="2"/><path d="M7 14h10v6H7z"/>',
        'eye'       => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
        'refresh'   => '<path d="M20.5 11a8.5 8.5 0 0 0-15-4.4"/><path d="M3.5 13a8.5 8.5 0 0 0 15 4.4"/><path d="M5 3v4h4M19 21v-4h-4"/>',
        'shuffle'   => '<path d="M17 3.5 21 7l-4 3.5"/><path d="M17 13.5 21 17l-4 3.5"/><path d="M3 7h4l10 10h4"/><path d="M3 17h4l3-3"/><path d="m14 10 3-3h4"/>',
        'lock'      => '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
        'external'  => '<path d="M14 4h6v6"/><path d="m20 4-8.5 8.5"/><path d="M18 14v4.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10"/>',
        'logout'    => '<path d="M9.5 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3.5"/><path d="M15 8.5 18.5 12 15 15.5"/><path d="M18.5 12H9"/>',
        'user'      => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',

        // Status and illustration
        'sparkles'  => '<path d="M12 3.5 13.6 8l4.4 1.6-4.4 1.6L12 15.7 10.4 11.2 6 9.6 10.4 8Z"/><path d="M18.5 15.5 19.3 18l2.2.8-2.2.8-.8 2.4-.8-2.4-2.2-.8 2.2-.8Z"/><path d="M5 3.5 5.6 5.3 7.5 6l-1.9.7L5 8.5l-.6-1.8L2.5 6l1.9-.7Z"/>',
        'map'       => '<path d="m3 6.5 6-2.5v13.5l-6 2.5Z"/><path d="M9 4l6 2.5v13.5L9 17.5Z"/><path d="m15 6.5 6-2.5v13.5l-6 2.5Z"/>',
        'flag'      => '<path d="M5 21V4"/><path d="M5 5h11l-1.5 3.5L16 12H5Z"/>',
        'trophy'    => '<path d="M7.5 4h9v5a4.5 4.5 0 0 1-9 0Z"/><path d="M7.5 5.5H5A1.5 1.5 0 0 0 5 8.5h2.5M16.5 5.5H19a1.5 1.5 0 0 1 0 3h-2.5"/><path d="M12 13.5V17"/><path d="M8.5 20h7"/><path d="M9.5 17h5v3h-5z"/>',
        'layers'    => '<path d="m12 3 9 5-9 5-9-5Z"/><path d="m3.5 12.5 8.5 4.7 8.5-4.7"/><path d="m3.5 16.8 8.5 4.7 8.5-4.7"/>',
        'palette'   => '<path d="M12 3a9 9 0 1 0 0 18 2 2 0 0 0 1.6-3.2 2 2 0 0 1 1.6-3.2H18a3 3 0 0 0 3-3A9 9 0 0 0 12 3Z"/><circle cx="7.5" cy="11" r="1.1"/><circle cx="10" cy="7" r="1.1"/><circle cx="15" cy="7.5" r="1.1"/>',
        'puzzle'    => '<path d="M9 4.5a2 2 0 1 1 4 0V6h2.5a1.5 1.5 0 0 1 1.5 1.5V10h1.5a2 2 0 1 1 0 4H17v2.5a1.5 1.5 0 0 1-1.5 1.5H13v-1.5a2 2 0 1 0-4 0V18H6.5A1.5 1.5 0 0 1 5 16.5V14H6.5a2 2 0 1 0 0-4H5V7.5A1.5 1.5 0 0 1 6.5 6H9Z"/>',
        'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 7.8h.01"/>',
        'alert'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5"/><path d="M12 16.2h.01"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.2 1.9"/>',
        'crown'     => '<path d="m3 7 3.5 3L12 4l5.5 6L21 7l-1.8 11H4.8Z"/>',
        'target'    => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1"/>',
        'file'      => '<path d="M13.5 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8.5Z"/><path d="M13.5 3v5.5H19"/>',
        'star'      => '<path d="m12 3.8 2.6 5.3 5.9.9-4.2 4.1 1 5.8-5.3-2.8-5.3 2.8 1-5.8L3.5 10l5.9-.9Z"/>',
        'heart'     => '<path d="M12 20s-7.5-4.7-7.5-9.6A4.4 4.4 0 0 1 12 7.6a4.4 4.4 0 0 1 7.5 2.8C19.5 15.3 12 20 12 20Z"/>',
        'dice'      => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><circle cx="8.5" cy="8.5" r="1.2"/><circle cx="15.5" cy="15.5" r="1.2"/><circle cx="12" cy="12" r="1.2"/>',
        // Two cards, the back one fanned out behind
        'cards'     => '<rect x="8.5" y="4" width="11" height="16" rx="2.2"/><path d="M5.6 6.9 4.2 7.4a2 2 0 0 0-1.2 2.6l3.6 9.4"/>',
        'gift'      => '<rect x="3.5" y="8.5" width="17" height="4" rx="1"/><path d="M5 12.5V19a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6.5"/><path d="M12 8.5V21"/><path d="M12 8.5S10.8 4 8.6 4a2.3 2.3 0 0 0 0 4.5Z"/><path d="M12 8.5S13.2 4 15.4 4a2.3 2.3 0 0 1 0 4.5Z"/>',
    ];

    /** Returns a complete <svg> tag */
    public static function get(string $name, int $size = 20, string $extraClass = ''): string
    {
        $body = self::PATHS[$name] ?? self::PATHS['info'];
        $cls  = trim('icon ' . $extraClass);

        return '<svg class="' . htmlspecialchars($cls, ENT_QUOTES) . '" width="' . $size . '" height="' . $size . '"'
             . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"'
             . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
             . $body . '</svg>';
    }

    public static function exists(string $name): bool
    {
        return isset(self::PATHS[$name]);
    }
}
