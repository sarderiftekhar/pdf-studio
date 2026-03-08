<?php

namespace PdfStudio\Laravel\Barcode;

use Picqer\Barcode\BarcodeGenerator as PicqerBarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorSVG;
use PdfStudio\Laravel\Exceptions\RenderException;

class BarcodeGenerator
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(string $type, string $value, array $options = []): string
    {
        if (! class_exists(BarcodeGeneratorSVG::class)) {
            throw new RenderException(
                'The picqer/php-barcode-generator package is required for barcode generation. '
                .'Install it with: composer require picqer/php-barcode-generator'
            );
        }

        $generator = new BarcodeGeneratorSVG;
        $barcodeType = $this->resolveType($type);
        $width = $options['width'] ?? 2;
        $height = $options['height'] ?? 50;

        return $generator->getBarcode($value, $barcodeType, $width, $height);
    }

    protected function resolveType(string $type): string
    {
        return match (strtoupper($type)) {
            'CODE128', 'C128' => PicqerBarcodeGenerator::TYPE_CODE_128,
            'CODE39', 'C39' => PicqerBarcodeGenerator::TYPE_CODE_39,
            'EAN13' => PicqerBarcodeGenerator::TYPE_EAN_13,
            'EAN8' => PicqerBarcodeGenerator::TYPE_EAN_8,
            'UPCA' => PicqerBarcodeGenerator::TYPE_UPC_A,
            'UPCE' => PicqerBarcodeGenerator::TYPE_UPC_E,
            'CODE93' => PicqerBarcodeGenerator::TYPE_CODE_93,
            'ITF14' => PicqerBarcodeGenerator::TYPE_ITF_14,
            default => $type,
        };
    }
}
