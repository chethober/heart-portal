<?php

namespace App\Enums;

enum Role: string
{
    case PlatformAdmin = 'platform_admin';
    case HospitalAdmin = 'hospital_admin';
    case Doctor = 'doctor';
    case Technician = 'technician';

    public function label(): string
    {
        return match ($this) {
            self::PlatformAdmin => __('Platform admin'),
            self::HospitalAdmin => __('Hospital admin'),
            self::Doctor => __('Doctor'),
            self::Technician => __('Echo technician'),
        };
    }

    /**
     * Roles a hospital admin may hand out to their own staff.
     *
     * @return list<self>
     */
    public static function assignableByHospital(): array
    {
        return [self::HospitalAdmin, self::Doctor, self::Technician];
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::PlatformAdmin => ['hospitals.manage'],
            self::HospitalAdmin => ['staff.manage', 'hospital.settings', 'audit.view', 'patients.view'],
            self::Doctor => ['patients.view', 'studies.create', 'studies.edit', 'studies.finalize'],
            self::Technician => ['patients.view', 'studies.create', 'studies.edit'],
        };
    }
}
