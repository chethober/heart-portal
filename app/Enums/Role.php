<?php

namespace App\Enums;

use App\Support\Forms;

enum Role: string
{
    case PlatformAdmin = 'platform_admin';
    case HospitalAdmin = 'hospital_admin';
    case Doctor = 'doctor';
    case Technician = 'technician';
    // Roles from the heart system: ward nurses and registry quality control record studies too.
    case Nurse = 'nurse';
    case QualityControl = 'quality_control';

    public function label(): string
    {
        return match ($this) {
            self::PlatformAdmin => __('Platform admin'),
            self::HospitalAdmin => __('Hospital admin'),
            self::Doctor => __('Doctor'),
            self::Technician => __('Echo technician'),
            self::Nurse => __('Nurse'),
            self::QualityControl => __('Quality control'),
        };
    }

    /**
     * Roles a hospital admin may hand out to their own staff.
     *
     * @return list<self>
     */
    public static function assignableByHospital(): array
    {
        return [self::HospitalAdmin, self::Doctor, self::Technician, self::Nurse, self::QualityControl];
    }

    /**
     * Permissions that come with the role. Access to each clinical form is granted per user
     * instead (see formCeiling() and defaultFormPermissions()).
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        $clinical = ['patients.view', 'patients.edit', 'prescriptions.create', 'prescriptions.edit'];

        return match ($this) {
            self::PlatformAdmin => ['hospitals.manage'],
            self::HospitalAdmin => ['staff.manage', 'hospital.settings', 'audit.view', 'patients.view'],
            self::Doctor => [...$clinical, 'prescriptions.sign'],
            self::Technician, self::Nurse => $clinical,
            self::QualityControl => [...$clinical, 'audit.view'],
        };
    }

    /**
     * The most form access a member with this role can be granted. As before per-form
     * permissions, only doctors sign reports and hospital admins only read them.
     *
     * @return list<string>
     */
    public function formCeiling(): array
    {
        return match ($this) {
            self::PlatformAdmin => [],
            self::HospitalAdmin => Forms::permissionsFor(['view']),
            self::Doctor => Forms::permissions(),
            self::Technician, self::Nurse, self::QualityControl => Forms::permissionsFor(['view', 'create', 'edit']),
        };
    }

    /**
     * Form access a new member with this role starts with: everything the role allows.
     *
     * @return list<string>
     */
    public function defaultFormPermissions(): array
    {
        return $this->formCeiling();
    }
}
