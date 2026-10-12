<?php

namespace App\Models\Scopes;

use App\Support\CurrentHospital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * The drug catalog a hospital sees: the shared drugs plus its own additions. Without a
 * hospital context only the shared drugs are visible, never another hospital's.
 */
class DrugCatalogScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $hospitalId = app(CurrentHospital::class)->id();
        $column = $model->qualifyColumn('hospital_id');

        $builder->where(fn (Builder $query) => $hospitalId === null
            ? $query->whereNull($column)
            : $query->whereNull($column)->orWhere($column, $hospitalId));
    }
}
