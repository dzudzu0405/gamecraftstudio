<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Services\AccessLink;
use App\Services\Tiers;

/**
 * The WarriorPlus delivery links.
 *
 * One link per product sold. The address it produces goes in the WarriorPlus
 * product setup as the delivery URL, and opening it raises whichever account
 * is signed in to that link's plan.
 *
 * Rotating a code is the fix for a link that has got out: the account keeps
 * everything it already redeemed, the old address stops working immediately,
 * and the new one goes back into WarriorPlus. That is safer than deleting the
 * link, which would take its redemption history with it.
 */
class LinkController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/links', [
            'pageTitle' => 'Access links',
            'links'     => AccessLink::all(),
            'counts'    => AccessLink::redemptionCounts(),
            'tiers'     => Tiers::all(),
        ]);
    }

    public function store(Request $request): void
    {
        $label = $request->str('label');
        $plan  = $request->str('plan');

        if ($label === '') {
            Flash::error('Give the link a name, so you can tell it apart from the others later.');
            $this->back('/admin/links');
            return;
        }

        if (!Tiers::exists($plan)) {
            Flash::error('Choose which plan the link hands out.');
            $this->back('/admin/links');
            return;
        }

        $now = date('Y-m-d H:i:s');

        Database::insert('access_links', [
            'code'       => AccessLink::generateCode(),
            'label'      => mb_substr($label, 0, 120),
            'plan'       => $plan,
            'note'       => $this->noteOrNull($request->str('note')),
            'max_uses'   => max(0, $request->int('max_uses', 0)),
            'uses'       => 0,
            'expires_at' => $this->expiryOrNull($request->str('expires_at')),
            'is_active'  => 1,
            'created_by' => $this->userId(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Flash::success('Link created. Copy the address into the WarriorPlus product as its delivery URL.');
        Response::redirect('/admin/links');
    }

    public function update(Request $request, array $params): void
    {
        $link = $this->findLink((int) ($params['id'] ?? 0));
        $plan = $request->str('plan');

        $data = [
            'label'      => mb_substr($request->str('label', (string) $link['label']), 0, 120),
            'note'       => $this->noteOrNull($request->str('note')),
            'max_uses'   => max(0, $request->int('max_uses', 0)),
            'expires_at' => $this->expiryOrNull($request->str('expires_at')),
            'is_active'  => $request->bool('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (Tiers::exists($plan)) {
            $data['plan'] = $plan;
        }

        Database::update('access_links', $data, ['id' => (int) $link['id']]);

        Flash::success('Saved.');
        Response::redirect('/admin/links');
    }

    /** New code, same link. The old address stops working at once. */
    public function rotate(Request $request, array $params): void
    {
        $link = $this->findLink((int) ($params['id'] ?? 0));

        Database::update('access_links', [
            'code'       => AccessLink::generateCode(),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $link['id']]);

        Flash::success('New address created for "' . $link['label']
            . '". The old one no longer works, so update the WarriorPlus product now.');

        Response::redirect('/admin/links');
    }

    public function destroy(Request $request, array $params): void
    {
        $link = $this->findLink((int) ($params['id'] ?? 0));

        // The history stays readable: plan_events keeps pointing at an id that
        // no longer resolves to a label, so switching off is the better answer
        // whenever anybody has actually redeemed it
        $used = Database::count('SELECT COUNT(*) FROM plan_events WHERE link_id = ?', [(int) $link['id']]);

        if ($used > 0) {
            Flash::error('That link has been used ' . $used . ' time' . ($used === 1 ? '' : 's')
                . ', so deleting it would break the plan history. Switch it off instead.');
            $this->back('/admin/links');
            return;
        }

        Database::delete('access_links', ['id' => (int) $link['id']]);

        Flash::success('Link deleted.');
        Response::redirect('/admin/links');
    }

    /** Every account this link has raised, newest first */
    public function redemptions(Request $request, array $params): void
    {
        $link = $this->findLink((int) ($params['id'] ?? 0));

        $rows = Database::all(
            'SELECT e.*, u.name AS user_name, u.email AS user_email, u.plan AS user_plan, u.is_active
               FROM plan_events e
          LEFT JOIN users u ON u.id = e.user_id
              WHERE e.link_id = ?
           ORDER BY e.created_at DESC, e.id DESC
              LIMIT 500',
            [(int) $link['id']]
        );

        $this->view('admin/link-uses', [
            'pageTitle' => $link['label'],
            'link'      => $link,
            'rows'      => $rows,
        ]);
    }

    // -----------------------------------------------------------------

    private function findLink(int $id): array
    {
        $link = AccessLink::findById($id);

        if (!$link) {
            Response::abort(404, 'No such access link.');
        }

        return $link;
    }

    private function noteOrNull(string $note): ?string
    {
        $note = trim($note);
        return $note === '' ? null : mb_substr($note, 0, 255);
    }

    /** The date input gives 'YYYY-MM-DD'; an empty box means no expiry at all */
    private function expiryOrNull(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);

        if ($ts === false) {
            return null;
        }

        // A date with no time means the whole of that day is still good
        return date('Y-m-d H:i:s', strtotime('23:59:59', $ts));
    }
}
