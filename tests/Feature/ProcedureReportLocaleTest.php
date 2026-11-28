<?php

use App\Enums\Role;
use App\Models\Admission;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\ProcedureStudy;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Reports\ProcedureReport;
use App\Support\Procedures;
use Illuminate\Support\Facades\View;

/*
 * Procedure reports print in the reader's interface language inside one shared template; the
 * sentences built from the form stay in its clinical language.
 */

dataset('report procedures', array_keys(Procedures::MODELS));

beforeEach(function () {
    $this->hospital = Hospital::factory()->create(['name' => 'Home Heart Hospital']);
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create(['name' => 'Dr. Home']);
});

/**
 * Records a draft study of the procedure for the patient Ali Rezaei and returns it.
 *
 * @param  array<string, mixed>  $fields
 */
function recordProcedure(User $user, string $procedure, array $fields = []): ProcedureStudy
{
    test()->actingAs($user)->post("/dashboard/procedures/{$procedure}", [
        'nationalID' => '0012345678', 'first_name' => 'Ali', 'last_name' => 'Rezaei',
        config("procedures.{$procedure}.date_field") => '1405/07/09',
        ...$fields,
    ])->assertSessionHasNoErrors()->assertRedirect();

    return Procedures::model($procedure)::withoutGlobalScope(HospitalScope::class)->latest('id')->firstOrFail();
}

test('every procedure report renders in Persian, right to left, without English chrome', function (string $procedure) {
    $study = recordProcedure($this->nurse, $procedure);
    app()->setLocale('fa');
    $title = ProcedureReport::for($procedure, [])->title();

    $this->actingAs($this->nurse)->withCookie('locale', 'fa')->get("/dashboard/procedures/{$procedure}/{$study->id}")
        ->assertOk()
        ->assertSee('<html lang="fa" dir="rtl"', false)
        ->assertSee('<div class="report-container" dir="rtl" lang="fa"', false)
        ->assertSee($title)
        ->assertSee('پیش نویس — امضا نشده')
        ->assertSee('چاپ گزارش')
        ->assertSee('بازگشت به بیمار')
        ->assertSee('تاریخ پروسیجر')
        ->assertSee('Ali Rezaei')
        ->assertDontSee('DRAFT — NOT SIGNED')
        ->assertDontSee('Print Report')
        ->assertDontSee('Back to Patient')
        ->assertDontSee('Procedure date')
        ->assertDontSee('Assistant physician')
        ->assertDontSee('Attending physician')
        ->assertDontSee('dir="ltr" lang="en"', false);
})->with('report procedures');

test('every procedure report and preview prints in the shared template', function (string $procedure) {
    $study = recordProcedure($this->nurse, $procedure);
    $composed = [];
    View::composer('procedures.report.*', function ($view) use (&$composed) {
        $composed[] = $view->name();
    });

    $this->actingAs($this->nurse)->get("/dashboard/procedures/{$procedure}/{$study->id}")->assertOk();
    expect($composed)->toBe(['procedures.report.document', 'procedures.report.narrative']);

    $composed = [];
    $this->actingAs($this->nurse)->withCookie('locale', 'ar')->post("/dashboard/procedures/{$procedure}/preview", ['first_name' => 'Ali'])
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', false)
        ->assertSee('<div class="report-container" dir="rtl" lang="ar"', false)
        ->assertSee('مسودة — غير موقّعة');
    expect($composed)->toBe(['procedures.report.document', 'procedures.report.narrative']);
})->with('report procedures');

test('a signed coronary angiography reads in Persian end to end, its findings kept as recorded', function () {
    $study = recordProcedure($this->nurse, 'angiography', [
        'lad_Vessel_patency' => '3', 'lad_proximal_part_segment' => '2', 'lad_prox_Stenotic' => '3',
        'result_legacy' => '6', 'recommend_cabg' => '1', 'prc' => '0',
    ]);
    $this->actingAs($this->doctor)->post("/dashboard/procedures/angiography/{$study->id}/finalize")->assertRedirect();

    $this->actingAs($this->doctor)->withCookie('locale', 'fa')->get("/dashboard/procedures/angiography/{$study->id}")
        ->assertOk()
        ->assertSee('Home Heart Hospital')
        ->assertSee('گزارش آنژیوگرافی')
        ->assertSee('یافته‌ها')
        ->assertSee('نتیجه')
        ->assertSee('عارضه مرتبط با پروسیجر')
        ->assertSee('بدون عارضه.')
        ->assertSee('توصیه')
        ->assertSee('پزشک دستیار')
        ->assertSee('پزشک معالج')
        ->assertSee('Dr. Home')
        ->assertSee('تاریخ امضا')
        ->assertSee('نسخه‌های امضاشده')
        // The narrative and the form's captions are the clinical record, in English.
        ->assertSee('Three-vessel CAD (3VD)')
        ->assertSee('Severe stenosis(70-89%) at proximal part segment')
        ->assertDontSee('DRAFT')
        ->assertDontSee('Coronary Angiography Report')
        ->assertDontSee('Signed versions');

    $this->actingAs($this->doctor)->withCookie('locale', 'de')->get("/dashboard/procedures/angiography/{$study->id}?version=1")
        ->assertOk()
        ->assertSee('<div class="report-container" dir="ltr" lang="de"', false)
        ->assertSee('Befund der Koronarangiographie')
        ->assertSee('Unterschrieben am')
        ->assertSee('Three-vessel CAD (3VD)');
});

test('the summary another form extracts stays in the clinical language whatever the reader\'s', function () {
    app()->setLocale('fa');

    $rhc = ['right_heart_result' => 'Severe pulmonary hypertension', 'cardiac_output' => 4];
    $labels = collect(ProcedureReport::for('rhc', $rhc)->sections())->flatMap(fn (array $section) => array_column($section['lines'], 'label'));

    expect($labels)->toContain('نتیجه')
        ->and(ProcedureReport::for('rhc', $rhc)->summaryText())->toBe('Severe pulmonary hypertension.')
        ->and(ProcedureReport::for('angioplasty', ['t1_territory_of_intervention' => '2', 't1_Result_Of_PCI_1' => '1'])->summaryText())->toBe('successful PCI on LAD')
        ->and(ProcedureReport::for('angiography', ['result_legacy' => '6', 'recommend_cabg' => '1'])->summaryText())
        ->toBe('آنژیوگرافی عروق کرونر انجام شد Three-vessel CAD (3VD) • Recommendation: CABG')
        ->and(app()->getLocale())->toBe('fa');
});

test('another hospital cannot open a procedure report or its signed versions in any language', function (string $procedure) {
    $study = recordProcedure($this->nurse, $procedure);
    $outsider = User::factory()->for(Hospital::factory()->create(['name' => 'Other Hospital']))->role(Role::Doctor)->create();

    foreach (['fa', 'en'] as $locale) {
        $this->actingAs($outsider)->withCookie('locale', $locale)->get("/dashboard/procedures/{$procedure}/{$study->id}")->assertNotFound();
        $this->actingAs($outsider)->withCookie('locale', $locale)->get("/dashboard/procedures/{$procedure}/{$study->id}?version=1")->assertNotFound();
    }

    // The preview prints the reader's own hospital and never another hospital's admission.
    $foreign = Admission::factory()->for(Patient::factory()->for(Hospital::factory()))->create(['ward' => 'Elsewhere ward']);
    $this->actingAs($outsider)->withCookie('locale', 'fa')->post("/dashboard/procedures/{$procedure}/preview", ['first_name' => 'Ali', 'admission_id' => $foreign->id])
        ->assertOk()
        ->assertSee('Other Hospital')
        ->assertDontSee('Home Heart Hospital')
        ->assertDontSee('Elsewhere ward');
})->with('report procedures');
