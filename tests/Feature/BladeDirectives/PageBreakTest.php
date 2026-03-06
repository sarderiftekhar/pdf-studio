<?php

it('renders @pageBreak as page-break CSS', function () {
    $blade = \Illuminate\Support\Facades\Blade::compileString('@pageBreak');

    expect($blade)->toContain('page-break-after')
        ->and($blade)->toContain('break-after');
});

it('renders @pageBreakBefore as page-break-before CSS', function () {
    $blade = \Illuminate\Support\Facades\Blade::compileString('@pageBreakBefore');

    expect($blade)->toContain('page-break-before')
        ->and($blade)->toContain('break-before');
});

it('renders @avoidBreak as avoid-break CSS', function () {
    $blade = \Illuminate\Support\Facades\Blade::compileString('@avoidBreak');

    expect($blade)->toContain('page-break-inside')
        ->and($blade)->toContain('break-inside');
});

it('renders @endAvoidBreak as closing div', function () {
    $blade = \Illuminate\Support\Facades\Blade::compileString('@endAvoidBreak');

    expect($blade)->toContain('</div>');
});
