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
        $args = $this->buildArguments($options);

        // wkhtmltopdf reads from stdin with '-' and writes to stdout with '-'
        $command = array_merge([$this->binary], $args, ['-', '-']);

        $process = new Process($command);
        $process->setTimeout($this->timeout);
        $process->setInput($html);

        $process->run();

        if (! $process->isSuccessful()) {
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
     * @return array<int, string>
     */
    protected function buildArguments(RenderOptions $options): array
    {
        $args = [];

        $args[] = '--page-size';
        $args[] = $options->format;

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
            $args[] = '--header-html';
            $args[] = $options->headerHtml;
        }

        if ($options->footerHtml !== null) {
            $args[] = '--footer-html';
            $args[] = $options->footerHtml;
        }

        $args[] = '--quiet';

        return $args;
    }

    public function supports(): DriverCapabilities
    {
        return new DriverCapabilities(
            landscape: true,
            customMargins: true,
            headerFooter: true,
            printBackground: true,
            supportedFormats: ['A4', 'Letter', 'Legal', 'A3', 'A5'],
        );
    }
}
