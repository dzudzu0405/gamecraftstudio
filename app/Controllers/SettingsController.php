<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Project;
use App\Services\Tiers;
use App\Services\Uploader;

/** Account settings */
class SettingsController extends Controller
{
    public function index(Request $request): void
    {
        $user   = $this->user();
        $userId = $this->userId();

        $this->view('settings/index', [
            'pageTitle'    => 'Settings',
            'user'         => $user,
            'plan'         => Tiers::get(Auth::plan()),
            'projectCount' => Project::countForUser($userId),
            'assetUsage'   => Uploader::usageBytes($userId),
            'exportCount'  => Database::count('SELECT COUNT(*) FROM exports WHERE user_id = ?', [$userId]),
            'missionCount' => Database::count(
                'SELECT COUNT(*) FROM project_missions m
                 JOIN projects p ON p.id = m.project_id WHERE p.user_id = ?',
                [$userId]
            ),
        ]);
    }

    public function saveProfile(Request $request): void
    {
        $userId = $this->userId();

        $v = new Validator($request->body);
        $v->required('name', 'your name')->max('name', 120, 'your name')
          ->required('email', 'your email address')->email('email')->max('email', 190, 'your email address');

        $email = strtolower($request->str('email'));

        // The new email must not belong to someone else
        if ($v->passes()) {
            $taken = Database::count(
                'SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?',
                [$email, $userId]
            );
            if ($taken > 0) {
                $v->rule('email', false, 'That email address is already in use.');
            }
        }

        if ($v->fails()) {
            Flash::error($v->firstError() ?? 'Please check the details you entered.');
            $this->backWithErrors($v->errors(), $request->body, '/settings');
            return;
        }

        Database::update('users', [
            'name'       => $request->str('name'),
            'email'      => $email,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $userId]);

        Auth::refresh();
        Flash::success('Account details saved.');
        Response::redirect('/settings');
    }

    public function savePassword(Request $request): void
    {
        $userId = $this->userId();
        $user   = Database::first('SELECT * FROM users WHERE id = ? LIMIT 1', [$userId]);

        $current = (string) $request->input('current_password', '');
        $new     = (string) $request->input('password', '');

        $v = new Validator($request->body);
        $v->required('current_password', 'your current password')
          ->required('password', 'a new password')->min('password', 8, 'your new password')->max('password', 200, 'your new password')
          ->matches('password_confirmation', 'password', 'the confirmation password');

        if ($v->passes() && !password_verify($current, (string) $user['password_hash'])) {
            $v->rule('current_password', false, 'That current password is not correct.');
        }

        if ($v->passes() && $current === $new) {
            $v->rule('password', false, 'The new password must be different from the old one.');
        }

        if ($v->fails()) {
            Flash::error($v->firstError() ?? 'Please check the details you entered.');
            $this->backWithErrors($v->errors(), [], '/settings');
            return;
        }

        Database::update('users', [
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
            'updated_at'    => date('Y-m-d H:i:s'),
        ], ['id' => $userId]);

        Flash::success('Password changed.');
        Response::redirect('/settings');
    }
}
