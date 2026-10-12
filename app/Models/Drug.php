<?php

namespace App\Models;

use App\Models\Scopes\DrugCatalogScope;
use App\Support\Prescribing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A catalog drug (heart's ep_drugs). Shared drugs have no hospital and are maintained by the
 * DrugCatalogSeeder; a hospital admin may add drugs only their hospital sees. The catalog holds
 * no patient data, so instead of BelongsToHospital it is scoped by DrugCatalogScope.
 *
 * @property int $id
 * @property int|null $hospital_id
 * @property string $generic_name
 * @property string|null $local_name
 * @property string $form
 * @property string|null $strength
 * @property bool $is_active
 */
#[Fillable(['hospital_id', 'code', 'generic_name', 'local_name', 'form', 'strength', 'default_dose', 'default_unit', 'default_route', 'default_frequency', 'is_active', 'legacy_id'])]
class Drug extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new DrugCatalogScope);

        static::saving(function (Drug $drug) {
            $drug->search_text = Prescribing::normalize(implode(' ', array_filter([$drug->generic_name, $drug->local_name, $drug->strength, $drug->form])));
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<Hospital, $this>
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function isShared(): bool
    {
        return $this->hospital_id === null;
    }

    /**
     * "Atorvastatin 40 mg tablet".
     */
    public function displayName(): string
    {
        return trim("{$this->generic_name} {$this->strength} {$this->form}");
    }

    /**
     * Every word of the query must appear in the drug's names, strength or form, as in heart.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, string $terms): void
    {
        $words = array_slice(array_filter(explode(' ', Prescribing::normalize($terms))), 0, 5);

        foreach ($words as $word) {
            $query->where('search_text', 'like', '%'.addcslashes($word, '%_\\').'%');
        }

        // Drugs whose name starts with the first word come first.
        if ($words !== []) {
            $query->orderByRaw('case when search_text like ? then 0 else 1 end', [addcslashes($words[0], '%_\\').'%']);
        }
    }
}
