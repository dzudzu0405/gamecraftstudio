<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\Tiers;
use Install\Schema;
use Install\Seeder;

/**
 * The one-time installer.
 * Checks the environment, creates the tables, seeds the content library and
 * creates the first administrator account.
 */
class InstallController extends Controller
{
    public function index(Request $request): void
    {
        // Already installed - do not let anyone back in
        if (gc_is_installed()) {
            Response::redirect('/');
        }

        $this->view('install/index', [
            'pageTitle' => 'Install GameCraft Studio',
            'checks'    => $this->environmentChecks(),
            'dbOk'      => Database::isConnected(),
            'dbError'   => $this->databaseError(),
        ], 'layouts/blank');
    }

    public function run(Request $request): void
    {
        if (gc_is_installed()) {
            Response::redirect('/');
        }

        $v = new Validator($request->body);
        $v->required('name', 'your name')->max('name', 120, 'your name')
          ->required('email', 'your email address')->email('email')
          ->required('password', 'a password')->min('password', 8, 'your password')
          ->matches('password_confirmation', 'password', 'the confirmation password');

        if ($v->fails()) {
            Flash::error($v->firstError() ?? 'Please check the details you entered.');
            $this->backWithErrors($v->errors(), $request->body, '/install');
            return;
        }

        if (!Database::isConnected()) {
            Flash::error('Could not connect to the database. Please check config.php.');
            $this->back('/install');
            return;
        }

        try {
            // 1. Create the tables
            $this->createTables();

            // 2. Seed the content library
            $report = Seeder::run(true);

            // 3. Create the first administrator account
            $userId = Database::insert('users', [
                'name'            => $request->str('name'),
                'email'           => strtolower($request->str('email')),
                'password_hash'   => password_hash($request->str('password'), PASSWORD_DEFAULT),
                'plan'            => Tiers::PUBLISHER,   // the first account gets everything unlocked
                'role'            => 'admin',
                'locale'          => 'en',
                'is_active'       => 1,
                'plan_started_at' => date('Y-m-d H:i:s'),
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            // 4. Sample projects (optional)
            if ($request->bool('demo')) {
                $report['projects'] = Seeder::demoProjects($userId, Tiers::PUBLISHER);
            }

            Auth::login($userId);

            Flash::success('Installation complete. Seeded ' . array_sum($report) . ' library records.');
            Response::redirect('/');

        } catch (\Throwable $e) {
            error_log('[GameCraft install] ' . $e->getMessage());
            Flash::error('Installation failed: ' . $e->getMessage());
            $this->back('/install');
        }
    }

    /** Creates the tables, ignoring MySQL's "index already exists" errors */
    private function createTables(): void
    {
        $driver = Database::driver();
        $pdo    = Database::connect();

        foreach (Schema::sql($driver) as $block) {
            foreach (explode(";\n", $block . "\n") as $statement) {
                $statement = trim($statement);
                // Strip comment lines
                $statement = preg_replace('/^--.*$/m', '', $statement) ?? $statement;
                $statement = trim($statement, " \t\n\r;");

                if ($statement === '') {
                    continue;
                }

                try {
                    $pdo->exec($statement);
                } catch (\PDOException $e) {
                    // 1061 = duplicate index; some MySQL builds word it differently
                    $isDuplicateIndex = str_contains($e->getMessage(), 'Duplicate key name')
                        || str_contains($e->getMessage(), 'already exists');

                    if (!$isDuplicateIndex) {
                        throw $e;
                    }
                }
            }
        }
    }

    /** Requirements the server has to meet */
    private function environmentChecks(): array
    {
        $uploads = GC_ROOT . '/uploads';
        $storage = GC_ROOT . '/storage';
        $driver  = (string) Config::get('database.driver', 'mysql');

        return [
            [
                'label'    => 'PHP 8.1 or newer',
                'ok'       => version_compare(PHP_VERSION, '8.1.0', '>='),
                'value'    => PHP_VERSION,
                'fix'      => 'Open cPanel > MultiPHP Manager and switch to PHP 8.1 or newer.',
            ],
            [
                'label'    => $driver === 'sqlite' ? 'SQLite support (pdo_sqlite)' : 'MySQL support (pdo_mysql)',
                'ok'       => extension_loaded($driver === 'sqlite' ? 'pdo_sqlite' : 'pdo_mysql'),
                'value'    => extension_loaded($driver === 'sqlite' ? 'pdo_sqlite' : 'pdo_mysql') ? 'Yes' : 'No',
                'fix'      => 'Enable it under cPanel > Select PHP Version > Extensions.',
            ],
            [
                'label'    => 'GD image library (resizes uploads)',
                'ok'       => extension_loaded('gd'),
                'value'    => extension_loaded('gd') ? 'Yes' : 'No',
                'fix'      => 'Optional. Without it, uploads are stored at their original size.',
                'optional' => true,
            ],
            [
                'label'    => 'Unicode text support (mbstring)',
                'ok'       => extension_loaded('mbstring'),
                'value'    => extension_loaded('mbstring') ? 'Yes' : 'No',
                'fix'      => 'Enable mbstring under cPanel > Select PHP Version > Extensions.',
            ],
            [
                'label'    => 'uploads/ folder is writable',
                'ok'       => is_dir($uploads) && is_writable($uploads),
                'value'    => is_dir($uploads) ? (is_writable($uploads) ? 'Writable' : 'Read only') : 'Missing',
                'fix'      => 'In File Manager, right-click uploads > Change Permissions > set 755.',
            ],
            [
                'label'    => 'storage/ folder is writable',
                'ok'       => is_dir($storage) && is_writable($storage),
                'value'    => is_dir($storage) ? (is_writable($storage) ? 'Writable' : 'Read only') : 'Missing',
                'fix'      => 'Set permissions to 755 on the storage folder.',
            ],
        ];
    }

    private function databaseError(): ?string
    {
        try {
            Database::connect();
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
