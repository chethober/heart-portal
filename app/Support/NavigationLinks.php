<?php

namespace App\Support;

use App\Enums\HospitalStatus;
use App\Enums\StudyStatus;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\User;

/**
 * The dashboard sections a user may open, shown in the top navigation, each with an
 * optional "needs attention" count (drafts, pending invitations, hospitals to approve).
 */
final class NavigationLinks
{
    /**
     * @return list<array{label: string, url: string, active: bool, count: int|null, children?: list<array{label: string, url: string, active: bool}>}>
     */
    public static function for(User $user): array
    {
        if ($user->isPlatformAdmin()) {
            return [
                self::link(__('Hospitals'), 'admin.hospitals.*', Hospital::where('status', HospitalStatus::Pending)->count()),
            ];
        }

        $links = [];

        $echo = Forms::allows($user, 'echo', 'create');
        $procedures = Forms::procedures($user, 'create');
        if ($echo || $procedures !== []) {
            // The dashboard home is the echo form; the other forms come from the heart system.
            $links[] = [
                ...self::link(__('New Study'), [$echo ? 'dashboard' : 'procedures.create', 'studies.create', 'procedures.create'], routeParameters: $echo ? [] : [$procedures[0]]),
                'children' => [
                    ...($echo ? [['label' => __('Echocardiography'), 'url' => route('studies.create'), 'active' => request()->routeIs('dashboard', 'studies.create')]] : []),
                    ...array_map(fn (string $key) => [
                        'label' => __(Procedures::label($key)),
                        'url' => route('procedures.create', $key),
                        'active' => request()->routeIs('procedures.create') && request()->route('procedure') === $key,
                    ], $procedures),
                ],
            ];
        }
        if ($user->can('patients.view')) {
            // Drafts of the forms this member may open.
            $drafts = Forms::allows($user, 'echo', 'view') ? EchoStudy::where('status', StudyStatus::Draft)->count() : 0;
            foreach (Forms::procedures($user, 'view') as $key) {
                $drafts += Procedures::model($key)::where('status', StudyStatus::Draft)->count();
            }
            $links[] = self::link(__('Worklist'), 'worklist', $drafts);
            $links[] = self::link(__('Patients'), ['patients.index', 'patients.show', 'patients.edit', 'admissions.*']);
            $links[] = self::link(__('Statistics'), 'statistics');
            $links[] = self::link(__('Drugs'), 'drugs.index');
        }
        if ($user->can('staff.manage')) {
            $links[] = self::link(__('Staff'), 'staff.*', Invitation::pending()->count());
        }
        if ($user->can('hospital.settings')) {
            $links[] = self::link(__('Wards'), 'wards.*');
        }
        if ($user->can('audit.view')) {
            $links[] = self::link(__('Audit log'), 'audit.*');
        }

        return $links;
    }

    /**
     * @param  string|list<string>  $routes  The first route is the link target; any of them marks it active.
     * @param  array<int|string, mixed>  $routeParameters  For the link target.
     * @return array{label: string, url: string, active: bool, count: int|null}
     */
    private static function link(string $label, string|array $routes, ?int $count = null, array $routeParameters = []): array
    {
        $routes = (array) $routes;

        return [
            'label' => $label,
            'url' => route(str_replace('.*', '.index', $routes[0]), $routeParameters),
            'active' => request()->routeIs(...$routes),
            'count' => $count ?: null,
        ];
    }
}
