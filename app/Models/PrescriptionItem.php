<?php

namespace App\Models;

use App\Enums\PrescriptionStatus;
use App\Models\Concerns\BelongsToHospital;
use App\Support\Prescribing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One drug on a prescription (heart's ep_prescriptionitems). The drug's name, form and strength
 * are copied from the catalog, and free-text drugs not in the catalog are allowed, as in heart.
 *
 * @property int $id
 * @property int $prescription_id
 * @property int|null $drug_id
 * @property int $position
 * @property string $drug_name
 * @property string|null $form
 * @property string|null $strength
 * @property string|null $dose
 * @property string|null $unit
 * @property string|null $route
 * @property string|null $frequency
 * @property string|null $duration
 * @property int|null $quantity
 * @property string|null $instructions
 */
#[Fillable(['hospital_id', 'prescription_id', 'drug_id', 'position', 'drug_name', 'form', 'strength', 'dose', 'unit', 'route', 'frequency', 'duration', 'quantity', 'instructions'])]
class PrescriptionItem extends Model
{
    use BelongsToHospital;

    protected static function booted(): void
    {
        $guard = function (PrescriptionItem $item) {
            $status = Prescription::withoutGlobalScopes()->whereKey($item->prescription_id)->toBase()->value('status');

            if ($status !== null && $status !== PrescriptionStatus::Draft->value) {
                throw new LogicException('The drugs of a signed prescription cannot be changed.');
            }
        };

        static::saving($guard);
        static::deleting($guard);
    }

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'position' => 'integer'];
    }

    /**
     * @return BelongsTo<Prescription, $this>
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * @return BelongsTo<Drug, $this>
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    /**
     * "Atorvastatin 40 mg tablet".
     */
    public function drugLabel(): string
    {
        return trim("{$this->drug_name} {$this->strength} {$this->form}");
    }

    /**
     * "1 tablet Oral, Once daily, for 30 days" — how the item reads on the prescription.
     */
    public function directions(): string
    {
        $dose = trim("{$this->dose} {$this->unit}");
        $parts = array_filter([
            trim($dose.' '.Prescribing::routeLabel($this->route)),
            Prescribing::frequencyLabel($this->frequency),
            $this->duration ? "for {$this->duration}" : null,
        ]);

        return implode(', ', $parts);
    }
}
