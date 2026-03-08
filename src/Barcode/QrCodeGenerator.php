<?php

namespace PdfStudio\Laravel\Barcode;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PdfStudio\Laravel\Exceptions\RenderException;

class QrCodeGenerator
{
    /**
     * Generate an SVG QR code for the given data.
     *
     * @param  array<string, mixed>  $options
     */
    public function generate(string $data, array $options = []): string
    {
        if (! class_exists(QRCode::class)) {
            throw new RenderException(
                'The chillerlan/php-qrcode package is required for QR code generation. '
                .'Install it with: composer require chillerlan/php-qrcode'
            );
        }

        $scale = $options['size'] ?? 5;
        $eccLevel = $this->resolveEccLevel($options['error_correction'] ?? 'M');

        $qrOptions = new QROptions([
            'eccLevel' => $eccLevel,
            'scale' => $scale,
            'addQuietzone' => true,
            'drawLightModules' => false,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
        ]);

        $qrCode = new QRCode($qrOptions);

        return $qrCode->render($data);
    }

    protected function resolveEccLevel(string $level): int
    {
        return match (strtoupper($level)) {
            'L' => EccLevel::L,
            'M' => EccLevel::M,
            'Q' => EccLevel::Q,
            'H' => EccLevel::H,
            default => EccLevel::M,
        };
    }
}
