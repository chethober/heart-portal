<?php

use App\Http\Controllers\Admin\HospitalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Clinical\DoctorController;
use App\Http\Controllers\Clinical\EchoStudyController;
use App\Http\Controllers\Clinical\HelpController;
use App\Http\Controllers\Clinical\PatientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HospitalPendingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\Staff\InvitationController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.landing')->name('home');
Route::view('/help', 'public.help')->name('help');
Route::post('/locale', LocaleController::class)->name('locale.update');

Route::get('/hospital/pending', HospitalPendingController::class)->middleware(['auth', 'verified'])->name('hospital.pending');

// Every signed-in form, search and report lives under /dashboard.
Route::prefix('dashboard')->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {
        // The hospital worklist; platform admins are sent to the hospitals tab.
        Route::get('/', DashboardController::class)->middleware('two-factor')->name('dashboard');

        // Platform administration (no access to patient data).
        Route::middleware(['role:platform_admin', 'two-factor'])->name('admin.')->group(function () {
            Route::get('/hospitals', [HospitalController::class, 'index'])->name('hospitals.index');
            Route::patch('/hospitals/{hospital}/status', [HospitalController::class, 'updateStatus'])->name('hospitals.status');
        });

        // Everything below requires an active member of an approved hospital.
        Route::middleware(['hospital.active', 'two-factor'])->group(function () {
            Route::get('/help', HelpController::class)->name('help.form');
            Route::get('/users/{user}/signature', [SignatureController::class, 'show'])->name('users.signature');
            Route::post('/profile/signature', [SignatureController::class, 'update'])->middleware('permission:studies.finalize')->name('profile.signature');
            Route::get('/audit', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit.index');

            Route::middleware('permission:studies.create')->group(function () {
                Route::get('/studies/create', [EchoStudyController::class, 'create'])->name('studies.create');
                Route::post('/studies', [EchoStudyController::class, 'store'])->name('studies.store');
                Route::post('/studies/preview', [EchoStudyController::class, 'preview'])->name('studies.preview');
                Route::get('/doctors', DoctorController::class)->name('doctors.index');
            });

            Route::middleware('permission:patients.view')->group(function () {
                Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
                Route::get('/studies/{study}', [EchoStudyController::class, 'show'])->name('studies.show');
                Route::delete('/studies/{study}', [EchoStudyController::class, 'destroy'])->name('studies.destroy');
                // Authorization for these depends on the study's status; see EchoStudyPolicy.
                Route::get('/studies/{study}/edit', [EchoStudyController::class, 'edit'])->name('studies.edit');
                Route::put('/studies/{study}', [EchoStudyController::class, 'update'])->name('studies.update');
                Route::post('/studies/{study}/finalize', [EchoStudyController::class, 'finalize'])->name('studies.finalize');
            });

            Route::middleware('permission:staff.manage')->prefix('staff')->name('staff.')->group(function () {
                Route::get('/', [StaffController::class, 'index'])->name('index');
                Route::patch('/{user}', [StaffController::class, 'update'])->name('update');
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
    $path = preg_replace(['#^admin/hospitals#', '#^help/echo-form#'], ['hospitals', 'help'], $page);
    $query = $request->getQueryString();

    return redirect()->to('/dashboard/'.$path.($query ? '?'.$query : ''), 301);
})->where('page', '(patients|studies|staff|audit|profile|admin/hospitals|help/echo-form)(/.*)?');

require __DIR__.'/auth.php';
