<?php
/**
 * GameCraft Studio - configuration
 * -----------------------------------------------------------------
 * SETUP: rename this file to  config.php  and fill in the database
 * details you created under cPanel > MySQL Databases.
 * -----------------------------------------------------------------
 */

return [

    // The name shown throughout the app
    'app_name' => 'GameCraft Studio',

    // Web address. Leave empty ('') to detect it automatically.
    // If the app lives in a sub-folder, for example https://example.com/gamecraft,
    // put that full address here: 'https://example.com/gamecraft'
    'base_url'  => '',

    // Time zone used for dates. See php.net/timezones for the full list.
    'timezone'  => 'UTC',

    // Debug mode. MUST be false on a live site.
    'debug'     => false,

    'database' => [
        // 'mysql' (recommended, and what cPanel provides) or 'sqlite' (local testing)
        'driver'   => 'mysql',

        // --- Your cPanel MySQL details ---
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'cpaneluser_gamecraft',   // Database name
        'user'     => 'cpaneluser_gamecraft',   // Database user
        'pass'     => '',                       // Database password
        'charset'  => 'utf8mb4',

        // Only used when driver is 'sqlite'
        'sqlite_path' => __DIR__ . '/storage/gamecraft.sqlite',
    ],

    // Where uploaded images are stored, relative to the app root
    'upload_dir'      => __DIR__ . '/uploads',

    // Largest accepted upload, in bytes. 12 MB.
    'upload_max_size' => 12 * 1024 * 1024,

    // Image formats accepted for upload
    'upload_allowed'  => ['jpg', 'jpeg', 'png', 'webp'],

    // Shows the Discover section - Marketplace and Community - in the sidebar.
    // Off by default: nothing can be bought or posted there yet, so the pages
    // only hold sample content. Set to true to bring the menu back.
    'discover_enabled' => false,

    // Plans are sold on WarriorPlus. Paste the sales page address here and the
    // Billing screen sends buyers straight to it. Leave empty to show no link.
    'purchase_url' => '',

    // Secret used to sign cookies and sessions. CHANGE THIS to your own long random string.
    'app_key' => 'replace-this-with-a-long-random-string',
];
