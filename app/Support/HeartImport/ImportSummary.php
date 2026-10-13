<?php

namespace App\Support\HeartImport;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Per-table counts of an import run, and the rows that failed.
 */
final class ImportSummary
{
    public const COLUMNS = ['read', 'created', 'updated', 'skipped', 'errors', 'warnings'];

    /**
     * @var array<string, array<string, int>>
     */
    private array $counts = [];

    /**
     * @var list<string>
     */
    private array $messages = [];

    public function __construct(private readonly string $source) {}

    public function add(string $table, string $column, int $by = 1): void
    {
        $this->counts[$table] ??= array_fill_keys(self::COLUMNS, 0);
        $this->counts[$table][$column] += $by;
    }

    /**
     * A row that could not be imported. Logged without field values, which may identify a patient.
     */
    public function error(string $table, int|string|null $legacyId, string|Throwable $problem): void
    {
        $this->add($table, 'errors');
        $message = $problem instanceof Throwable ? class_basename($problem).': '.$problem->getMessage() : $problem;
        $this->messages[] = "{$table} #{$legacyId}: {$message}";

        Log::warning("{$this->source} import: {$table} row could not be imported", [
            'table' => $table,
            'legacy_id' => $legacyId,
            'problem' => $message,
        ]);
    }

    /**
     * A value that was dropped (e.g. an unreadable date); the row itself was imported.
     */
    public function warning(string $table, int|string|null $legacyId, string $problem): void
    {
        $this->add($table, 'warnings');
        $this->messages[] = "{$table} #{$legacyId}: warning: {$problem}";
    }

    public function note(string $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function counts(): array
    {
        return $this->counts;
    }

    public function count(string $table, string $column): int
    {
        return $this->counts[$table][$column] ?? 0;
    }

    /**
     * @return list<string>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * @return list<list<int|string>>
     */
    public function rows(): array
    {
        $rows = [];

        foreach ($this->counts as $table => $counts) {
            $rows[] = [$table, ...array_values($counts)];
        }

        return $rows;
    }
}
