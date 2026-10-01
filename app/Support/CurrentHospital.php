<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Holds the hospital (tenant) that the current request or command acts on.
 *
 * Web requests resolve it from the authenticated user; console commands
 * such as the legacy importer set it explicitly with set().
 */
class CurrentHospital
{
    private ?int $id = null;

    public function set(?int $hospitalId): void
    {
        $this->id = $hospitalId;
    }

    public function id(): ?int
    {
        return $this->id ?? Auth::user()?->hospital_id;
    }
}
