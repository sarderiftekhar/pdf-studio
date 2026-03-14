<?php

namespace PdfStudio\Laravel\Testing;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\Output\PdfResult;
use PdfStudio\Laravel\Output\StorageResult;
use PdfStudio\Laravel\PdfBuilder;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfFake extends PdfBuilder
{
    /** @var array<int, array{view: string|null, html: string|null, driver: string, options: mixed, data: array<string, mixed>, output: string}> */
    protected array $renders = [];

    /** @var array<int, string> */
    protected array $downloads = [];

    /** @var array<int, array{path: string, disk: string|null}> */
    protected array $saves = [];

    /** @var array<int, array{sources: array<mixed>}> */
    protected array $merges = [];

    protected bool $wasWatermarked = false;

    protected bool $wasProtected = false;

    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function render(): PdfResult
    {
        $driverName = $this->getResolvedDriverName();
        $html = $this->context->rawHtml ?? '';
        $content = "FAKE_PDF\n".($this->context->viewName ?? $html);

        $this->renders[] = [
            'view' => $this->context->viewName,
            'html' => $this->context->rawHtml,
            'driver' => $driverName,
            'options' => clone $this->context->options,
            'data' => $this->context->data,
            'output' => $content,
        ];

        if ($this->context->options->watermark !== null) {
            $this->wasWatermarked = true;
        }

        if ($this->context->options->userPassword !== null || $this->context->options->ownerPassword !== null) {
            $this->wasProtected = true;
        }

        return new PdfResult(
            content: $content,
            driver: $driverName,
            renderTimeMs: 0,
        );
    }

    public function download(string $filename): \Illuminate\Http\Response
    {
        $this->downloads[] = $filename;

        return $this->render()->download($filename);
    }

    public function stream(string $filename): StreamedResponse
    {
        return $this->render()->stream($filename);
    }

    public function save(string $path, ?string $disk = null): StorageResult
    {
        $this->saves[] = ['path' => $path, 'disk' => $disk];
        $result = $this->render();

        return new StorageResult(
            path: $path,
            disk: $disk ?? config('filesystems.default', 'local'),
            bytes: $result->bytes,
        );
    }

    /**
     * @param  array<mixed>  $sources
     */
    public function merge(array $sources): PdfResult
    {
        $this->merges[] = ['sources' => $sources];

        return new PdfResult(
            content: 'FAKE_PDF_MERGED',
            driver: 'fake-merger',
            renderTimeMs: 0,
        );
    }

    // ---- Assertion Methods ----

    public function assertRendered(?Closure $callback = null): static
    {
        Assert::assertNotEmpty($this->renders, 'Expected at least one PDF render, but none occurred.');

        if ($callback !== null) {
            foreach ($this->renders as $render) {
                if ($callback($render)) {
                    return $this;
                }
            }
            Assert::fail('No rendered PDF matched the given callback.');
        }

        return $this;
    }

    public function assertRenderedView(string $viewName): static
    {
        $matched = collect($this->renders)->contains(fn (array $r) => $r['view'] === $viewName);

        Assert::assertTrue($matched, "Expected view [{$viewName}] to be rendered, but it was not.");

        return $this;
    }

    public function assertRenderedCount(int $count): static
    {
        Assert::assertCount($count, $this->renders, "Expected {$count} PDF renders, but got ".count($this->renders).'.');

        return $this;
    }

    public function assertDownloaded(string $filename): static
    {
        Assert::assertContains($filename, $this->downloads, "Expected [{$filename}] to be downloaded, but it was not.");

        return $this;
    }

    public function assertSavedTo(string $path, ?string $disk = null): static
    {
        $matched = collect($this->saves)->contains(function (array $s) use ($path, $disk) {
            if ($s['path'] !== $path) {
                return false;
            }

            return $disk === null || $s['disk'] === $disk;
        });

        Assert::assertTrue($matched, "Expected PDF to be saved to [{$path}]".($disk ? " on disk [{$disk}]" : '').'.');

        return $this;
    }

    public function assertSaved(string $path, ?string $disk = null): static
    {
        return $this->assertSavedTo($path, $disk);
    }

    public function assertDriverWas(string $driver): static
    {
        $matched = collect($this->renders)->contains(fn (array $r) => $r['driver'] === $driver);

        Assert::assertTrue($matched, "Expected driver [{$driver}] to be used, but it was not.");

        return $this;
    }

    public function assertContains(string $text): static
    {
        Assert::assertNotEmpty($this->renders, 'No renders to check content against.');

        $content = $this->lastRenderedContent();

        Assert::assertStringContainsString($text, $content, "Expected rendered PDF to contain [{$text}].");

        return $this;
    }

    public function assertContainsHtml(string $html): static
    {
        return $this->assertContains($html);
    }

    public function assertContainsText(string $text): static
    {
        Assert::assertNotEmpty($this->renders, 'No renders to check content against.');

        $content = strip_tags($this->lastRenderedContent());
        Assert::assertStringContainsString($text, $content, "Expected rendered text to contain [{$text}].");

        return $this;
    }

    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function assertHasMetadata(array $metadata): static
    {
        $lastRender = $this->lastRender();

        Assert::assertEquals(
            $metadata,
            $lastRender['options']->metadata,
            'Expected rendered PDF metadata to match the provided array.'
        );

        return $this;
    }

    public function assertPdfVariant(string $variant): static
    {
        $lastRender = $this->lastRender();

        Assert::assertSame(
            $variant,
            $lastRender['options']->pdfVariant,
            "Expected PDF variant [{$variant}] to be used."
        );

        return $this;
    }

    public function assertHasAttachment(string $path, ?string $name = null): static
    {
        $lastRender = $this->lastRender();

        $matched = collect($lastRender['options']->attachments)->contains(function (array $attachment) use ($path, $name) {
            if (($attachment['path'] ?? null) !== $path) {
                return false;
            }

            return $name === null || ($attachment['name'] ?? null) === $name;
        });

        Assert::assertTrue($matched, "Expected rendered PDF to include attachment [{$path}].");

        return $this;
    }

    public function assertMerged(): static
    {
        Assert::assertNotEmpty($this->merges, 'Expected at least one PDF merge, but none occurred.');

        return $this;
    }

    public function assertMergedCount(int $count): static
    {
        Assert::assertCount($count, $this->merges, "Expected {$count} PDF merges, but got ".count($this->merges).'.');

        return $this;
    }

    public function assertWatermarked(): static
    {
        Assert::assertTrue($this->wasWatermarked, 'Expected PDF to be watermarked, but it was not.');

        return $this;
    }

    public function assertProtected(): static
    {
        Assert::assertTrue($this->wasProtected, 'Expected PDF to be password protected, but it was not.');

        return $this;
    }

    public function assertNothingRendered(): static
    {
        Assert::assertEmpty($this->renders, 'Expected no PDF renders, but '.count($this->renders).' occurred.');

        return $this;
    }

    /**
     * @return array{view: string|null, html: string|null, driver: string, options: mixed, data: array<string, mixed>, output: string}
     */
    public function lastRender(): array
    {
        Assert::assertNotEmpty($this->renders, 'Expected at least one PDF render, but none occurred.');

        $render = end($this->renders);

        Assert::assertIsArray($render);

        return $render;
    }

    protected function lastRenderedContent(): string
    {
        $lastRender = $this->lastRender();

        return $lastRender['html'] ?? $lastRender['view'] ?? '';
    }
}
