<?php

use App\Http\Controllers\Clinical\DrugController;
use App\Http\Controllers\Clinical\PrescriptionController;
use App\Http\Controllers\PrescriptionVerificationController;
use Illuminate\Support\Facades\Route;

/*
| E-prescription, ported from heart's ep module: drug catalog, prescriptions and the public
| verification page the printed QR code links to. Included from routes/web.php.
*/

Route::get('/prescriptions/verify/{token}', PrescriptionVerificationController::class)
    ->where('token', '[A-Za-z0-9]{32}')
    ->middleware('throttle:30,1')
    ->name('prescriptions.verify');

Route::prefix('dashboard')->middleware(['auth', 'verified', 'hospital.active', 'two-factor'])->group(function () {
    Route::middleware('permission:prescriptions.create')->group(function () {
        Route::get('/drugs/search', [DrugController::class, 'search'])->middleware('throttle:60,1')->name('drugs.search');
        Route::get('/prescriptions/create', [PrescriptionController::class, 'create'])->name('prescriptions.create');
        Route::post('/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
    });

    Route::middleware('permission:patients.view')->group(function () {
        Route::get('/drugs', [DrugController::class, 'index'])->name('drugs.index');
        // Authorization for these depends on the prescription's status; see PrescriptionPolicy.
        Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])->whereNumber('prescription')->name('prescriptions.show');
        Route::get('/prescriptions/{prescription}/edit', [PrescriptionController::class, 'edit'])->whereNumber('prescription')->name('prescriptions.edit');
        Route::put('/prescriptions/{prescription}', [PrescriptionController::class, 'update'])->whereNumber('prescription')->name('prescriptions.update');
        Route::delete('/prescriptions/{prescription}', [PrescriptionController::class, 'destroy'])->whereNumber('prescription')->name('prescriptions.destroy');
        Route::post('/prescriptions/{prescription}/sign', [PrescriptionController::class, 'sign'])->whereNumber('prescription')->name('prescriptions.sign');
        Route::post('/prescriptions/{prescription}/cancel', [PrescriptionController::class, 'cancel'])->whereNumber('prescription')->name('prescriptions.cancel');
    });

    Route::middleware('permission:hospital.settings')->group(function () {
        Route::post('/drugs', [DrugController::class, 'store'])->name('drugs.store');
        Route::patch('/drugs/{drug}', [DrugController::class, 'update'])->whereNumber('drug')->name('drugs.update');
    });
});
