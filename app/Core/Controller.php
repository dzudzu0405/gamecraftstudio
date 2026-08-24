<?php
namespace App\Core;

/** Base class for every controller */
abstract class Controller
{
    /** Renders a view inside a layout */
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        View::display($view, $data, $layout);
    }

    protected function json($data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function redirect(string $path): void
    {
        Response::redirect($path);
    }

    protected function back(string $fallback = '/'): void
    {
        Response::back($fallback);
    }

    /** Returns to the form carrying the errors and what was typed */
    protected function backWithErrors(array $errors, array $old, string $fallback = '/'): void
    {
        Flash::putErrors($errors);
        Flash::keepOld($old);
        $this->back($fallback);
    }

    protected function user(): ?array
    {
        return Auth::user();
    }

    protected function userId(): int
    {
        return (int) Auth::id();
    }

    /** Loads a project owned by the current user, or 404s */
    protected function ownedProject(int $id): array
    {
        $row = Database::first(
            'SELECT * FROM projects WHERE id = :id AND user_id = :uid LIMIT 1',
            ['id' => $id, 'uid' => $this->userId()]
        );
        if (!$row) {
            Response::abort(404, 'No such project in your account.');
        }
        return $row;
    }
}
