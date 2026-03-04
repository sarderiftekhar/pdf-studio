<?php

namespace PdfStudio\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use PdfStudio\Laravel\PdfBuilder;

/**
 * @method static PdfBuilder view(string $view)
 * @method static PdfBuilder html(string $html)
 *
 * @see PdfBuilder
 */
class Pdf extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PdfBuilder::class;
    }
}
