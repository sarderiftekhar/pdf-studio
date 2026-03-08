<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Contracts\WatermarkerContract;
use PdfStudio\Laravel\DTOs\WatermarkOptions;
use PdfStudio\Laravel\Exceptions\ManipulationException;
use setasign\Fpdi\Fpdi;

class PdfWatermarker implements WatermarkerContract
{
    public function apply(string $pdfContent, WatermarkOptions $options): string
    {
        if (!class_exists(Fpdi::class)) {
            throw new ManipulationException(
                'Watermarking requires setasign/fpdi. Install it with: composer require setasign/fpdi'
            );
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'pdfstudio_wm_');
        file_put_contents($tempFile, $pdfContent);

        try {
            $fpdi = new Fpdi;
            $pageCount = $fpdi->setSourceFile($tempFile);

            for ($i = 1; $i <= $pageCount; $i++) {
                $templateId = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);

                if ($options->text !== null) {
                    $this->applyTextWatermark($fpdi, $options, $size['width'], $size['height']);
                } elseif ($options->imagePath !== null) {
                    $this->applyImageWatermark($fpdi, $options, $size['width'], $size['height']);
                }
            }

            return $fpdi->Output('S');
        } finally {
            @unlink($tempFile);
        }
    }

    protected function applyTextWatermark(Fpdi $fpdi, WatermarkOptions $options, float $width, float $height): void
    {
        $fpdi->SetFont('Helvetica', 'B', $options->fontSize);

        $rgb = $this->hexToRgb($options->color);
        $fpdi->SetTextColor($rgb[0], $rgb[1], $rgb[2]);

        $textWidth = $fpdi->GetStringWidth($options->text ?? '');
        $x = ($width - $textWidth) / 2;
        $y = $height / 2;

        if ($options->position === 'top-left') {
            $x = 10;
            $y = 20;
        } elseif ($options->position === 'top-right') {
            $x = $width - $textWidth - 10;
            $y = 20;
        } elseif ($options->position === 'bottom-left') {
            $x = 10;
            $y = $height - 20;
        } elseif ($options->position === 'bottom-right') {
            $x = $width - $textWidth - 10;
            $y = $height - 20;
        }

        $fpdi->SetXY($x, $y);
        $fpdi->Cell($textWidth, $options->fontSize / 2, $options->text ?? '', 0, 0, 'C');
    }

    protected function applyImageWatermark(Fpdi $fpdi, WatermarkOptions $options, float $width, float $height): void
    {
        if ($options->imagePath === null || !file_exists($options->imagePath)) {
            return;
        }

        $imgWidth = $width * 0.3;
        $x = ($width - $imgWidth) / 2;
        $y = ($height - $imgWidth) / 2;

        if ($options->position === 'top-left') {
            $x = 10;
            $y = 10;
        } elseif ($options->position === 'top-right') {
            $x = $width - $imgWidth - 10;
            $y = 10;
        } elseif ($options->position === 'bottom-left') {
            $x = 10;
            $y = $height - $imgWidth - 10;
        } elseif ($options->position === 'bottom-right') {
            $x = $width - $imgWidth - 10;
            $y = $height - $imgWidth - 10;
        }

        $fpdi->Image($options->imagePath, $x, $y, $imgWidth);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @throws ManipulationException
     */
    public function output(): never
    {
        throw new ManipulationException('Use apply() to produce output.');
    }
}
