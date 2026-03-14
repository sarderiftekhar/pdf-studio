<?php

namespace PdfStudio\Laravel\Drivers;

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;
use Spatie\Browsershot\Browsershot;

class ChromiumDriver implements RendererContract
{
    /** @var array<string, mixed> */
    protected array $config;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        if (!class_exists(Browsershot::class)) {
            throw new DriverException(
                'The Chromium driver requires spatie/browsershot. Install it with: composer require spatie/browsershot'
            );
        }

        $this->config = $config;
    }

    public function render(string $html, RenderOptions $options): string
    {
        $browsershot = $this->createBrowsershot($html, $options);

        if ($options->autoHeight) {
            return $this->renderWithAutoHeight($browsershot, $html, $options);
        }

        return $browsershot->pdf();
    }

    protected function renderWithAutoHeight(Browsershot $browsershot, string $html, RenderOptions $options): string
    {
        // Two-pass render: first measure content height, then render with exact height
        $measurer = $this->createBrowsershot($html, $options);
        $height = (int) $measurer->evaluate('() => document.body.scrollHeight');
        $height = min($height + $options->margins['top'] + $options->margins['bottom'], $options->maxHeight);

        // A4 width in mm = 210, convert to approximate pixel width
        $widthMm = $options->landscape ? 297 : 210;

        $browsershot->paperSize($widthMm, $height)
            ->pages('1');

        return $browsershot->pdf();
    }

    protected function createBrowsershot(string $html, RenderOptions $options): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->format($options->format)
            ->margins(
                $options->margins['top'],
                $options->margins['right'],
                $options->margins['bottom'],
                $options->margins['left'],
            );

        if ($options->landscape) {
            $browsershot->landscape();
        }

        if ($options->printBackground) {
            $browsershot->showBackground();
        }

        if ($options->headerHtml !== null || $options->footerHtml !== null) {
            $browsershot->showBrowserHeaderAndFooter();

            $headerHtml = $options->headerHtml;
            $footerHtml = $options->footerHtml;

            // Per-page header/footer control via JS injection
            if ($headerHtml !== null) {
                $headerHtml = $this->injectPerPageControl($headerHtml, $options, 'header');
            }

            if ($footerHtml !== null) {
                $footerHtml = $this->injectPerPageControl($footerHtml, $options, 'footer');
            }

            if ($headerHtml !== null) {
                $browsershot->headerHtml($headerHtml);
            }

            if ($footerHtml !== null) {
                $browsershot->footerHtml($footerHtml);
            }
        }

        if (isset($this->config['binary'])) {
            $browsershot->setChromePath((string) $this->config['binary']);
        }

        if (isset($this->config['node_binary'])) {
            $browsershot->setNodeBinary((string) $this->config['node_binary']);
        }

        if (isset($this->config['npm_binary'])) {
            $browsershot->setNpmBinary((string) $this->config['npm_binary']);
        }

        if (isset($this->config['node_module_path'])) {
            $browsershot->setNodeModulePath((string) $this->config['node_module_path']);
        }

        if (!empty($this->config['no_sandbox'])) {
            $browsershot->noSandbox();
        }

        if (!empty($this->config['chromium_args'])) {
            $browsershot->addChromiumArguments((array) $this->config['chromium_args']);
        }

        if (isset($this->config['timeout'])) {
            $browsershot->timeout((int) $this->config['timeout']);
        }

        return $browsershot;
    }

    /**
     * Inject JavaScript into header/footer HTML for per-page visibility control.
     */
    protected function injectPerPageControl(string $html, RenderOptions $options, string $type): string
    {
        $conditions = [];

        if ($type === 'header') {
            if ($options->headerExceptFirst) {
                $conditions[] = 'pageNum === 1';
            }
            if (!empty($options->headerExcludePages)) {
                $pages = json_encode($options->headerExcludePages);
                $conditions[] = "{$pages}.includes(pageNum)";
            }
            if ($options->headerOnPages !== null) {
                $pages = json_encode($options->headerOnPages);
                $conditions[] = "!{$pages}.includes(pageNum)";
            }
        }

        if ($type === 'footer') {
            if ($options->footerExceptLast) {
                $conditions[] = 'pageNum === totalPages';
            }
            if (!empty($options->footerExcludePages)) {
                $pages = json_encode($options->footerExcludePages);
                $conditions[] = "{$pages}.includes(pageNum)";
            }
        }

        if (empty($conditions)) {
            return $html;
        }

        $hideCondition = implode(' || ', $conditions);

        $script = <<<JS
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var pageNum = parseInt(document.querySelector('.pageNumber')?.textContent || '1');
            var totalPages = parseInt(document.querySelector('.totalPages')?.textContent || '1');
            if ({$hideCondition}) {
                document.body.style.display = 'none';
            }
        });
        </script>
        JS;

        // Inject before closing </head> or </html> tag, or append
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
            supportedFormats: ['A4', 'Letter', 'Legal', 'Tabloid', 'Ledger', 'A0', 'A1', 'A2', 'A3', 'A5', 'A6'],
            autoHeight: true,
        );
    }
}
