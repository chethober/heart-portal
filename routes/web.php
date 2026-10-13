<?php

use App\Http\Controllers\Admin\HospitalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Clinical\AdmissionController;
use App\Http\Controllers\Clinical\DoctorController;
use App\Http\Controllers\Clinical\EchoStudyController;
use App\Http\Controllers\Clinical\PatientController;
use App\Http\Controllers\Clinical\PatientRecordController;
use App\Http\Controllers\Clinical\ProcedureStudyController;
use App\Http\Controllers\Clinical\WardController;
use App\Http\Controllers\Clinical\WardPatientsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HospitalPendingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\Staff\InvitationController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\WorklistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.landing')->name('home');
Route::view('/help', 'public.help')->name('help');
// The echo form guide is now part of the help center; keep old links and bookmarks working.
Route::permanentRedirect('/help/echo-form', '/help#recording');
Route::post('/locale', LocaleController::class)->name('locale.update');

Route::get('/hospital/pending', HospitalPendingController::class)->middleware(['auth', 'verified'])->name('hospital.pending');

// Every signed-in form, search and report lives under /dashboard.
Route::prefix('dashboard')->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {
        // The echo form for staff who record studies; everyone else is sent to their first section.
        Route::get('/', DashboardController::class)->middleware('two-factor')->name('dashboard');

        // Platform administration (no access to patient data).
        Route::middleware(['role:platform_admin', 'two-factor'])->name('admin.')->group(function () {
            Route::get('/hospitals', [HospitalController::class, 'index'])->name('hospitals.index');
            Route::patch('/hospitals/{hospital}/status', [HospitalController::class, 'updateStatus'])->name('hospitals.status');
        });

        // Everything below requires an active member of an approved hospital.
        Route::middleware(['hospital.active', 'two-factor'])->group(function () {
            Route::get('/users/{user}/signature', [SignatureController::class, 'show'])->name('users.signature');
            Route::post('/profile/signature', [SignatureController::class, 'update'])->middleware('can:sign-reports')->name('profile.signature');
            Route::get('/audit', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit.index');

            Route::middleware('permission:echo.create')->group(function () {
                Route::get('/studies/create', [EchoStudyController::class, 'create'])->name('studies.create');
                Route::post('/studies', [EchoStudyController::class, 'store'])->name('studies.store');
                Route::post('/studies/preview', [EchoStudyController::class, 'preview'])->name('studies.preview');
            });
            Route::get('/doctors', DoctorController::class)->middleware('can:record-studies')->name('doctors.index');
            // Each procedure has its own "{procedure}.create" permission, checked by the controller.
            Route::middleware('can:record-studies')->group(function () {
                Route::get('/procedures/{procedure}/create', [ProcedureStudyController::class, 'create'])->name('procedures.create');
                Route::post('/procedures/{procedure}', [ProcedureStudyController::class, 'store'])->name('procedures.store');
            });

            Route::middleware('permission:patients.view')->group(function () {
                Route::get('/worklist', WorklistController::class)->name('worklist');
                Route::get('/statistics', StatisticsController::class)->name('statistics');
                Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
                Route::get('/studies/{study}', [EchoStudyController::class, 'show'])->name('studies.show');
                Route::delete('/studies/{study}', [EchoStudyController::class, 'destroy'])->name('studies.destroy');
                // Authorization for these depends on the study's status; see EchoStudyPolicy.
                Route::get('/studies/{study}/edit', [EchoStudyController::class, 'edit'])->name('studies.edit');
                Route::put('/studies/{study}', [EchoStudyController::class, 'update'])->name('studies.update');
                Route::post('/studies/{study}/finalize', [EchoStudyController::class, 'finalize'])->name('studies.finalize');

                // Procedures ported from heart (angiography, ...); authorized like echo studies.
                Route::post('/procedures/{procedure}/preview', [ProcedureStudyController::class, 'preview'])->name('procedures.preview');
                Route::get('/procedures/{procedure}/{procedureStudy}', [ProcedureStudyController::class, 'show'])->name('procedures.show');
                Route::delete('/procedures/{procedure}/{procedureStudy}', [ProcedureStudyController::class, 'destroy'])->name('procedures.destroy');
                Route::get('/procedures/{procedure}/{procedureStudy}/edit', [ProcedureStudyController::class, 'edit'])->name('procedures.edit');
                Route::put('/procedures/{procedure}/{procedureStudy}', [ProcedureStudyController::class, 'update'])->name('procedures.update');
                Route::post('/procedures/{procedure}/{procedureStudy}/finalize', [ProcedureStudyController::class, 'finalize'])->name('procedures.finalize');
            });

            // Heart's patient record, admissions, wards and ward patient list.
            Route::middleware('permission:patients.view')->group(function () {
                Route::get('/ward-patients', WardPatientsController::class)->name('ward-patients');
                Route::get('/patients/{patient}', [PatientRecordController::class, 'show'])->whereNumber('patient')->name('patients.show');
                Route::get('/patients/{patient}/admissions/{admission}', [AdmissionController::class, 'show'])->middleware('permission:admission.view')->whereNumber(['patient', 'admission'])->scopeBindings()->name('admissions.show');
            });
            Route::middleware('permission:patients.edit')->whereNumber('patient')->group(function () {
                Route::get('/patients/{patient}/edit', [PatientRecordController::class, 'edit'])->name('patients.edit');
                Route::put('/patients/{patient}', [PatientRecordController::class, 'update'])->name('patients.update');
            });
            Route::middleware('permission:admission.create')->whereNumber('patient')->group(function () {
                Route::get('/patients/{patient}/admissions/create', [AdmissionController::class, 'create'])->name('admissions.create');
                Route::post('/patients/{patient}/admissions', [AdmissionController::class, 'store'])->name('admissions.store');
            });
            Route::middleware('permission:admission.edit')->whereNumber(['patient', 'admission'])->group(function () {
                Route::scopeBindings()->group(function () {
                    Route::get('/patients/{patient}/admissions/{admission}/edit', [AdmissionController::class, 'edit'])->name('admissions.edit');
                    Route::put('/patients/{patient}/admissions/{admission}', [AdmissionController::class, 'update'])->name('admissions.update');
                    Route::post('/patients/{patient}/admissions/{admission}/discharge', [AdmissionController::class, 'discharge'])->name('admissions.discharge');
                });
            });
            Route::middleware('permission:hospital.settings')->prefix('wards')->name('wards.')->group(function () {
                Route::get('/', [WardController::class, 'index'])->name('index');
                Route::post('/', [WardController::class, 'store'])->name('store');
                Route::patch('/{ward}', [WardController::class, 'update'])->whereNumber('ward')->name('update');
            });

            Route::middleware('permission:staff.manage')->prefix('staff')->name('staff.')->group(function () {
                Route::get('/', [StaffController::class, 'index'])->name('index');
                Route::patch('/{user}', [StaffController::class, 'update'])->name('update');
                Route::put('/{user}/access', [StaffController::class, 'updateAccess'])->name('access.update');
                Route::delete('/{user}/two-factor', [StaffController::class, 'resetTwoFactor'])->name('two-factor.reset');
                Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
                Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
            });
        });
    });

    // Account settings stay reachable before email verification and two-factor enrolment.
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::post('/profile/two-factor', [TwoFactorController::class, 'store'])->name('two-factor.enable');
        Route::post('/profile/two-factor/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:6,1')->name('two-factor.confirm');
        Route::post('/profile/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
        Route::delete('/profile/two-factor', [TwoFactorController::class, 'destroy'])->name('two-factor.disable');
    });
});

// Pages that used to live outside /dashboard: keep old links and bookmarks working.
Route::get('/{page}', function (Request $request, string $page) {
    $path = preg_replace('#^admin/hospitals#', 'hospitals', $page);
    $query = $request->getQueryString();

    return redirect()->to('/dashboard/'.$path.($query ? '?'.$query : ''), 301);
})->where('page', '(patients|studies|staff|audit|profile|admin/hospitals)(/.*)?');

require __DIR__.'/prescriptions.php';
require __DIR__.'/auth.php';
