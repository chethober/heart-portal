<?php

use App\Services\EchoCalculations;

test('computes BSA and indexed LV values', function () {
    $r = EchoCalculations::derive(['Height' => '180', 'Weight' => '80', 'lv_LVEDV' => '150', 'lv_LVEDD' => '50']);

    expect(round($r['computed_BSA'], 3))->toBe(2.0)
        ->and(round($r['computed_LVEDVI'], 1))->toBe(75.0)
        ->and(round($r['computed_LVEDDI'], 1))->toBe(25.0);
});

test('grades LV size from LVEDVI using sex-specific cut-offs', function (string $gender, string $expected) {
    $r = EchoCalculations::derive(['gender' => $gender, 'Height' => '180', 'Weight' => '80', 'lv_LVEDV' => '150', 'lv_size' => 'Auto']);

    expect($r['lv_size'])->toBe($expected)
        ->and($r['lv_size_was_auto'])->toBeTrue()
        ->and($r['lv_size_input_used'])->toBe('LVEDVI');
})->with([
    ['Male', 'Mild LV enlargement'],       // 74 < 75 <= 89
    ['Female', 'Moderate LV enlargement'], // 70 < 75 <= 80
]);

test('keeps a value the user chose instead of auto-grading it', function () {
    $r = EchoCalculations::derive(['gender' => 'Male', 'lv_EF' => '20', 'lv_Systolic' => 'With normal systolic function']);

    expect($r['lv_Systolic'])->toBe('With normal systolic function')
        ->and($r['lv_Systolic_was_auto'])->toBeFalse();
});

test('estimates SPAP from TRG and IVC-derived RAP and grades PH', function () {
    $r = EchoCalculations::derive(['tv_TRG' => '40', 'cp_ivc_size' => '23', 'cp_Respiratory_variation' => '<50% respiratory variation']);

    expect($r['cp_SPAP'])->toBe(55.0)
        ->and($r['cp_SPAP_rap'])->toBe(15)
        ->and($r['cp_quantity'])->toBe('Moderate PH');
});

test('grades diastolic dysfunction when all three criteria are abnormal', function () {
    $r = EchoCalculations::derive([
        'lv_septale' => '5', 'lv_lateral' => '6', 'mv_PE_Velocity' => '1.2', 'mv_PA_Velocity' => '0.5', 'tv_TRG' => '40', 'lv_Diastolic' => 'Auto',
    ]);

    expect($r['lv_Diastolic'])->toBe('Grade III diastolic dysfunction');
});

test('lists dilated aortic segments', function () {
    $r = EchoCalculations::derive(['gender' => 'Female', 'av_sinus_valsalva' => '36', 'av_annulus' => '20', 'av_aorta' => 'Auto']);

    expect($r['av_aorta'])->toBe('dilated aortic size (sinuses of valsalva)')
        ->and($r['av_aorta_dilated_segments'])->toBe(['sinuses of valsalva']);
});
