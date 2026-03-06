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

        if (!in_array($options->format, $capabilities->supportedFormats, true)) {
            $warnings[] = "The format '{$options->format}' is not in this driver's supported formats: ".implode(', ', $capabilities->supportedFormats).'.';
        }

        return $warnings;
    }
}
