<?php
/**
 * FOR LOCAL TESTING ONLY - there is no need to upload this to your host.
 *
 * PHP's built-in web server does not read .htaccess, so this script routes
 * every request to index.php in its place.
 *
 * Run it with:
 *     php -S localhost:8000 dev-server.php
 *
 * On cPanel (Apache) this file is unused - .htaccess handles the routing.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);

// Block direct access to source code and data
foreach (['/app/', '/install/', '/storage/', '/config.php'] as $blocked) {
    if (str_starts_with($path, $blocked)) {
        http_response_code(403);
        echo 'Forbidden';
        return true;
    }
}

// Serve real static files (css, js, images) as they are
if ($path !== '/' && is_file($file) && !str_ends_with($path, '.php')) {
    return false;
}

require __DIR__ . '/index.php';
