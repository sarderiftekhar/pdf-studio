<?php

namespace PdfStudio\Laravel\Drivers;

use Dompdf\Dompdf;
use Dompdf\Options;
use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;

class DompdfDriver implements RendererContract
{
    /** @var array<string, mixed> */
    protected array $config;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        if (! class_exists(Dompdf::class)) {
            throw new DriverException(
                'The dompdf driver requires dompdf/dompdf. Install it with: composer require dompdf/dompdf'
            );
        }

        $this->config = $config;
    }

    public function render(string $html, RenderOptions $options): string
    {
        $dompdfOptions = new Options;
        $dompdfOptions->setIsRemoteEnabled(true);

        if (isset($this->config['options']) && is_array($this->config['options'])) {
            foreach ($this->config['options'] as $key => $value) {
                $setter = 'set'.ucfirst($key);
                if (method_exists($dompdfOptions, $setter)) {
                    $dompdfOptions->{$setter}($value);
                }
            }
        }

        $dompdf = new Dompdf($dompdfOptions);
        $dompdf->loadHtml($html);

        $orientation = $options->landscape ? 'landscape' : 'portrait';
        $paper = $this->config['paper'] ?? $options->format;
        $dompdf->setPaper($paper, $orientation);

        $dompdf->render();

        $output = $dompdf->output();

        if ($output === null) {
            throw new DriverException('Dompdf failed to generate PDF output.');
        }

        return $output;
    }

    public function supports(): DriverCapabilities
    {
        return new DriverCapabilities(
            landscape: true,
            customMargins: true,
            headerFooter: false,
            printBackground: false,
            supportedFormats: ['A4', 'Letter', 'Legal', 'A3', 'A5'],
        );
    }
}
