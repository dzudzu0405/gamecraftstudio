<?php
/**
 * Application bootstrap: load config, register the autoloader, start the session.
 * Every request passes through here first.
 */

define('GC_ROOT', dirname(__DIR__));
define('GC_VERSION', '1.0.0');

// --- Namespace autoloader (no Composer needed) ---
spl_autoload_register(function (string $class): void {
    $map = [
        'App\\'     => GC_ROOT . '/app/',
        'Install\\' => GC_ROOT . '/install/',
    ];

    foreach ($map as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

use App\Core\Config;
use App\Core\Session;
use App\Core\View;

// --- Configuration ---
$configFile = GC_ROOT . '/config.php';
Config::load($configFile);

date_default_timezone_set((string) Config::get('timezone', 'UTC'));

$debug = (bool) Config::get('debug', false);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_NOTICE));

// Log errors to a file rather than showing them to visitors
ini_set('log_errors', '1');
ini_set('error_log', GC_ROOT . '/storage/logs/error.log');

// --- Session ---
Session::start();

// --- View ---
View::setDirectory(GC_ROOT . '/app/Views');

// --- Has the app been installed yet? ---
function gc_is_installed(): bool
{
    if (!is_file(GC_ROOT . '/config.php')) {
        return false;
    }
    if (!\App\Core\Database::isConnected()) {
        return false;
    }
    return \App\Core\Database::tableExists('users');
}
