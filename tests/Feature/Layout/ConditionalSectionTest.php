<?php

use Illuminate\Support\Facades\Blade;

it('compiles showIf Blade directive', function () {
    $compiled = Blade::compileString('@showIf($condition)');
    expect($compiled)->toContain('display: none');
});

it('compiles endShowIf Blade directive', function () {
    $compiled = Blade::compileString('@endShowIf');
    expect($compiled)->toContain('</div>');
});

it('compiles keepTogether Blade directive', function () {
    $compiled = Blade::compileString('@keepTogether');
    expect($compiled)->toContain('page-break-inside: avoid');
});

it('compiles endKeepTogether Blade directive', function () {
    $compiled = Blade::compileString('@endKeepTogether');
    expect($compiled)->toContain('</div>');
});
