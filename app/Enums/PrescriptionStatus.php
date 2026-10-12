<?php

namespace App\Enums;

enum PrescriptionStatus: string
{
    case Draft = 'draft';
    case Signed = 'signed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Signed => 'Signed',
            self::Cancelled => 'Cancelled',
        };
    }
}
