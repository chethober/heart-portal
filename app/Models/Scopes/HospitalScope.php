<?php

namespace App\Models\Scopes;

use App\Support\CurrentHospital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class HospitalScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $hospitalId = app(CurrentHospital::class)->id();

        if ($hospitalId !== null) {
            $builder->where($model->qualifyColumn('hospital_id'), $hospitalId);

            return;
        }

        // Fail closed: without a tenant context nothing is visible, except to
        // trusted console code (migrations, seeders, imports) outside tests.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $builder->whereRaw('1 = 0');
    }
}
