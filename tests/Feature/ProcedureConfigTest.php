<?php

use App\Support\Procedures;

dataset('procedures', array_keys(Procedures::MODELS));

/*
 * The server clears a field whose show_if condition is not met (heart disabled such fields, so
 * they were never saved). A wrong condition would silently drop what a clinician typed, so check
 * every condition can actually be met, and is decided before the field itself.
 */
test('every show_if depends on an earlier field and one of its values', function (string $procedure) {
    $fields = Procedures::fields($procedure);
    $order = array_flip(array_keys($fields));
    expect($fields)->not->toBeEmpty();

    foreach ($fields as $name => $field) {
        if (! isset($field['show_if'])) {
            continue;
        }

        [$parent, $value] = $field['show_if'];
        expect(array_key_exists($parent, $fields))->toBeTrue("{$name} depends on missing {$parent}");
        expect($order[$parent])->toBeLessThan($order[$name], "{$name} comes before {$parent}");

        $allowed = match ($fields[$parent]['type']) {
            'choice' => array_map('strval', array_keys($fields[$parent]['options'])),
            'boolean' => ['1'],
            default => [],
        };
        expect(in_array($value, $allowed, true))->toBeTrue("{$name} needs {$parent} = {$value}, which it cannot have");
    }
})->with('procedures');

test('every field has a known type and choices have options', function (string $procedure) {
    foreach (Procedures::fields($procedure) as $name => $field) {
        expect(in_array($field['type'], ['choice', 'boolean', 'number', 'text', 'multiline', 'date', 'time', 'diagnoses'], true))->toBeTrue("{$name} has unknown type {$field['type']}");

        if ($field['type'] === 'choice') {
            expect($field['options'])->not->toBeEmpty($name);
        }
    }

    expect(Procedures::fields($procedure))->toHaveKey(config("procedures.{$procedure}.date_field"));
})->with('procedures');
