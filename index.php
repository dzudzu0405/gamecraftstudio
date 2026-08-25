<?php
/**
 * GameCraft Studio - the single entry point.
 *
 * Every URL routes through this file (see .htaccess). If the host has
 * mod_rewrite disabled, the fallback form still works: index.php?r=projects
 */

require __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\Url;
use App\Core\View;

$request = new Request();

// -----------------------------------------------------------------
//  Not installed yet -> send them to the installer
// -----------------------------------------------------------------
if (!gc_is_installed()) {
    if (!str_starts_with($request->path, '/install')) {
        Response::redirect('/install');
    }
}

// -----------------------------------------------------------------
//  Variables shared with every view
// -----------------------------------------------------------------
View::share('appName', (string) App\Core\Config::get('app_name', 'GameCraft Studio'));
View::share('currentPath', $request->path);
View::share('authUser', Auth::check() ? Auth::user() : null);

// -----------------------------------------------------------------
//  CSRF protection on every state-changing request
// -----------------------------------------------------------------
Csrf::verify($request);

// -----------------------------------------------------------------
//  Routes
// -----------------------------------------------------------------
$router = new Router();

$auth  = [[Auth::class, 'requireLogin']];
$guest = [[Auth::class, 'requireGuest']];

// --- Installation ---
$router->get('/install',          'InstallController@index');
$router->post('/install',         'InstallController@run');

// --- Accounts ---
$router->get('/login',            'AuthController@showLogin',    $guest);
$router->post('/login',           'AuthController@login',        $guest);
$router->get('/register',         'AuthController@showRegister', $guest);
$router->post('/register',        'AuthController@register',     $guest);
$router->post('/logout',          'AuthController@logout');

// --- Forgotten password ---
$router->get('/forgot',           'PasswordController@showForgot', $guest);
$router->post('/forgot',          'PasswordController@sendLink',   $guest);
$router->get('/reset/{token}',    'PasswordController@showReset',  $guest);
$router->post('/reset/{token}',   'PasswordController@saveReset',  $guest);

// --- Sign in with Google (only routed when a client id and secret are set) ---
if (App\Services\GoogleAuth::isEnabled()) {
    $router->get('/auth/google',          'GoogleController@start',    $guest);
    $router->get('/auth/google/callback', 'GoogleController@callback', $guest);
}

// --- Dashboard (FR-04 to FR-08, FR-19 to FR-22) ---
$router->get('/',                 'DashboardController@index',   $auth);

// --- My Projects (FR-09 to FR-16) ---
$router->get('/projects',             'ProjectController@index',     $auth);
$router->get('/projects/{id}',        'ProjectController@show',      $auth);
$router->post('/projects/{id}/duplicate', 'ProjectController@duplicate', $auth);
$router->post('/projects/{id}/delete',    'ProjectController@destroy',   $auth);
$router->post('/projects/{id}/rename',    'ProjectController@rename',    $auth);
$router->post('/projects/{id}/status',    'ProjectController@setStatus', $auth);

// --- Create a game: the five-step assembler flow (SRS section 2.3) ---
$router->get('/create',                'CreateController@start',      $auth);
$router->post('/create',               'CreateController@store',      $auth);
$router->get('/create/{id}/step/{step}',  'CreateController@step',    $auth);
$router->post('/create/{id}/step/{step}', 'CreateController@saveStep', $auth);
$router->post('/create/{id}/upload',      'CreateController@upload',   $auth);
$router->post('/create/{id}/generate',    'CreateController@generateMissions', $auth);
$router->get('/import',                'CreateController@importForm', $auth);
$router->post('/import',               'CreateController@import',     $auth);

// --- Studio: detailed editing (FR-25, FR-26) ---
$router->get('/studio/{id}',              'StudioController@index',   $auth);
$router->post('/studio/{id}/mission/{mid}',        'StudioController@saveMission',   $auth);
$router->post('/studio/{id}/mission/{mid}/reroll', 'StudioController@rerollMission', $auth);
$router->post('/studio/{id}/regenerate',  'StudioController@regenerate', $auth);
$router->post('/studio/{id}/content',     'StudioController@saveContent', $auth);
$router->post('/studio/{id}/players',     'StudioController@savePlayers', $auth);

// --- Preview and export (FR-26, FR-27) ---
$router->get('/preview/{id}',      'ExportController@preview',  $auth);
$router->get('/print/{id}',        'ExportController@print',    $auth);
$router->post('/export/{id}',      'ExportController@create',   $auth);
$router->get('/exports',           'ExportController@index',    $auth);
$router->post('/exports/{id}/delete', 'ExportController@destroy', $auth);
$router->get('/listing/{id}',      'ExportController@listing',  $auth);
$router->post('/listing/{id}',     'ExportController@saveListing', $auth);

// --- Library and the other modules ---
$router->get('/templates',         'TemplateController@index',  $auth);
$router->post('/templates/{id}/use', 'TemplateController@use',   $auth);
$router->get('/library',           'LibraryController@index',   $auth);
$router->get('/assets',            'AssetController@index',     $auth);
$router->post('/assets/upload',    'AssetController@upload',    $auth);
$router->post('/assets/{id}/delete','AssetController@destroy',  $auth);

// Discover: not registered at all while Marketplace and Community hold nothing
// but sample data, so both fall through to the 404 handler below.
if (App\Core\Config::get('discover_enabled', false)) {
    $router->get('/marketplace',       'MarketplaceController@index', $auth);
    $router->get('/community',         'CommunityController@index', $auth);
    $router->post('/community/{id}/like', 'CommunityController@like', $auth);
}

// --- Plans and billing (FR-21, FR-22, FR-28, FR-29) ---
$router->get('/billing',           'BillingController@index',   $auth);
$router->post('/billing/plan',     'BillingController@changePlan', $auth);

// --- Account settings ---
$router->get('/settings',          'SettingsController@index',  $auth);
$router->post('/settings/profile', 'SettingsController@saveProfile',  $auth);
$router->post('/settings/password','SettingsController@savePassword', $auth);

// --- Generated placeholder artwork (SVG) ---
$router->get('/art/scene/{theme}/{seed}.svg',    'ArtController@scene');
$router->get('/art/character/{seed}/{pose}.svg', 'ArtController@character');
$router->get('/art/card/{kind}/{theme}.svg',     'ArtController@card');
$router->get('/art/token/{color}.svg',           'ArtController@token');
$router->get('/art/sticker/{key}.svg',           'ArtController@sticker');
$router->get('/art/map/{id}.svg',                'ArtController@map', $auth);

// --- Not found ---
$router->fallback(function (Request $req, int $status) {
    if ($req->isAjax()) {
        Response::json(['ok' => false, 'message' => 'No such route.'], $status);
    }
    Response::abort($status);
});

// -----------------------------------------------------------------
//  Run
// -----------------------------------------------------------------
try {
    $router->dispatch($request);
} catch (\Throwable $e) {
    error_log('[GameCraft] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    if ((bool) App\Core\Config::get('debug', false)) {
        http_response_code(500);
        echo '<pre style="padding:24px;font:13px ui-monospace,monospace;background:#2B2438;color:#FFD9D9;white-space:pre-wrap">';
        echo htmlspecialchars($e->getMessage(), ENT_QUOTES) . "\n\n";
        echo htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString(), ENT_QUOTES);
        echo '</pre>';
        exit;
    }

    Response::abort(500, 'Something went wrong. Please try again in a moment.');
}
