<?php

use App\Support\ReportHtml;

test('keeps the formatting the report templates produce', function () {
    $html = '<u>Mild LV enlargement</u> <span class="tooltip-container" style="color:red">i<span class="tooltip-table-box">x</span></span>';

    expect(ReportHtml::clean($html))->toBe($html);
});

test('strips scripts, event handlers and unknown tags from free text', function () {
    $clean = ReportHtml::clean('<u onclick="steal()">a</u><script>alert(1)</script><img src=x onerror=alert(1)><a href="javascript:x">link</a>');

    expect($clean)->toBe('<u>a</u>link')
        ->not->toContain('script')
        ->not->toContain('onerror');
});

test('preserves Persian text', function () {
    expect(ReportHtml::clean('<u>بیمار</u>'))->toBe('<u>بیمار</u>');
});
