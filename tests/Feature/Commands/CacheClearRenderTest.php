<?php

it('clears render cache with --render flag', function () {
    $this->artisan('pdf-studio:cache-clear', ['--render' => true])
        ->expectsOutputToContain('render cache cleared')
        ->assertSuccessful();
});

it('clears css cache without --render flag', function () {
    $this->artisan('pdf-studio:cache-clear')
        ->expectsOutputToContain('CSS cache cleared')
        ->assertSuccessful();
});
