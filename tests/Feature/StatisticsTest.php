<?php

use App\Enums\Role;
use App\Enums\StudyStatus;
use App\Models\AngiographyStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use App\Support\JalaliDate;
use App\Support\RegistryStatistics;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
});

/**
 * An angiography of the hospital, with the given field values.
 *
 * @param  array<string, mixed>  $values
 */
function angiographyFor(Hospital $hospital, array $values = []): AngiographyStudy
{
    $study = new AngiographyStudy([...$values]);
    $study->forceFill([
        'hospital_id' => $hospital->id,
        'patient_id' => Patient::factory()->for($hospital)->create()->id,
        'status' => StudyStatus::Draft,
        ...$values,
    ])->save();

    return $study;
}

test('the statistics page counts only the hospital\'s own studies', function () {
    $month = JalaliDate::format(now(), 'yyyy/MM');
    angiographyFor($this->hospital, ['result_legacy' => '4', 'DateOf_Date_of_angiography' => $month.'/1']);
    angiographyFor($this->hospital, ['result_legacy' => '4', 'status' => StudyStatus::Final]);
    angiographyFor($this->hospital, ['result_legacy' => '6']);
    // Another hospital's studies and deleted studies are not counted.
    angiographyFor(Hospital::factory()->create(), ['result_legacy' => '6']);
    angiographyFor($this->hospital, ['result_legacy' => '6'])->delete();

    $this->actingAs($this->nurse);
    $statistics = new RegistryStatistics(12);

    $angiography = collect($statistics->totals())->firstWhere('key', 'angiography');
    expect($angiography)->toMatchArray(['total' => 3, 'signed' => 1, 'drafts' => 2, 'patients' => 3]);

    $monthly = $statistics->monthly();
    expect($monthly['months'])->toHaveCount(12)
        ->and(end($monthly['months']))->toBe($month)
        ->and($monthly['series']['angiography'][$month])->toBe(3)
        ->and($monthly['max'])->toBe(3);

    $result = collect($statistics->distributions())->firstWhere('field', 'result_legacy');
    expect($result['total'])->toBe(3)
        ->and($result['rows'])->toBe([['label' => '1VD', 'count' => 2], ['label' => '3VD', 'count' => 1]]);

    $this->get('/dashboard/statistics')
        ->assertOk()
        ->assertSee('Studies per month')
        ->assertSee('1VD');
});

test('procedures count in the month of their procedure date', function () {
    angiographyFor($this->hospital, ['DateOf_Date_of_angiography' => '1300/1/1']);

    $this->actingAs($this->nurse);
    $monthly = (new RegistryStatistics(6))->monthly();

    expect($monthly['months'])->toHaveCount(6)
        ->and($monthly['max'])->toBe(0);
});

test('the statistics page works with no studies', function () {
    $this->actingAs($this->nurse)->get('/dashboard/statistics?months=24')
        ->assertOk()
        ->assertSee('No studies in this period.');
});

test('the statistics page needs the patients permission', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $this->actingAs($admin)->get('/dashboard/statistics')->assertOk();

    // The platform admin sees no patient data.
    $this->actingAs(User::factory()->platformAdmin()->create())->get('/dashboard/statistics')->assertForbidden();
});
