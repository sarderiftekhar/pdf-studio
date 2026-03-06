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

            if ($options->headerHtml !== null) {
                $browsershot->headerHtml($options->headerHtml);
            }

            if ($options->footerHtml !== null) {
                $browsershot->footerHtml($options->footerHtml);
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

        if (isset($this->config['timeout'])) {
            $browsershot->timeout((int) $this->config['timeout']);
        }

        return $browsershot->pdf();
    }

    public function supports(): DriverCapabilities
    {
        return new DriverCapabilities(
            landscape: true,
            customMargins: true,
            headerFooter: true,
            printBackground: true,
            supportedFormats: ['A4', 'Letter', 'Legal', 'Tabloid', 'Ledger', 'A0', 'A1', 'A2', 'A3', 'A5', 'A6'],
        );
    }
}
