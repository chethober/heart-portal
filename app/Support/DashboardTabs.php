<?php

namespace App\Support;

use App\Enums\HospitalStatus;
use App\Enums\StudyStatus;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\User;

/**
 * The dashboard's section tabs a user may open, each with an optional
 * "needs attention" count (drafts, pending invitations, hospitals to approve).
 */
final class DashboardTabs
{
    /**
     * @return list<array{label: string, url: string, active: bool, count: int|null}>
     */
    public static function for(User $user): array
    {
        if ($user->isPlatformAdmin()) {
            return [
                self::tab(__('Hospitals'), 'admin.hospitals.*', Hospital::where('status', HospitalStatus::Pending)->count()),
            ];
        }

        $tabs = [];

        if ($user->can('patients.view')) {
            $tabs[] = self::tab(__('Worklist'), 'dashboard', EchoStudy::where('status', StudyStatus::Draft)->count());
            $tabs[] = self::tab(__('Patient echos'), 'patients.index');
        }
        if ($user->can('staff.manage')) {
            $tabs[] = self::tab(__('Staff'), 'staff.*', Invitation::pending()->count());
        }
        if ($user->can('audit.view')) {
            $tabs[] = self::tab(__('Audit log'), 'audit.*');
        }

        return $tabs;
    }

    /**
     * @return array{label: string, url: string, active: bool, count: int|null}
     */
    private static function tab(string $label, string $route, ?int $count = null): array
    {
        $name = str_replace('.*', '.index', $route);

        return [
            'label' => $label,
            'url' => route($name),
            'active' => request()->routeIs($route),
            'count' => $count ?: null,
        ];
    }
}
