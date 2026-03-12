<?php

namespace PdfStudio\Laravel\Drivers;

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;

class WeasyPrintDriver implements RendererContract
{
    protected string $binary;

    protected int $timeout;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {
        $this->binary = (string) ($config['binary'] ?? 'weasyprint');
        $this->timeout = (int) ($config['timeout'] ?? 60);
    }

    public function render(string $html, RenderOptions $options): string
    {
        $styledHtml = $this->injectPrintStyles($html, $options);
        $styledHtml = $this->injectMetadata($styledHtml, $options);

        $htmlFile = tempnam(sys_get_temp_dir(), 'pdfstudio_weasy_html_');
        $pdfFile = tempnam(sys_get_temp_dir(), 'pdfstudio_weasy_pdf_');

        if ($htmlFile === false || $pdfFile === false) {
            throw new DriverException('Failed to create temporary files for WeasyPrint rendering.');
        }

        try {
            if (file_put_contents($htmlFile, $styledHtml) === false) {
                throw new DriverException('Failed to write temporary HTML for WeasyPrint rendering.');
            }

            $command = $this->buildCommand($htmlFile, $pdfFile, $options);
            $this->executeCommand($command, $this->timeout);

            $pdf = file_get_contents($pdfFile);

            if ($pdf === false || $pdf === '') {
                throw new DriverException('WeasyPrint produced empty output.');
            }

            if (!str_starts_with($pdf, '%PDF')) {
                throw new DriverException('WeasyPrint returned unexpected output instead of PDF bytes.');
            }

            return $pdf;
        } finally {
            @unlink($htmlFile);
            @unlink($pdfFile);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function buildCommand(string $htmlFile, string $pdfFile, RenderOptions $options): array
    {
        $command = [
            $this->binary,
            $htmlFile,
            $pdfFile,
        ];

        if ($options->attachments !== []) {
            foreach ($options->attachments as $attachment) {
                $path = $attachment['path'] ?? null;

                if (!is_string($path) || $path === '' || !is_file($path)) {
                    throw new DriverException('WeasyPrint attachments require a valid file path.');
                }

                $command[] = '--attachment';
                $command[] = $path;
            }
        }

        if ($options->pdfVariant !== null) {
            $command[] = '--pdf-variant';
            $command[] = $options->pdfVariant;
        }

        if ($options->metadata !== []) {
            $command[] = '--custom-metadata';
        }

        if ($options->taggedPdf) {
            $command[] = '--pdf-tags';
        }

        return $command;
    }

    protected function injectPrintStyles(string $html, RenderOptions $options): string
    {
        $pageCss = sprintf(
            '@page { size: %s%s; margin: %smm %smm %smm %smm; }',
            $options->format,
            $options->landscape ? ' landscape' : '',
            $options->margins['top'],
            $options->margins['right'],
            $options->margins['bottom'],
            $options->margins['left'],
        );

        $styleTag = "<style>{$pageCss}</style>";

        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $styleTag.'</head>', $html);
        }

        return "<!DOCTYPE html>\n<html>\n<head>{$styleTag}</head>\n<body>{$html}</body>\n</html>";
    }

    protected function injectMetadata(string $html, RenderOptions $options): string
    {
        if ($options->metadata === []) {
            return $html;
        }

        $tags = '';

        foreach ($options->metadata as $name => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            $content = htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
            $metaName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $tags .= "<meta name=\"{$metaName}\" content=\"{$content}\">";
        }

        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $tags.'</head>', $html);
        }

        return "<!DOCTYPE html>\n<html>\n<head>{$tags}</head>\n<body>{$html}</body>\n</html>";
    }

    /**
     * @param  array<int, string>  $command
     */
    protected function executeCommand(array $command, int $timeout): void
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptorSpec, $pipes);

        if (!is_resource($process)) {
            throw new DriverException('Unable to start WeasyPrint process.');
        }

        fclose($pipes[0]);

        $start = microtime(true);
        $stdout = '';
        $stderr = '';

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        while (true) {
            $status = proc_get_status($process);
            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';

            if (!$status['running']) {
                break;
            }

            if ((microtime(true) - $start) > $timeout) {
                proc_terminate($process, 9);
                throw new DriverException("WeasyPrint rendering timed out after {$timeout} seconds.");
            }

            usleep(10000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $message = trim($stderr !== '' ? $stderr : $stdout);
            throw new DriverException("WeasyPrint rendering failed: {$message}");
        }
    }

    public function supports(): DriverCapabilities
    {
        return new DriverCapabilities(
            landscape: true,
            customMargins: true,
            headerFooter: false,
            printBackground: true,
            supportedFormats: ['Letter', 'Legal', 'Tabloid', 'Ledger', 'A0', 'A1', 'A2', 'A3', 'A4', 'A5', 'A6'],
            autoHeight: false,
            pageRanges: false,
            preferCssPageSize: true,
            scale: false,
            waitForFonts: false,
            waitUntil: false,
            waitDelay: false,
            waitForSelector: false,
            waitForFunction: false,
            taggedPdf: true,
            outline: false,
            metadata: true,
            attachments: true,
            pdfVariants: true,
        );
    }
}
