<?php

namespace PdfStudio\Laravel\Drivers;

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;
use Symfony\Component\Process\Process;

class WkhtmlDriver implements RendererContract
{
    protected string $binary;

    protected int $timeout;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->binary = $config['binary'] ?? '/usr/local/bin/wkhtmltopdf';
        $this->timeout = (int) ($config['timeout'] ?? 60);
    }

    public function render(string $html, RenderOptions $options): string
    {
        [$args, $tempFiles] = $this->buildArguments($options);

        // wkhtmltopdf reads from stdin with '-' and writes to stdout with '-'
        $command = array_merge([$this->binary], $args, ['-', '-']);

        $process = new Process($command);
        $process->setTimeout($this->timeout);
        $process->setInput($html);

        $process->run();

        foreach ($tempFiles as $tempFile) {
            @unlink($tempFile);
        }

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput() ?: $process->getOutput();

            throw new DriverException("wkhtmltopdf rendering failed: {$error}");
        }

        $output = $process->getOutput();

        if (empty($output)) {
            throw new DriverException('wkhtmltopdf produced empty output.');
        }

        return $output;
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    /**
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    protected function buildArguments(RenderOptions $options): array
    {
        $args = [];
        $tempFiles = [];

        if ($options->autoHeight) {
            // Use custom page height for auto-height mode
            $widthMm = $options->landscape ? 297 : 210;
            $args[] = '--page-width';
            $args[] = (string) $widthMm;
            $args[] = '--page-height';
            $args[] = (string) $options->maxHeight;
            $args[] = '--disable-smart-shrinking';
        } else {
            $args[] = '--page-size';
            $args[] = $options->format;
        }

        if ($options->landscape) {
            $args[] = '--orientation';
            $args[] = 'Landscape';
        } else {
            $args[] = '--orientation';
            $args[] = 'Portrait';
        }

        $args[] = '--margin-top';
        $args[] = (string) $options->margins['top'];
        $args[] = '--margin-right';
        $args[] = (string) $options->margins['right'];
        $args[] = '--margin-bottom';
        $args[] = (string) $options->margins['bottom'];
        $args[] = '--margin-left';
        $args[] = (string) $options->margins['left'];

        if ($options->printBackground) {
            $args[] = '--print-media-type';
        }

        if ($options->headerHtml !== null) {
            $headerHtml = $this->injectPerPageControl($options->headerHtml, $options, 'header');
            $headerFile = tempnam(sys_get_temp_dir(), 'pdfstudio_header_').'.html';
            file_put_contents($headerFile, $headerHtml);
            $tempFiles[] = $headerFile;
            $args[] = '--header-html';
            $args[] = $headerFile;
        }

        if ($options->footerHtml !== null) {
            $footerHtml = $this->injectPerPageControl($options->footerHtml, $options, 'footer');
            $footerFile = tempnam(sys_get_temp_dir(), 'pdfstudio_footer_').'.html';
            file_put_contents($footerFile, $footerHtml);
            $tempFiles[] = $footerFile;
            $args[] = '--footer-html';
            $args[] = $footerFile;
        }

        $args[] = '--quiet';

        return [$args, $tempFiles];
    }

    /**
     * Inject JavaScript into header/footer HTML for per-page visibility control.
     * wkhtmltopdf passes page/topage query parameters to header/footer HTML.
     */
    protected function injectPerPageControl(string $html, RenderOptions $options, string $type): string
    {
        $conditions = [];

        if ($type === 'header') {
            if ($options->headerExceptFirst) {
                $conditions[] = 'page === 1';
            }
            if (!empty($options->headerExcludePages)) {
                $pages = json_encode($options->headerExcludePages);
                $conditions[] = "{$pages}.indexOf(page) !== -1";
            }
            if ($options->headerOnPages !== null) {
                $pages = json_encode($options->headerOnPages);
                $conditions[] = "{$pages}.indexOf(page) === -1";
            }
        }

        if ($type === 'footer') {
            if ($options->footerExceptLast) {
                $conditions[] = 'page === topage';
            }
            if (!empty($options->footerExcludePages)) {
                $pages = json_encode($options->footerExcludePages);
                $conditions[] = "{$pages}.indexOf(page) !== -1";
            }
        }

        if (empty($conditions)) {
            return $html;
        }

        $hideCondition = implode(' || ', $conditions);

        // wkhtmltopdf passes page info as query parameters
        $script = <<<JS
        <script>
        (function() {
            var params = new URLSearchParams(window.location.search);
            var page = parseInt(params.get('page') || '1');
            var topage = parseInt(params.get('topage') || '1');
            if ({$hideCondition}) {
                document.body.style.display = 'none';
            }
        })();
        </script>
        JS;

        if (str_contains($html, '</head>')) {
            return str_replace('</head>', $script.'</head>', $html);
        }

        return $html.$script;
    }

    public function supports(): DriverCapabilities
    {
        return new DriverCapabilities(
            landscape: true,
            customMargins: true,
            headerFooter: true,
            printBackground: true,
            supportedFormats: ['A4', 'Letter', 'Legal', 'A3', 'A5'],
            autoHeight: true,
            waitUntil: false,
            waitDelay: false,
        );
    }
}
