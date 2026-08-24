<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Art;
use App\Services\Difficulty;

/** Community inspiration - FR-19, FR-20 */
class CommunityController extends Controller
{
    public function index(Request $request): void
    {
        $theme = $request->str('theme');
        $sort  = $request->str('sort', 'popular');

        $sql    = 'SELECT * FROM community_posts WHERE 1 = 1';
        $params = [];

        if ($theme !== '' && isset(Art::THEMES[$theme])) {
            $sql .= ' AND theme = ?';
            $params[] = $theme;
        }

        $sql .= $sort === 'new'
            ? ' ORDER BY created_at DESC, id DESC'
            : ' ORDER BY is_featured DESC, likes DESC, id DESC';

        $this->view('community/index', [
            'pageTitle' => 'Community',
            'posts'     => Database::all($sql, $params),
            'themes'    => Art::THEMES,
            'theme'     => $theme,
            'sort'      => $sort,
            'liked'     => Session::get('liked_posts', []),
        ]);
    }

    /** Likes a post. Kept in the session so each visit can only like once */
    public function like(Request $request, array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $post = Database::first('SELECT * FROM community_posts WHERE id = ? LIMIT 1', [$id]);

        if (!$post) {
            Response::abort(404, 'That post no longer exists.');
        }

        $liked = Session::get('liked_posts', []);

        if (in_array($id, $liked, true)) {
            Database::run('UPDATE community_posts SET likes = CASE WHEN likes > 0 THEN likes - 1 ELSE 0 END WHERE id = ?', [$id]);
            $liked = array_values(array_diff($liked, [$id]));
        } else {
            Database::run('UPDATE community_posts SET likes = likes + 1 WHERE id = ?', [$id]);
            $liked[] = $id;
        }

        Session::put('liked_posts', $liked);
        $this->back('/community');
    }
}
