<?php

use PdfStudio\Laravel\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/**
 * Check if Chromium/Puppeteer is available for integration tests.
 */
function chromiumAvailable(): bool
{
    static $available = null;

    if ($available !== null) {
        return $available;
    }

    if (!class_exists(\Spatie\Browsershot\Browsershot::class)) {
        return $available = false;
    }

    $puppeteerPath = realpath(__DIR__.'/../node_modules/puppeteer');

    if (!$puppeteerPath || !is_dir($puppeteerPath)) {
        return $available = false;
    }

    try {
        $output = shell_exec('node -e "console.log(require(\'puppeteer\').executablePath())" 2>&1');

        return $available = is_string($output) && is_file(trim($output));
    } catch (\Throwable) {
        return $available = false;
    }
}
