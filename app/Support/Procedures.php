<?php

namespace App\Support;

use App\Models\AngiographyStudy;
use App\Models\ProcedureStudy;
use Illuminate\Database\Schema\Blueprint;

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
