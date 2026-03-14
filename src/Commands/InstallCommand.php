<?php

namespace PdfStudio\Laravel\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    protected $signature = 'pdf-studio:install {--all : Install all optional dependencies}';

    protected $description = 'Install optional PDF Studio dependencies';

    /** @var array<string, array{package: string, class: string, description: string}> */
    protected array $features = [
        'Chromium PDF driver' => [
            'package' => 'spatie/browsershot',
            'class' => \Spatie\Browsershot\Browsershot::class,
            'description' => 'Render PDFs via headless Chrome',
        ],
        'Dompdf PDF driver' => [
            'package' => 'dompdf/dompdf',
            'class' => \Dompdf\Dompdf::class,
            'description' => 'Render PDFs via dompdf',
        ],
        'PDF manipulation' => [
            'package' => 'setasign/fpdi',
            'class' => \setasign\Fpdi\Fpdi::class,
            'description' => 'Merge, watermark, split, and reorder PDFs',
        ],
        'Form filling & protection' => [
            'package' => 'mikehaertl/php-pdftk',
            'class' => \mikehaertl\pdftk\Pdf::class,
            'description' => 'AcroForm filling and password protection',
        ],
        'Barcodes' => [
            'package' => 'picqer/php-barcode-generator',
            'class' => \Picqer\Barcode\BarcodeGeneratorSVG::class,
            'description' => '@barcode Blade directive',
        ],
        'QR codes' => [
            'package' => 'chillerlan/php-qrcode',
            'class' => \chillerlan\QRCode\QRCode::class,
            'description' => '@qrcode Blade directive',
        ],
    ];

    public function handle(): int
    {
        $this->info('PDF Studio Dependency Installer');
        $this->line('');

        $toInstall = $this->option('all')
            ? $this->getAllMissing()
            : $this->promptForFeatures();

        if ($toInstall === []) {
            $this->info('Nothing to install — all selected dependencies are already present.');

            return self::SUCCESS;
        }

        $packages = implode(' ', $toInstall);
        $this->info("Installing: {$packages}");
        $this->line('');

        $process = new Process(
            ['composer', 'require', ...$toInstall],
            base_path(),
        );
        $process->setTimeout(300);

        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        $this->line('');

        if (! $process->isSuccessful()) {
            $this->error('Composer install failed. See output above for details.');

            return self::FAILURE;
        }

        $this->info('Dependencies installed successfully!');
        $this->showImagickReminder();

        return self::SUCCESS;
    }

    /** @return list<string> */
    protected function getAllMissing(): array
    {
        $missing = [];

        foreach ($this->features as $name => $feature) {
            if (class_exists($feature['class'])) {
                $this->line("  <info>[SKIP]</info> {$name} — already installed");
            } else {
                $missing[] = $feature['package'];
            }
        }

        $this->line('');

        return $missing;
    }

    /** @return list<string> */
    protected function promptForFeatures(): array
    {
        $options = [];

        foreach ($this->features as $name => $feature) {
            $installed = class_exists($feature['class']);
            $status = $installed ? ' (installed)' : '';
            $options[$name] = "{$name} — {$feature['description']}{$status}";
        }

        /** @var list<string> $selected */
        $selected = $this->choice(
            'Which features would you like to install?',
            array_values($options),
            null,
            null,
            true,
        );

        $packages = [];

        foreach ($selected as $selection) {
            foreach ($this->features as $name => $feature) {
                if ($options[$name] === $selection) {
                    if (class_exists($feature['class'])) {
                        $this->line("  <info>[SKIP]</info> {$name} — already installed");
                    } else {
                        $packages[] = $feature['package'];
                    }
                    break;
                }
            }
        }

        $this->line('');

        return $packages;
    }

    protected function showImagickReminder(): void
    {
        if (! extension_loaded('imagick')) {
            $this->line('');
            $this->warn('Note: For PDF thumbnail generation, you also need the imagick PHP extension.');
            $this->line('  This must be installed separately (not via Composer).');
        }
    }
}
