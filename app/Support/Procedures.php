<?php

namespace App\Support;

use App\Models\AngiographyStudy;
use App\Models\AngioplastyStudy;
use App\Models\DischargeStudy;
use App\Models\InterventionStudy;
use App\Models\PeripheralStudy;
use App\Models\ProcedureStudy;
use App\Models\RhcStudy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clinical procedures ported from the heart system. Each one has a model, a
 * {key}_studies table and a field list in config/procedures/{key}.php.
 */
final class Procedures
{
    /**
     * @var array<string, class-string<ProcedureStudy>>
     */
    public const MODELS = [
        'angiography' => AngiographyStudy::class,
        'angioplasty' => AngioplastyStudy::class,
        'rhc' => RhcStudy::class,
        'peripheral' => PeripheralStudy::class,
        'intervention' => InterventionStudy::class,
        'discharge' => DischargeStudy::class,
    ];

    /**
     * @return class-string<ProcedureStudy>
     */
    public static function model(string $key): string
    {
        return self::MODELS[$key] ?? abort(404);
    }

    public static function label(string $key): string
    {
        return config("procedures.{$key}.label");
    }

    /**
     * @return list<array{title: string, fields: array<string, array<string, mixed>>}>
     */
    public static function sections(string $key): array
    {
        return config("procedures.{$key}.sections");
    }

    /**
     * Every field of the procedure, keyed by column name.
     *
     * @return array<string, array{type: string, label: string, group?: string, options?: array<string, string>, blank?: list<string>, show_if?: array{0: string, 1: string}, legacy?: string}>
     */
    public static function fields(string $key): array
    {
        return array_merge(...array_column(self::sections($key), 'fields'));
    }

    /**
     * Creates {key}_studies: the study workflow columns plus one column per field.
     */
    public static function createTable(string $key): void
    {
        Schema::create("{$key}_studies", function (Blueprint $table) use ($key) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('amended_at')->nullable();
            $table->text('amendment_reason')->nullable();
            // Row id in heart's table for this procedure, for imported studies.
            $table->unsignedBigInteger('legacy_id')->nullable();

            self::addColumns($table, $key);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['hospital_id', 'patient_id', 'created_at']);
            $table->index(['hospital_id', 'status']);
            $table->unique(['hospital_id', 'legacy_id']);
        });
    }

    /**
     * Writing direction of the procedure's form and report (heart's Persian forms are rtl).
     */
    public static function dir(string $key): string
    {
        return config("procedures.{$key}.dir", 'ltr');
    }

    /**
     * Adds a nullable column for each field; used by the procedure's migration.
     */
    public static function addColumns(Blueprint $table, string $key): void
    {
        foreach (self::fields($key) as $column => $field) {
            match ($field['type']) {
                'number' => $table->double($column)->nullable(),
                'boolean' => $table->boolean($column)->nullable(),
                'date' => $table->string($column, 10)->nullable(),
                'time' => $table->string($column, 5)->nullable(),
                default => $table->text($column)->nullable(),
            };
        }
    }

    /**
     * The model cast for a field's stored value.
     */
    public static function cast(string $type): string
    {
        return match ($type) {
            'number' => 'float',
            'boolean' => 'boolean',
            default => 'string',
        };
    }
}
