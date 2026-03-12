<?php

namespace PdfStudio\Laravel\Commands;

use Illuminate\Console\Command;
use PdfStudio\Laravel\Drivers\FakeDriver;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Fonts\FontRegistry;

class DoctorCommand extends Command
{
    protected $signature = 'pdf-studio:doctor';

    protected $description = 'Run diagnostics on your PDF Studio installation';

    public function handle(): int
    {
        $this->info('PDF Studio Diagnostics');
        $this->line('');

        $allPassed = true;

        // PHP version
        $allPassed = $this->check('PHP Version >= 8.1', version_compare(PHP_VERSION, '8.1.0', '>='),
            'Upgrade PHP to >= 8.1');

        // Memory limit
        $memoryLimit = ini_get('memory_limit') ?: '128M';
        $memoryBytes = $this->parseMemory($memoryLimit);
        $allPassed = $this->check("Memory Limit ({$memoryLimit})", $memoryBytes === -1 || $memoryBytes >= 128 * 1024 * 1024,
            'Increase memory_limit to at least 128M in php.ini') && $allPassed;

        // DOM extension
        $allPassed = $this->check('DOM extension available', class_exists(\DOMDocument::class),
            'Enable the PHP DOM/XML extension for HTML asset resolution') && $allPassed;

        // Temporary directory
        $tempDir = sys_get_temp_dir();
        $allPassed = $this->check("Temporary directory writable ({$tempDir})", is_dir($tempDir) && is_writable($tempDir),
            'Ensure PHP can write to the system temp directory') && $allPassed;

        // Driver summary
        $defaultDriver = (string) config('pdf-studio.default_driver', 'chromium');
        $this->line("  <info>[INFO]</info> Default driver: {$defaultDriver}");

        // Chromium / Node
        $allPassed = $this->checkBinary('Node.js', 'node', '--version') && $allPassed;

        $cloudflareAccountId = (string) config('pdf-studio.drivers.cloudflare.account_id', '');
        $cloudflareToken = (string) config('pdf-studio.drivers.cloudflare.api_token', '');
        if ($cloudflareAccountId !== '' && $cloudflareToken !== '') {
            $this->line("  <info>[INFO]</info> Cloudflare Browser Rendering configured (account {$cloudflareAccountId})");
        } else {
            $this->warn('  [SKIP] Cloudflare Browser Rendering credentials not configured');
        }

        // Gotenberg endpoint
        $gotenbergUrl = (string) config('pdf-studio.drivers.gotenberg.url', '');
        if ($gotenbergUrl !== '') {
            $this->line("  <info>[INFO]</info> Gotenberg endpoint configured ({$gotenbergUrl})");
            if ($defaultDriver === 'gotenberg') {
                $reachable = $this->checkUrlReachable($gotenbergUrl);
                $allPassed = $this->check("Gotenberg endpoint reachable ({$gotenbergUrl})", $reachable,
                    'Start Gotenberg or update pdf-studio.drivers.gotenberg.url') && $allPassed;
            }
        } else {
            $this->warn('  [SKIP] Gotenberg endpoint not configured');
        }

        $weasyBinary = (string) config('pdf-studio.drivers.weasyprint.binary', 'weasyprint');
        if ($this->commandExists($weasyBinary, '--version')) {
            $this->checkBinary('WeasyPrint', $weasyBinary, '--version');
        } else {
            $this->warn("  [SKIP] WeasyPrint binary not found ({$weasyBinary})");
        }

        // dompdf
        $allPassed = $this->check('dompdf/dompdf installed', class_exists(\Dompdf\Dompdf::class),
            'composer require dompdf/dompdf') && $allPassed;

        // wkhtmltopdf binary
        $wkhtmlBinary = (string) config('pdf-studio.drivers.wkhtmltopdf.binary', '/usr/local/bin/wkhtmltopdf');
        $allPassed = $this->check("wkhtmltopdf ({$wkhtmlBinary})", $this->binaryExists($wkhtmlBinary),
            'Install wkhtmltopdf or update config path') && $allPassed;

        // pdftk binary
        $pdftkBinary = (string) config('pdf-studio.manipulation.pdftk_binary', '/usr/bin/pdftk');
        $allPassed = $this->check("pdftk ({$pdftkBinary})", $this->binaryExists($pdftkBinary),
            'Install pdftk for merge/watermark/protect features') && $allPassed;

        // FPDI
        $allPassed = $this->check('setasign/fpdi installed', class_exists(\setasign\Fpdi\Fpdi::class),
            'composer require setasign/fpdi (for merge/watermark)') && $allPassed;

        // Tailwind binary
        /** @var string|null $tailwindBinary */
        $tailwindBinary = config('pdf-studio.tailwind.binary');
        if ($tailwindBinary) {
            $allPassed = $this->check("Tailwind binary ({$tailwindBinary})", $this->binaryExists($tailwindBinary),
                'Verify tailwind binary path') && $allPassed;
        } else {
            $this->warn('  [SKIP] Tailwind binary not configured');
        }

        $assetPolicy = (bool) config('pdf-studio.assets.allow_remote', true) ? 'remote assets allowed' : 'remote assets blocked';
        $inlineLocal = (bool) config('pdf-studio.assets.inline_local', true) ? 'inline local assets enabled' : 'inline local assets disabled';
        $this->line("  <info>[INFO]</info> Asset policy: {$assetPolicy}; {$inlineLocal}");

        $allPassed = $this->checkConfiguredFonts() && $allPassed;

        // Test render
        $allPassed = $this->checkFakeRender() && $allPassed;

        $this->line('');
        if ($allPassed) {
            $this->info('All checks passed!');
        } else {
            $this->warn('Some checks failed. See suggestions above.');
        }

        return $allPassed ? self::SUCCESS : self::FAILURE;
    }

    protected function check(string $label, bool $passed, string $fixSuggestion): bool
    {
        if ($passed) {
            $this->line("  <info>[PASS]</info> {$label}");
        } else {
            $this->line("  <error>[FAIL]</error> {$label}");
            $this->line("         Fix: {$fixSuggestion}");
        }

        return $passed;
    }

    protected function checkBinary(string $label, string $command, string $versionFlag): bool
    {
        $output = [];
        $code = -1;
        @exec("{$command} {$versionFlag} 2>&1", $output, $code);

        $version = $code === 0 ? trim(implode('', $output)) : null;

        if ($version) {
            $this->line("  <info>[PASS]</info> {$label} ({$version})");

            return true;
        }

        $this->line("  <error>[FAIL]</error> {$label} not found");
        $this->line("         Fix: Install {$command}");

        return false;
    }

    protected function commandExists(string $command, string $versionFlag): bool
    {
        $output = [];
        $code = -1;
        @exec("{$command} {$versionFlag} 2>&1", $output, $code);

        return $code === 0;
    }

    protected function checkUrlReachable(string $url): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 2,
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false && empty($http_response_header)) {
            return false;
        }

        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $matches) === 1) {
                $status = (int) $matches[1];

                return $status >= 200 && $status < 500;
            }
        }

        return false;
    }

    protected function checkFakeRender(): bool
    {
        try {
            $driver = new FakeDriver;
            $result = $driver->render('<h1>Test</h1>', new RenderOptions);

            $passed = str_contains($result, 'FAKE_PDF');
            $this->check('Test render (fake driver)', $passed, 'Internal error — FakeDriver not working');

            return $passed;
        } catch (\Throwable $e) {
            $this->check('Test render (fake driver)', false, $e->getMessage());

            return false;
        }
    }

    protected function checkConfiguredFonts(): bool
    {
        /** @var FontRegistry $fonts */
        $fonts = $this->laravel->make(FontRegistry::class);

        if ($fonts->all() === []) {
            $this->warn('  [SKIP] No custom fonts configured');

            return true;
        }

        $allPassed = true;

        foreach ($fonts->all() as $font) {
            if ($font->sources === []) {
                $allPassed = $this->check(
                    "Font [{$font->name}] sources configured",
                    false,
                    'Add at least one local font file path under pdf-studio.fonts'
                ) && $allPassed;

                continue;
            }

            foreach ($font->sources as $source) {
                $label = "Font [{$font->name}] source ({$source})";
                $allPassed = $this->check(
                    $label,
                    is_file($source) && is_readable($source),
                    'Ensure the font file exists and is readable by PHP'
                ) && $allPassed;
            }
        }

        return $allPassed;
    }

    protected function binaryExists(string $path): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            $code = -1;
            @exec('where '.escapeshellarg(basename($path)).' 2>&1', $output, $code);

            return $code === 0;
        }

        return is_executable($path);
    }

    protected function parseMemory(string $value): int
    {
        $value = trim($value);

        if ($value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
