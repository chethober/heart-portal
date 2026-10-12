<?php

namespace App\Support\HeartImport;

/**
 * A row read from heart or the HIS, with case-insensitive column access:
 * heart's code writes NationalCode to a column named nationalcode.
 */
final class LegacyRow
{
    /**
     * @var array<string, mixed>
     */
    private array $values;

    /**
     * @param  array<string, mixed>|object  $row
     */
    public function __construct(array|object $row)
    {
        $this->values = array_change_key_case((array) $row, CASE_LOWER);
    }

    /**
     * Whether the source table has the column at all. A missing column is
     * left alone on import, unlike a column that is null.
     */
    public function has(string $column): bool
    {
        return array_key_exists(strtolower($column), $this->values);
    }

    public function get(string $column): mixed
    {
        return $this->values[strtolower($column)] ?? null;
    }

    /**
     * The first of the columns that holds a value.
     */
    public function first(string ...$columns): mixed
    {
        foreach ($columns as $column) {
            if (LegacyValue::text($this->get($column)) !== null) {
                return $this->get($column);
            }
        }

        return null;
    }
}
