<?php

namespace PdfStudio\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use PdfStudio\Laravel\PdfBuilder;

/**
 * @method static PdfBuilder view(string $view)
 * @method static PdfBuilder html(string $html)
 * @method static PdfBuilder template(string $name)
 * @method static \PdfStudio\Laravel\Output\PdfResult render()
 * @method static \Illuminate\Http\Response download(string $filename)
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse stream(string $filename)
 * @method static \PdfStudio\Laravel\Output\StorageResult save(string $path, ?string $disk = null)
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
