<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Models\Project;
use App\Services\Entitlements;
use App\Services\LoginCode;
use App\Services\Tiers;
use App\Services\Uploader;

/**
 * Managing accounts: who is on which plan, who can sign in, who is an
 * administrator.
 *
 * Everything that changes a plan goes through Entitlements::setPlan(), so the
 * plan history on a user's page is complete - a refund handled here reads back
 * later as "Administrator moved Pro -> Starter" with the note that was typed.
 *
 * THE THREE THINGS THIS SCREEN WILL NOT DO
 *   - take away your own administrator rights
 *   - deactivate or delete your own account
 *   - remove the last administrator on the site
 * Each of those would leave nobody able to get back in.
 */
class UserController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): void
    {
        $q      = $request->str('q');
        $plan   = $request->str('plan');
        $role   = $request->str('role');
        $status = $request->str('status');
        $page   = max(1, $request->int('page', 1));

        $where  = [];
        $params = [];

        if ($q !== '') {
            $where[]  = '(name LIKE ? OR email LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        if (Tiers::exists($plan)) {
            $where[]  = 'plan = ?';
            $params[] = $plan;
        }

        if (in_array($role, ['admin', 'creator'], true)) {
            $where[]  = 'role = ?';
            $params[] = $role;
        }

        if ($status === 'disabled') {
            $where[] = 'is_active = 0';
        } elseif ($status === 'active') {
            $where[] = 'is_active = 1';
        } elseif ($status === 'unverified') {
            $where[] = 'email_verified_at IS NULL';
        }

        $sql   = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $total = Database::count('SELECT COUNT(*) FROM users' . $sql, $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        $rows = Database::all(
            'SELECT * FROM users' . $sql . ' ORDER BY id DESC LIMIT ' . self::PER_PAGE
            . ' OFFSET ' . (($page - 1) * self::PER_PAGE),
            $params
        );

        // Project counts for the whole page in one query rather than one each
        $counts = [];
        if ($rows) {
            $ids  = array_map(static fn (array $r): int => (int) $r['id'], $rows);
            $marks = implode(',', array_fill(0, count($ids), '?'));

            foreach (Database::all(
                'SELECT user_id, COUNT(*) AS n FROM projects WHERE user_id IN (' . $marks . ') GROUP BY user_id',
                $ids
            ) as $row) {
                $counts[(int) $row['user_id']] = (int) $row['n'];
            }
        }

        $this->view('admin/users', [
            'pageTitle' => 'Users',
            'users'     => $rows,
            'counts'    => $counts,
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'filters'   => ['q' => $q, 'plan' => $plan, 'role' => $role, 'status' => $status],
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $user = $this->findUser((int) ($params['id'] ?? 0));

        $this->view('admin/user', [
            'pageTitle'   => $user['name'],
            'row'         => $user,
            'history'     => Entitlements::historyFor((int) $user['id']),
            'projects'    => Database::all(
                'SELECT id, title, status, updated_at FROM projects WHERE user_id = ? ORDER BY updated_at DESC LIMIT 10',
                [(int) $user['id']]
            ),
            'projectCount' => Project::countForUser((int) $user['id']),
            'exportCount'  => Database::count('SELECT COUNT(*) FROM exports WHERE user_id = ?', [(int) $user['id']]),
            'assetCount'   => Database::count('SELECT COUNT(*) FROM user_assets WHERE user_id = ?', [(int) $user['id']]),
            'storage'      => Uploader::formatBytes(Uploader::usageBytes((int) $user['id'])),
            'isSelf'       => (int) $user['id'] === $this->userId(),
        ]);
    }

    // -----------------------------------------------------------------
    //  Actions
    // -----------------------------------------------------------------

    public function setPlan(Request $request, array $params): void
    {
        $user = $this->findUser((int) ($params['id'] ?? 0));
        $plan = $request->str('plan');

        if (!Tiers::exists($plan)) {
            Flash::error('That is not a valid plan.');
            $this->back('/admin/users/' . $user['id']);
            return;
        }

        $note = $request->str('note');

        $changed = Entitlements::setPlan(
            (int) $user['id'],
            $plan,
            Entitlements::SOURCE_ADMIN,
            $this->userId(),
            null,
            $note !== '' ? $note : null,
            $request->ip()
        );

        if ($changed) {
            Flash::success($user['name'] . ' is now on the ' . Tiers::name($plan) . ' plan.');
        } else {
            Flash::info($user['name'] . ' was already on the ' . Tiers::name($plan) . ' plan.');
        }

        $this->back('/admin/users/' . $user['id']);
    }

    public function setRole(Request $request, array $params): void
    {
        $user = $this->findUser((int) ($params['id'] ?? 0));
        $role = $request->str('role') === 'admin' ? 'admin' : 'creator';

        if ((int) $user['id'] === $this->userId()) {
            Flash::error('You cannot change your own administrator rights. Ask another administrator to do it.');
            $this->back('/admin/users/' . $user['id']);
            return;
        }

        if ($role === 'creator' && $this->isLastAdmin((int) $user['id'])) {
            Flash::error('That is the only administrator left. Promote somebody else first.');
            $this->back('/admin/users/' . $user['id']);
            return;
        }

        Database::update('users', [
            'role'       => $role,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $user['id']]);

        Flash::success($role === 'admin'
            ? $user['name'] . ' is now an administrator.'
            : $user['name'] . ' is no longer an administrator.');

        $this->back('/admin/users/' . $user['id']);
    }

    public function setStatus(Request $request, array $params): void
    {
        $user   = $this->findUser((int) ($params['id'] ?? 0));
        $active = $request->str('active') === '1';

        if (!$active && (int) $user['id'] === $this->userId()) {
            Flash::error('You cannot deactivate your own account.');
            $this->back('/admin/users/' . $user['id']);
            return;
        }

        if (!$active && $this->isLastAdmin((int) $user['id'])) {
            Flash::error('That is the only administrator left, so the account has to stay active.');
            $this->back('/admin/users/' . $user['id']);
            return;
        }

        Database::update('users', [
            'is_active'  => $active ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $user['id']]);

        Flash::success($active
            ? $user['name'] . ' can sign in again.'
            : $user['name'] . ' can no longer sign in. Their games are untouched.');

        $this->back('/admin/users/' . $user['id']);
    }

    /**
     * Confirms an address by hand.
     *
     * For the buyer whose code never arrives - a mailbox that bounces our mail,
     * a typo in the address they registered with - rather than leaving them
     * stuck on the code screen with a paid plan they cannot reach.
     */
    public function verifyEmail(Request $request, array $params): void
    {
        $user = $this->findUser((int) ($params['id'] ?? 0));

        if (!empty($user['email_verified_at'])) {
            Flash::info('That address was already confirmed.');
            $this->back('/admin/users/' . $user['id']);
            return;
        }

        LoginCode::markVerified((int) $user['id']);

        Flash::success($user['name'] . ' can now sign in without the emailed code.');
        $this->back('/admin/users/' . $user['id']);
    }

    /**
     * Deletes an account and everything it owns.
     *
     * Deactivating is nearly always the better answer, and the screen says so:
     * this cannot be undone, and it takes the buyer's games with it. It is here
     * for the cases that need it - a duplicate account, or somebody asking to
     * be removed.
     */
    public function destroy(Request $request, array $params): void
    {
        $user = $this->findUser((int) ($params['id'] ?? 0));
        $id   = (int) $user['id'];

        if ($id === $this->userId()) {
            Flash::error('You cannot delete your own account.');
            $this->back('/admin/users/' . $id);
            return;
        }

        if ($this->isLastAdmin($id)) {
            Flash::error('That is the only administrator left. Promote somebody else first.');
            $this->back('/admin/users/' . $id);
            return;
        }

        // Typing the email address is the confirmation - a misplaced click on a
        // list of hundreds should not be able to do this
        if (strtolower($request->str('confirm')) !== strtolower((string) $user['email'])) {
            Flash::error('The email address did not match, so nothing was deleted.');
            $this->back('/admin/users/' . $id);
            return;
        }

        // Uploaded files live on disk, so they go first - a row deleted before
        // its file leaves the file behind with nothing pointing at it
        foreach (Database::all('SELECT id FROM user_assets WHERE user_id = ?', [$id]) as $asset) {
            Uploader::delete((int) $asset['id'], $id);
        }

        Database::transaction(function () use ($id) {
            $projectIds = array_map(
                static fn (array $r): int => (int) $r['id'],
                Database::all('SELECT id FROM projects WHERE user_id = ?', [$id])
            );

            foreach ($projectIds as $projectId) {
                Database::delete('project_missions', ['project_id' => $projectId]);
                Database::delete('project_players', ['project_id' => $projectId]);
            }

            Database::delete('projects', ['user_id' => $id]);
            Database::delete('exports', ['user_id' => $id]);
            Database::delete('listings', ['user_id' => $id]);
            Database::delete('user_assets', ['user_id' => $id]);
            Database::delete('password_resets', ['user_id' => $id]);
            Database::delete('login_codes', ['user_id' => $id]);
            Database::delete('plan_events', ['user_id' => $id]);

            // Community posts are public and outlive the account
            Database::run('UPDATE community_posts SET user_id = NULL WHERE user_id = ?', [$id]);
            Database::run('UPDATE plan_events SET actor_id = NULL WHERE actor_id = ?', [$id]);
            Database::run('UPDATE access_links SET created_by = NULL WHERE created_by = ?', [$id]);

            Database::delete('users', ['id' => $id]);
        });

        Flash::success($user['name'] . ' and everything on that account have been deleted.');
        Response::redirect('/admin/users');
    }

    // -----------------------------------------------------------------

    private function findUser(int $id): array
    {
        $row = Database::first('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);

        if (!$row) {
            Response::abort(404, 'No such account.');
        }

        return $row;
    }

    /** Would removing this account's rights leave the site with no administrator? */
    private function isLastAdmin(int $id): bool
    {
        if ((string) Database::value('SELECT role FROM users WHERE id = ?', [$id]) !== 'admin') {
            return false;
        }

        return Database::count("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1") <= 1;
    }
}
