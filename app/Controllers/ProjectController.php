<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Project;
use App\Services\Tiers;

/** My Projects - FR-09 to FR-16 */
class ProjectController extends Controller
{
    public function index(Request $request): void
    {
        $userId = $this->userId();

        // Remember the chosen view mode (grid / list) - FR-13
        $view = $request->str('view');
        if (in_array($view, ['grid', 'list'], true)) {
            Session::put('projects_view', $view);
        }
        $view = Session::get('projects_view', 'grid');

        $search = $request->str('q');
        $sort   = $request->str('sort', 'recent');
        $status = $request->str('status');

        if (!isset(Project::SORTS[$sort])) {
            $sort = 'recent';
        }

        $projects = Project::forUser($userId, [
            'search' => $search,
            'sort'   => $sort,
            'status' => $status,
        ]);

        $this->view('projects/index', [
            'pageTitle' => 'My projects',
            'projects'  => $projects,
            'total'     => Project::countForUser($userId),
            'view'      => $view,
            'search'    => $search,
            'sort'      => $sort,
            'status'    => $status,
            'canCreate' => Project::canCreate($userId, Auth::plan()),
            'limit'     => Tiers::projectLimit(Auth::plan()),
        ]);
    }

    /** Opening a project takes you straight to the Studio */
    public function show(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        Response::redirect('/studio/' . (int) $project['id']);
    }

    public function rename(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $title   = trim($request->str('title'));

        if ($title === '') {
            Flash::error('The game title cannot be empty.');
            $this->back('/projects');
            return;
        }

        Project::touch((int) $project['id'], ['title' => mb_substr($title, 0, 160)]);
        Flash::success('Project renamed.');
        $this->back('/projects');
    }

    public function setStatus(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $status  = $request->str('status');

        if (!in_array($status, Project::STATUSES, true)) {
            Flash::error('That is not a valid status.');
            $this->back('/projects');
            return;
        }

        $data = ['status' => $status];
        if ($status === 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        Project::touch((int) $project['id'], $data);
        Flash::success('Project status updated.');
        $this->back('/projects');
    }

    /** FR-14: duplicate a project */
    public function duplicate(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));

        if (!Project::canCreate($this->userId(), Auth::plan())) {
            Flash::warning('Your plan has reached its project limit. Upgrade to create more.');
            Response::redirect('/billing');
            return;
        }

        $newId = Project::duplicate((int) $project['id'], $this->userId());

        if ($newId === null) {
            Flash::error('That project could not be duplicated.');
            $this->back('/projects');
            return;
        }

        Flash::success('Project duplicated.');
        Response::redirect('/studio/' . $newId);
    }

    /** FR-14: delete a project */
    public function destroy(Request $request, array $params): void
    {
        $project = $this->ownedProject((int) ($params['id'] ?? 0));
        $title   = (string) $project['title'];

        Project::delete((int) $project['id'], $this->userId());

        Flash::success('Deleted the project "' . $title . '".');
        Response::redirect('/projects');
    }
}
