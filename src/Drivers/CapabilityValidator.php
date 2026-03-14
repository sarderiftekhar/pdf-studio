<?php

namespace PdfStudio\Laravel\Drivers;

use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;

class CapabilityValidator
{
    /**
     * Validate render options against driver capabilities.
     *
     * @return array<int, string> List of warning messages
     */
    public static function validate(RenderOptions $options, DriverCapabilities $capabilities): array
    {
        $warnings = [];

        if (($options->headerHtml !== null || $options->footerHtml !== null) && !$capabilities->headerFooter) {
            $warnings[] = 'This driver does not support custom header/footer HTML. The header/footer options will be ignored.';
        }

        if ($options->printBackground && !$capabilities->printBackground) {
            $warnings[] = 'This driver does not support printing background graphics. The printBackground option will be ignored.';
        }

        if ($options->pageRanges !== null && !$capabilities->pageRanges) {
            $warnings[] = 'This driver does not support page ranges. The pageRanges option will be ignored.';
        }

        if ($options->preferCssPageSize && !$capabilities->preferCssPageSize) {
            $warnings[] = 'This driver does not support CSS-defined page sizes. The preferCssPageSize option will be ignored.';
        }

        if ($options->scale !== 1.0 && !$capabilities->scale) {
            $warnings[] = 'This driver does not support PDF scaling. The scale option will be ignored.';
        }

        if ($options->waitForFonts && !$capabilities->waitForFonts) {
            $warnings[] = 'This driver does not support waiting for fonts before rendering. The waitForFonts option will be ignored.';
        }

        if ($options->waitUntil !== null && !$capabilities->waitUntil) {
            $warnings[] = 'This driver does not support navigation readiness events. The waitUntil option will be ignored.';
        }

        if ($options->waitDelayMs !== null && !$capabilities->waitDelay) {
            $warnings[] = 'This driver does not support delayed rendering. The waitDelay option will be ignored.';
        }

        if ($options->waitForSelector !== null && !$capabilities->waitForSelector) {
            $warnings[] = 'This driver does not support waiting for a selector before rendering. The waitForSelector option will be ignored.';
        }

        if ($options->waitForFunction !== null && !$capabilities->waitForFunction) {
            $warnings[] = 'This driver does not support waiting for a JavaScript function before rendering. The waitForFunction option will be ignored.';
        }

        if ($options->taggedPdf && !$capabilities->taggedPdf) {
            $warnings[] = 'This driver does not support tagged PDFs. The taggedPdf option will be ignored.';
        }

        if ($options->outline && !$capabilities->outline) {
            $warnings[] = 'This driver does not support PDF outlines/bookmarks. The outline option will be ignored.';
        }

        if ($options->metadata !== [] && !$capabilities->metadata) {
            $warnings[] = 'This driver does not support PDF metadata. The metadata option will be ignored.';
        }

        if ($options->attachments !== [] && !$capabilities->attachments) {
            $warnings[] = 'This driver does not support PDF attachments. The attachments option will be ignored.';
        }

        if ($options->pdfVariant !== null && !$capabilities->pdfVariants) {
            $warnings[] = 'This driver does not support PDF variants such as PDF/A or PDF/UA. The pdfVariant option will be ignored.';
        }

        if (!in_array($options->format, $capabilities->supportedFormats, true)) {
            $warnings[] = "The format '{$options->format}' is not in this driver's supported formats: ".implode(', ', $capabilities->supportedFormats).'.';
        }

        return $warnings;
    }
}
