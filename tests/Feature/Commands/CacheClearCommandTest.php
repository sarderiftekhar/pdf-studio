<?php

use Illuminate\Support\Facades\Cache;

it('clears the PDF Studio CSS cache', function () {
    Cache::put('pdf-studio:css:testkey', '.some { css }');

    $this->artisan('pdf-studio:cache-clear')
        ->expectsOutputToContain('cleared')
        ->assertExitCode(0);
});

it('succeeds even when cache is empty', function () {
    $this->artisan('pdf-studio:cache-clear')
        ->assertExitCode(0);
});
