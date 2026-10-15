<?php

use App\Enums\Role;
use App\Models\AngiographyStudy;
use App\Models\AuditLog;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Support\JalaliCalendar;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $this->patient = Patient::factory()->for($this->hospital)->create(['national_id' => '0012345679', 'first_name' => 'Ali', 'last_name' => '=HYPERLINK("x")']);
});

function csvRows(string $body): array
{
    expect(str_starts_with($body, "\u{FEFF}"))->toBeTrue();

    return array_map(str_getcsv(...), array_filter(explode("\n", substr($body, 3))));
}

test('a procedure exports with labels or stored codes', function () {
    AngiographyStudy::factory()->for($this->patient)->create(['lad_Vessel_patency' => '3']);

    $labels = csvRows($this->actingAs($this->admin)->get('/dashboard/exports/angiography')->assertOk()->streamedContent());
    $codes = csvRows($this->actingAs($this->admin)->get('/dashboard/exports/angiography?format=codes')->streamedContent());

    $column = array_search('lad_Vessel_patency', $codes[0], true);
    $options = collect(config('procedures.angiography.sections'))->flatMap(fn ($section) => $section['fields'])['lad_Vessel_patency']['options'];

    expect($codes)->toHaveCount(2)
        ->and($codes[1][$column])->toBe('3')
        ->and($labels[1][$column])->toBe($options['3'])
        ->and($codes[1][array_search('national_id', $codes[0], true)])->toBe('0012345679')
        // Text that looks like a formula is kept as text.
        ->and($codes[1][array_search('last_name', $codes[0], true)])->toBe('\'=HYPERLINK("x")');
});

test('echo studies export too, filtered by status and date', function () {
    EchoStudy::factory()->for($this->patient)->create(['lv_EF' => 55]);
    EchoStudy::factory()->for($this->patient)->create(['status' => 'final', 'created_at' => now()->subYears(2)]);

    expect(csvRows($this->actingAs($this->admin)->get('/dashboard/exports/echo')->streamedContent()))->toHaveCount(3)
        ->and(csvRows($this->actingAs($this->admin)->get('/dashboard/exports/echo?status=draft')->streamedContent()))->toHaveCount(2)
        ->and(csvRows($this->actingAs($this->admin)->get('/dashboard/exports/echo?from='.JalaliCalendar::today())->streamedContent()))->toHaveCount(2);

    $this->actingAs($this->admin)->get('/dashboard/exports/echo?from=yesterday')->assertSessionHasErrors('from');
});

test('exports are only for staff allowed to export the form, and logged without patient data', function () {
    $nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->actingAs($nurse)->get('/dashboard/exports/echo')->assertForbidden();

    $this->admin->revokePermissionTo('echo.view');
    $this->actingAs($this->admin)->get('/dashboard/exports/echo')->assertForbidden();
    $this->actingAs($this->admin)->get('/dashboard/exports/admission')->assertNotFound();

    $this->actingAs($this->admin)->get('/dashboard/exports/angiography')->assertOk()->streamedContent();
    $log = AuditLog::withoutGlobalScope(HospitalScope::class)->where('action', 'registry.exported')->sole();
    expect($log->changes)->toMatchArray(['form' => 'angiography', 'rows' => 0])
        ->and(json_encode($log->changes))->not->toContain('0012345679');
});

test('another hospital\'s studies never appear in an export', function () {
    EchoStudy::factory()->for($this->patient)->create();
    $otherAdmin = User::factory()->for(Hospital::factory())->role(Role::HospitalAdmin)->create();

    expect(csvRows($this->actingAs($otherAdmin)->get('/dashboard/exports/echo')->streamedContent()))->toHaveCount(1);
});

test('the statistics page offers the export to those who may use it', function () {
    $this->actingAs($this->admin)->get('/dashboard/statistics')->assertOk()->assertSee('Export registry data');

    $nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->actingAs($nurse)->get('/dashboard/statistics')->assertOk()->assertDontSee('Export registry data');
});
