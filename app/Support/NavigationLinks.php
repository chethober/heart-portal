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
     * @return list<array{label: string, url: string, active: bool, count: int|null}>
     */
    public static function for(User $user): array
    {
        if ($user->isPlatformAdmin()) {
            return [
                self::link(__('Hospitals'), 'admin.hospitals.*', Hospital::where('status', HospitalStatus::Pending)->count()),
            ];
        }

        $links = [];

        if ($user->can('studies.create')) {
            // The dashboard home is the echo form.
            $links[] = self::link(__('New Study'), ['dashboard', 'studies.create']);
        }
        if ($user->can('patients.view')) {
            $links[] = self::link(__('Worklist'), 'worklist', EchoStudy::where('status', StudyStatus::Draft)->count());
            $links[] = self::link(__('Patients'), 'patients.index');
        }
        if ($user->can('staff.manage')) {
            $links[] = self::link(__('Staff'), 'staff.*', Invitation::pending()->count());
        }
        if ($user->can('audit.view')) {
            $links[] = self::link(__('Audit log'), 'audit.*');
        }

        return $links;
    }

    /**
     * @param  string|list<string>  $routes  The first route is the link target; any of them marks it active.
     * @return array{label: string, url: string, active: bool, count: int|null}
     */
    private static function link(string $label, string|array $routes, ?int $count = null): array
    {
        $routes = (array) $routes;

        return [
            'label' => $label,
            'url' => route(str_replace('.*', '.index', $routes[0])),
            'active' => request()->routeIs(...$routes),
            'count' => $count ?: null,
        ];
    }
}
