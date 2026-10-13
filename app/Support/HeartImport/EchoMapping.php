<?php

namespace App\Support\HeartImport;

/**
 * Maps a row of heart's `echo` table to echo_studies columns, using the maps
 * in config/heart.php (echo) and the column types in config/echo.php.
 */
final class EchoMapping
{
    /**
     * Heart's "none" (-1) and "Missing" (9) placeholders.
     */
    private const BLANK = ['-1', '9'];

    /**
     * @var list<string>
     */
    private array $warnings = [];

    /**
     * @return array<string, mixed> echo_studies column => value, only for columns heart's row has
     */
    public function map(LegacyRow $row): array
    {
        $this->warnings = [];
        $map = config('heart.echo');
        $types = [...config('echo.fields'), 'echo_date' => 'date'];
        $values = [];
        $others = [];
        $notes = [];

        $columns = array_unique([...array_keys($types), ...array_keys($map['renames']), ...array_keys($map['choices'])]);

        foreach ($columns as $column) {
            $target = $map['renames'][$column] ?? $column;

            if (! isset($types[$target]) || ! $row->has($column)) {
                continue;
            }

            $raw = $row->get($column);

            if (isset($map['choices'][$column])) {
                [$label, $extra, $codes] = $map['choices'][$column];
                $code = LegacyValue::code($raw);
                $entry = null;

                if ($code !== null && array_key_exists($code, $codes)) {
                    $entry = $codes[$code];
                } elseif ($code !== null && ! in_array($code, self::BLANK, true)) {
                    $this->warnings[] = "{$column} has no mapping for code {$code}";
                }

                if (is_array($entry)) {
                    [$entry, $text] = $entry;
                    if ($entry === 'Other') {
                        $others[$extra] = $text;
                    } else {
                        $notes[$extra][] = "{$label}: {$text}";
                    }
                }

                $values[$target] = $entry;

                continue;
            }

            $values[$target] = match ($types[$target]) {
                'number' => LegacyValue::number($raw),
                'boolean' => LegacyValue::boolean($raw),
                'date' => LegacyValue::date($raw),
                default => LegacyValue::string($raw),
            };

            if ($values[$target] === null && LegacyValue::text($raw) !== null) {
                $this->warnings[] = "{$column} value could not be read as {$types[$target]}";
            }
        }

        foreach ($map['notes'] as $column => $note) {
            [$extra, $label] = $note;
            $text = LegacyValue::string($row->get($column));

            if ($text !== null && isset($note[2])) {
                $text = $note[2][LegacyValue::code($text)] ?? null;
            }

            if ($text !== null) {
                $notes[$extra][] = "{$label}: {$text}";
            }
        }

        foreach ($others as $column => $text) {
            $values[$column] = isset($values[$column]) ? "{$text}; {$values[$column]}" : $text;
        }

        foreach ($notes as $column => $lines) {
            $values[$column] = implode("\n", array_filter([$values[$column] ?? null, ...$lines]));
        }

        return $values;
    }

    /**
     * Values of the last mapped row that were dropped.
     *
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }
}
