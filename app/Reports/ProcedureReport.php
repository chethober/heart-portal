<?php

namespace App\Reports;

use App\Support\Diagnoses;
use App\Support\JalaliDate;
use App\Support\Procedures;
use Illuminate\Support\Carbon;
use Illuminate\Support\Traits\Localizable;
use LogicException;

/**
 * Builds the clinical narrative of a procedure report, ported from heart's Handlebars report
 * templates (reports/templates/{procedure}.js). A builder only reads the report array (a study's
 * toReportArray() or a signed version's snapshot), so a signed version always reads as it was signed.
 *
 * Sections are lists of lines (a line is a sentence with an optional bold label), optionally with a
 * table above them (e.g. right heart pressures). Text is plain and
 * escaped by the view. Filled fields the narrative does not mention are listed in appendix(), so
 * nothing recorded on the form is silently left out of the printed report.
 *
 * Every report prints inside the same template (procedures/report/document.blade.php). The title,
 * section titles, line labels, table headers and signature boxes are written with __() in the
 * interface language; the sentences built from the form and its field captions and choices stay
 * in the form's clinical language (language()).
 */
abstract class ProcedureReport
{
    use Localizable;

    /**
     * Key in Procedures::MODELS.
     */
    public const PROCEDURE = '';

    /**
     * Procedures that have a narrative report; the others use the generic field listing.
     *
     * @var array<string, class-string<ProcedureReport>>
     */
    private const BUILDERS = [
        'angiography' => AngiographyReport::class,
        'angioplasty' => AngioplastyReport::class,
        'discharge' => DischargeReport::class,
        'rhc' => RhcReport::class,
        'peripheral' => PeripheralReport::class,
        'intervention' => InterventionReport::class,
    ];

    /**
     * Section titles and line labels of the narrative that make up the short summary another
     * form (the discharge summary's procedure results) extracts with summaryText(). A section
     * title takes all of its lines. A report may build its summary itself instead (buildSummary()).
     *
     * @var list<string>
     */
    protected const SUMMARY = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    protected readonly array $fields;

    /**
     * Fields the narrative has reported (or deliberately consumed).
     *
     * @var array<string, true>
     */
    private array $reported = [];

    /**
     * @var list<array{title: string|null, lines: list<array{label: string|null, text: string}>, table?: list<list<string>>}>|null
     */
    private ?array $sections = null;

    /**
     * @param  array<string, mixed>  $data  The report array (current values or a signed snapshot).
     */
    final public function __construct(protected readonly array $data)
    {
        $fields = Procedures::fields(static::PROCEDURE);
        // A Persian report uses the Persian wording a form in English keeps beside its own ("fa").
        $this->fields = $this->language() === 'fa' ? array_map(fn (array $field) => [
            ...$field,
            'label' => $field['fa'] ?? $field['label'],
            ...(isset($field['fa_options']) ? ['options' => $field['fa_options']] : []),
        ], $fields) : $fields;
    }

    /**
     * The narrative builder for a procedure, or null when it only has the generic report.
     *
     * @param  array<string, mixed>  $data
     */
    public static function for(string $procedure, array $data): ?self
    {
        $class = self::BUILDERS[$procedure] ?? null;

        return $class ? new $class($data) : null;
    }

    public static function supports(string $procedure): bool
    {
        return isset(self::BUILDERS[$procedure]);
    }

    /**
     * The report title, printed under the hospital name.
     */
    abstract public function title(): string;

    /**
     * The clinical language of the narrative's sentences and field captions: 'en' (left to right)
     * or 'fa' (right to left).
     */
    public function language(): string
    {
        return Procedures::dir(static::PROCEDURE) === 'rtl' ? 'fa' : 'en';
    }

    /**
     * Who signs the printed report, as heart's signature table listed them; the last box is the
     * signing doctor. A name is filled in when the form recorded it; a box with a "step" shows who
     * signed that sign-off step (ProcedureStudy::SIGNOFFS). Cath lab reports are signed by the
     * assistant and the attending physician.
     *
     * @return list<array{title: string, name: string|null, step?: string}>
     */
    public function signatureBoxes(): array
    {
        return [
            ['title' => __('Assistant physician'), 'name' => null],
            ['title' => __('Attending physician'), 'name' => null],
        ];
    }

    /**
     * Extra identification lines for the patient header (label => value): the registry code, on
     * forms that have one.
     *
     * @return array<string, string>
     */
    public function headerItems(): array
    {
        return $this->has('qID') ? array_filter([__('Registry code') => $this->text('qID')]) : [];
    }

    /**
     * @return list<array{title: string|null, lines: list<array{label: string|null, text: string}>, table?: list<list<string>>}>
     */
    abstract protected function build(): array;

    /**
     * @return list<array{title: string|null, lines: list<array{label: string|null, text: string}>, table?: list<list<string>>}>
     */
    final public function sections(): array
    {
        return $this->sections ??= array_values(array_filter(
            $this->build(),
            fn (array $section) => $section['lines'] !== [] || ! empty($section['table']),
        ));
    }

    /**
     * The narrative's key findings as plain text, for pasting into another report (the discharge
     * summary's procedure results). Empty when none of them is filled in. It is saved into that
     * form, so it is written in the clinical language whoever extracts it.
     */
    final public function summaryText(): string
    {
        return $this->withLocale($this->language(), fn () => trim((new static($this->data))->buildSummary()));
    }

    /**
     * By default the SUMMARY lines, one "Label: text" line each; with a single SUMMARY entry,
     * just its text.
     */
    protected function buildSummary(): string
    {
        $lines = [];
        $labelled = count(static::SUMMARY) > 1;
        $summary = array_map(fn (string $label) => __($label), static::SUMMARY);

        foreach ($this->sections() as $section) {
            $whole = $section['title'] !== null && in_array($section['title'], $summary, true);
            foreach ($section['lines'] as $line) {
                if ($whole || ($line['label'] !== null && in_array($line['label'], $summary, true))) {
                    $lines[] = $line['label'] !== null && ($labelled || $whole) ? $line['label'].': '.$line['text'] : $line['text'];
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Filled fields the narrative did not mention, by form section.
     *
     * @return list<array{title: string, items: list<array{label: string, value: string}>}>
     */
    final public function appendix(): array
    {
        // Everything the printed report shows, so only what it leaves out is listed here.
        $this->sections();
        $this->headerItems();
        $this->signatureBoxes();
        $appendix = [];

        foreach (Procedures::sections(static::PROCEDURE) as $section) {
            $items = [];
            foreach ($section['fields'] as $name => $field) {
                if (isset($this->reported[$name]) || ($value = $this->display($name)) === null) {
                    continue;
                }
                $items[] = ['label' => $this->fields[$name]['label'], 'value' => $value];
            }
            if ($items !== []) {
                $appendix[] = ['title' => $this->language() === 'fa' ? ($section['fa'] ?? $section['title']) : $section['title'], 'items' => $items];
            }
        }

        return $appendix;
    }

    // ------------------------------------------------------------------ field access

    /**
     * The stored value, or null when empty. Throws for a name that is not a field of the
     * procedure, so a misspelt heart field name fails loudly instead of printing nothing.
     */
    protected function raw(string $name): mixed
    {
        if (! isset($this->fields[$name])) {
            throw new LogicException(static::class." reads unknown field {$name}.");
        }

        $value = $this->data[$name] ?? null;

        return $value === '' || $value === [] || $value === false && $this->fields[$name]['type'] !== 'boolean' ? null : $value;
    }

    protected function has(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Whether the field holds the given choice code (compared as strings, since signed snapshots
     * are JSON and live values are model casts). A match counts as reported.
     */
    protected function is(string $name, string ...$codes): bool
    {
        $value = $this->raw($name);
        if ($value === null) {
            return false;
        }

        $value = is_bool($value) ? ($value ? '1' : '0') : $this->code($value);
        if (in_array($value, $codes, true)) {
            $this->reported[$name] = true;

            return true;
        }

        return false;
    }

    /**
     * A ticked box or a "Yes" (code 1) answer.
     */
    protected function yes(string $name): bool
    {
        return $this->is($name, '1');
    }

    /**
     * The field's text as the report prints it (choice label, formatted number, text), marking it
     * reported. Null when empty.
     */
    protected function text(string $name): ?string
    {
        $text = $this->display($name);
        if ($text !== null) {
            $this->reported[$name] = true;
        }

        return $text;
    }

    /**
     * text() for a field that only some sections of the form have (e.g. a vessel's take-off).
     */
    protected function optional(string $name): ?string
    {
        return $this->has($name) ? $this->text($name) : null;
    }

    /**
     * The field's label (its caption on the form), marking the field reported.
     */
    protected function caption(string $name): string
    {
        $this->raw($name);
        $this->reported[$name] = true;

        return $this->fields[$name]['label'];
    }

    /**
     * Captions of the fields answered "Yes" (or ticked).
     *
     * @param  list<string>  $names
     * @return list<string>
     */
    protected function yesCaptions(array $names, string $code = '1'): array
    {
        return array_values(array_map(
            fn (string $name) => $this->caption($name),
            array_filter($names, fn (string $name) => $this->is($name, $code)),
        ));
    }

    /**
     * Marks fields as covered by the narrative without printing them (e.g. suppressed on purpose).
     */
    protected function consume(string ...$names): void
    {
        foreach ($names as $name) {
            $this->raw($name);
            $this->reported[$name] = true;
        }
    }

    /**
     * Display text without marking the field reported.
     */
    private function display(string $name): ?string
    {
        $value = $this->raw($name);
        $field = $this->fields[$name];

        if ($value === null) {
            return null;
        }

        return match ($field['type']) {
            'boolean' => $value ? __('Yes') : null,
            'choice' => $field['options'][$this->code($value)] ?? (string) $value,
            'number' => self::number($value),
            'diagnoses' => is_array($value) ? implode('; ', Diagnoses::labels($value)) : (string) $value,
            default => trim((string) $value) === '' ? null : trim((string) $value),
        };
    }

    private function code(mixed $value): string
    {
        return is_float($value) && floor($value) === $value ? (string) (int) $value : (string) $value;
    }

    // ------------------------------------------------------------------ helpers

    protected static function number(mixed $value): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        $value = (float) $value;

        return floor($value) === $value ? (string) (int) $value : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * Joins the non-empty parts with the language's list separator.
     *
     * @param  array<int, string|null>  $parts
     */
    protected function join(array $parts, ?string $separator = null): string
    {
        return implode($separator ?? ($this->language() === 'fa' ? '، ' : ', '), array_filter($parts, fn ($part) => $part !== null && $part !== ''));
    }

    /**
     * @param  list<array{label: string|null, text: string}|null>  $lines
     * @param  list<list<string>>  $table  Rows of a table printed above the lines; the first row is the header.
     * @return array{title: string|null, lines: list<array{label: string|null, text: string}>, table?: list<list<string>>}
     */
    protected static function section(?string $title, array $lines, array $table = []): array
    {
        $section = ['title' => $title, 'lines' => array_values(array_filter($lines, fn (?array $line) => $line !== null && trim($line['text']) !== ''))];

        // Empty rows are dropped; a table with only its header row has nothing to show.
        $rows = array_values(array_filter(array_slice($table, 1), fn (array $row) => implode('', $row) !== ''));

        return $rows !== [] ? [...$section, 'table' => [$table[0], ...$rows]] : $section;
    }

    /**
     * @return array{label: string|null, text: string}|null
     */
    protected static function line(?string $label, ?string $text): ?array
    {
        return $text === null || trim($text) === '' ? null : ['label' => $label, 'text' => trim($text)];
    }

    /**
     * Ends a sentence with a full stop unless it already ends with punctuation.
     */
    protected static function sentence(string $text): string
    {
        $text = trim($text);

        return $text === '' || preg_match('/[.!?:؟]$/u', $text) ? $text : $text.'.';
    }

    /**
     * The patient's age in whole Jalali years on the procedure date (or the day the study was
     * recorded), so a reprinted signed report keeps the age it was signed with.
     */
    protected function age(): ?int
    {
        $dob = self::jalali($this->data['dob'] ?? null);
        $on = self::jalali($this->data[config('procedures.'.static::PROCEDURE.'.date_field')] ?? null);

        if ($on === null && ! empty($this->data['created_at'])) {
            $on = self::jalali(JalaliDate::format(Carbon::parse($this->data['created_at']), 'yyyy/MM/dd'));
        }
        if ($dob === null || $on === null) {
            return null;
        }

        $age = $on[0] - $dob[0] - (($on[1] * 100 + $on[2]) < ($dob[1] * 100 + $dob[2]) ? 1 : 0);

        return $age >= 0 && $age < 130 ? $age : null;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null Year, month and day of a "yyyy/m/d" Jalali date.
     */
    protected static function jalali(mixed $date): ?array
    {
        if (! is_string($date) || ! preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', trim($date), $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2], (int) $m[3]];
    }

    protected function gender(): ?string
    {
        return match ($this->data['gender'] ?? null) {
            'Male' => $this->language() === 'fa' ? 'مرد' : 'male',
            'Female' => $this->language() === 'fa' ? 'زن' : 'female',
            default => null,
        };
    }

    /**
     * "a 55-year-old male" / "۵۵ ساله، مرد" style description, or null when unknown.
     */
    protected function ageAndGender(): ?string
    {
        $age = $this->age();
        $gender = $this->gender();

        if ($this->language() === 'fa') {
            return $this->join([$age !== null ? "{$age} ساله" : null, $gender], ' ') ?: null;
        }

        if ($age === null && $gender === null) {
            return null;
        }

        return $age !== null ? "a {$age}-year-old".($gender ? " {$gender}" : '') : "a {$gender}";
    }

    /**
     * "date, time" for a date field and its time field.
     */
    protected function dateTime(string $date, ?string $time = null): ?string
    {
        return $this->join([$this->text($date), $time ? $this->text($time) : null], ' ') ?: null;
    }

    /**
     * A choice whose "other" code points to a free-text field shows that text instead.
     */
    protected function choiceOrOther(string $choice, string $other, string $otherCode = '99'): ?string
    {
        if ($this->is($choice, $otherCode)) {
            return $this->text($other) ?? $this->text($choice);
        }

        return $this->text($choice);
    }
}
