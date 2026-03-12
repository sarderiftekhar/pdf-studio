<?php

namespace PdfStudio\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use PdfStudio\Laravel\PdfBuilder;
use PdfStudio\Laravel\Testing\PdfFake;

/**
 * @method static PdfBuilder view(string $view)
 * @method static PdfBuilder html(string $html)
 * @method static PdfBuilder template(string $name)
 * @method static PdfBuilder pageRanges(string $pageRanges)
 * @method static PdfBuilder preferCssPageSize(bool $prefer = true)
 * @method static PdfBuilder scale(float $scale)
 * @method static PdfBuilder waitForFonts(bool $wait = true)
 * @method static PdfBuilder waitUntil(string $event)
 * @method static PdfBuilder waitForNetworkIdle(bool $strict = true)
 * @method static PdfBuilder waitDelay(int $milliseconds)
 * @method static PdfBuilder waitForSelector(string $selector, array<string, mixed> $options = [])
 * @method static PdfBuilder waitForFunction(string $function, int $timeout = 0)
 * @method static PdfBuilder taggedPdf(bool $tagged = true)
 * @method static PdfBuilder outline(bool $outline = true)
 * @method static PdfBuilder metadata(array<string, scalar|null> $metadata)
 * @method static PdfBuilder pdfVariant(string $variant)
 * @method static PdfBuilder attachFile(string $path, ?string $name = null, ?string $mime = null)
 * @method static \PdfStudio\Laravel\Output\PdfResult render()
 * @method static \PdfStudio\Laravel\Output\PdfResult compose(array<int, array<string, mixed>> $documents, ?string $driver = null)
 * @method static array<int, \PdfStudio\Laravel\Output\PdfResult> split(string $pdfContent, array<int, string> $ranges)
 * @method static \PdfStudio\Laravel\Output\PdfResult flattenPdf(string $pdfContent)
 * @method static int pageCount(string $pdfContent)
 * @method static array<int, \PdfStudio\Laravel\Output\PdfResult> chunk(string $pdfContent, int $pagesPerChunk)
 * @method static \PdfStudio\Laravel\Output\PdfResult embedFiles(string $pdfContent, array<int, array<string, mixed>> $files)
 * @method static \Illuminate\Http\Response download(string $filename)
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse stream(string $filename)
 * @method static \PdfStudio\Laravel\Output\StorageResult save(string $path, ?string $disk = null)
 * @method static void batch(array<int, array<string, mixed>> $items, ?string $driver = null, ?string $disk = null)
 * @method static \PdfStudio\Laravel\Output\PdfResult merge(array<int, string|\PdfStudio\Laravel\Output\PdfResult|array<string, mixed>> $sources)
 * @method static PdfBuilder watermark(string $text, float $opacity = 0.3, int $rotation = -45, string $position = 'center', int $fontSize = 48, string $color = '#999999')
 * @method static PdfBuilder protect(?string $userPassword = null, ?string $ownerPassword = null, array<string> $permissions = [])
 * @method static PdfBuilder contentFit(int $maxHeight = 5000)
 * @method static PdfBuilder cache(?int $ttl = null)
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse livewireDownload(string $filename)
 * @method static \PdfStudio\Laravel\Manipulation\AcroFormBuilder acroform(string $pdfPath)
 * @method static PdfBuilder bootstrap()
 * @method static PdfBuilder tailwind()
 * @method static \PdfStudio\Laravel\Thumbnail\ThumbnailResult thumbnail(int $width = 300, string $format = 'png', int $quality = 85, int $page = 1)
 * @method static PdfBuilder withTableOfContents(int $depth = 6, string $title = 'Table of Contents', string $mode = 'auto')
 * @method static PdfBuilder withBookmarks()
 *
 * @see PdfBuilder
 */
class Pdf extends Facade
{
    public static function fake(): PdfFake
    {
        $fake = static::getFacadeApplication()->make(PdfFake::class);
        static::swap($fake);

        return $fake;
    }

    public static function thumbnailFromFile(string $path, int $page = 1, int $width = 300, string $format = 'png', int $quality = 85): \PdfStudio\Laravel\Thumbnail\ThumbnailResult
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new \PdfStudio\Laravel\Exceptions\RenderException("Cannot read file: {$path}");
        }

        $generator = static::getFacadeApplication()->make(\PdfStudio\Laravel\Thumbnail\ThumbnailGenerator::class);

        return $generator->generate($content, $page, $width, $format, $quality);
    }

    protected static function getFacadeAccessor(): string
    {
        return PdfBuilder::class;
    }
}
