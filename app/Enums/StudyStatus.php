<?php

namespace App\Enums;

enum StudyStatus: string
{
    case Draft = 'draft';
    case Final = 'final';
    case Amended = 'amended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Final => 'Final',
            self::Amended => 'Amended',
        };
    }

    /**
     * Final and amended reports are signed; changing them requires an amendment.
     */
    public function isSigned(): bool
    {
        return $this !== self::Draft;
    }
}
