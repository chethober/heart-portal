@php
    /**
     * Report body of a procedure without a narrative builder: every filled field, section by
     * section, with the form's captions. Printed inside the shared template (report/document).
     *
     * @var string $procedure
     * @var array<string, mixed> $report
     */
    use App\Support\Diagnoses;
    use App\Support\Procedures;

    $display = function (array $field, mixed $value): ?string {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return match ($field['type']) {
            'boolean' => __('Yes'),
            'choice' => $field['options'][$value] ?? (string) $value,
            'number' => is_float($value) && floor($value) === $value ? (string) (int) $value : (string) $value,
            'diagnoses' => is_array($value) ? (implode('; ', Diagnoses::labels($value)) ?: null) : (string) $value,
            default => (string) $value,
        };
    };
    $fieldSections = collect(Procedures::sections($procedure))
        ->map(fn (array $section) => [
            'title' => $section['title'],
            'rows' => collect($section['fields'])
                ->map(fn (array $field, string $name) => ['label' => $field['label'], 'value' => $display($field, $report[$name] ?? null)])
                ->filter(fn (array $row) => $row['value'] !== null)
                ->values(),
        ])
        ->filter(fn (array $section) => $section['rows']->isNotEmpty());
@endphp

@forelse ($fieldSections as $section)
    <div class="section">
        <div class="section-title">{{ $section['title'] }}</div>
        <div class="table-responsive">
            <table>
                <tbody>
                    @foreach ($section['rows']->chunk(2) as $pair)
                        <tr>
                            @foreach ($pair as $row)
                                <td style="width: 25%; font-weight: 600; color: #2c3e50;">{{ $row['label'] }}</td>
                                <td style="width: 25%; white-space: pre-line;" dir="auto">{{ $row['value'] }}</td>
                            @endforeach
                            @if ($pair->count() === 1)
                                <td style="width: 25%"></td><td style="width: 25%"></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="section"><p style="color: #6b7280;">{{ __('No findings recorded yet.') }}</p></div>
@endforelse
