<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Output\PdfResult;
use PdfStudio\Laravel\Output\StorageResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
    $this->app['view']->addNamespace('pdf-test', __DIR__.'/../stubs/views');
});

it('renders a view and returns PdfResult', function () {
    $result = Pdf::view('pdf-test::simple')
        ->data(['name' => 'Render'])
        ->render();

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toContain('FAKE_PDF')
        ->and($result->content())->toContain('Hello Render')
        ->and($result->driver)->toBe('fake')
        ->and($result->renderTimeMs)->toBeGreaterThanOrEqual(0);
});

it('renders raw HTML and returns PdfResult', function () {
    $result = Pdf::html('<h1>Raw</h1>')->render();

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toContain('<h1>Raw</h1>');
});

it('downloads a PDF', function () {
    $response = Pdf::html('<p>Download</p>')->download('test.pdf');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->headers->get('Content-Disposition'))->toContain('test.pdf');
});

it('streams a PDF', function () {
    $response = Pdf::html('<p>Stream</p>')->stream('preview.pdf');

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('Content-Disposition'))->toContain('preview.pdf');
});

it('saves a PDF to storage', function () {
    Storage::fake('local');

    $storageResult = Pdf::html('<p>Save</p>')->save('output.pdf', 'local');

    expect($storageResult)->toBeInstanceOf(StorageResult::class)
        ->and($storageResult->path)->toBe('output.pdf');

    Storage::disk('local')->assertExists('output.pdf');
});

it('uses a custom driver', function () {
    $result = Pdf::html('<p>Custom</p>')
        ->driver('fake')
        ->render();

    expect($result->driver)->toBe('fake');
});

it('passes render options through to the driver', function () {
    $result = Pdf::html('<p>Options</p>')
        ->format('Letter')
        ->landscape()
        ->margins(top: 20)
        ->render();

    expect($result)->toBeInstanceOf(PdfResult::class);
});

it('composes multiple documents and merges them', function () {
    $merger = new class implements \PdfStudio\Laravel\Contracts\MergerContract
    {
        public array $sources = [];

        public function merge(array $sources): PdfResult
        {
            $this->sources = $sources;

            return new PdfResult(
                content: 'FAKE_COMPOSED_PDF',
                driver: 'fake-merger',
                renderTimeMs: 0,
            );
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Contracts\MergerContract::class, $merger);

    $result = Pdf::compose([
        [
            'html' => '<h1>Cover</h1>',
        ],
        [
            'view' => 'pdf-test::simple',
            'data' => ['name' => 'Composed'],
            'options' => [
                'format' => 'Letter',
                'metadata' => ['title' => 'Section Two'],
            ],
        ],
    ], 'fake');

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toBe('FAKE_COMPOSED_PDF')
        ->and($merger->sources)->toHaveCount(2)
        ->and($merger->sources[0])->toBeInstanceOf(PdfResult::class)
        ->and($merger->sources[1])->toBeInstanceOf(PdfResult::class)
        ->and($merger->sources[1]->content())->toContain('Hello Composed');
});

it('throws when composed document input is invalid', function () {
    Pdf::compose([
        ['data' => ['name' => 'Missing source']],
    ]);
})->throws(\InvalidArgumentException::class, 'either [view] or [html]');

it('splits an existing pdf through the builder', function () {
    $splitter = new class
    {
        public function split(string $pdfContent, array $ranges): array
        {
            return [
                new PdfResult(content: 'PART_1', driver: 'fpdi-splitter', renderTimeMs: 0),
                new PdfResult(content: 'PART_2', driver: 'fpdi-splitter', renderTimeMs: 0),
            ];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfSplitter::class, $splitter);

    $results = Pdf::split('%PDF-fake', ['1-2', '3-4']);

    expect($results)->toHaveCount(2)
        ->and($results[0])->toBeInstanceOf(PdfResult::class)
        ->and($results[0]->content())->toBe('PART_1')
        ->and($results[1]->content())->toBe('PART_2');
});

it('flattens an existing pdf through the builder', function () {
    $flattener = new class
    {
        public function flatten(string $pdfContent): PdfResult
        {
            return new PdfResult(
                content: 'FLATTENED_PDF',
                driver: 'pdftk-flattener',
                renderTimeMs: 0,
            );
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfFlattener::class, $flattener);

    $result = Pdf::flattenPdf('%PDF-fake');

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toBe('FLATTENED_PDF')
        ->and($result->driver)->toBe('pdftk-flattener');
});

it('chunks an existing pdf through the builder', function () {
    $chunker = new class
    {
        public function chunk(string $pdfContent, int $pagesPerChunk): array
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($pagesPerChunk)->toBe(2);

            return [
                new PdfResult(content: 'CHUNK_1', driver: 'fpdi-splitter', renderTimeMs: 0),
                new PdfResult(content: 'CHUNK_2', driver: 'fpdi-splitter', renderTimeMs: 0),
            ];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfChunker::class, $chunker);

    $results = Pdf::chunk('%PDF-fake', 2);

    expect($results)->toHaveCount(2)
        ->and($results[0])->toBeInstanceOf(PdfResult::class)
        ->and($results[0]->content())->toBe('CHUNK_1')
        ->and($results[1]->content())->toBe('CHUNK_2');
});

it('embeds files into an existing pdf through the builder', function () {
    $embedder = new class
    {
        public function embed(string $pdfContent, array $files): PdfResult
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($files)->toHaveCount(1);
            expect($files[0]['name'])->toBe('report.csv');

            return new PdfResult(
                content: 'EMBEDDED_PDF',
                driver: 'gotenberg-embedder',
                renderTimeMs: 0,
            );
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfEmbedder::class, $embedder);

    $result = Pdf::embedFiles('%PDF-fake', [[
        'path' => '/tmp/report.csv',
        'name' => 'report.csv',
        'mime' => 'text/csv',
    ]]);

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toBe('EMBEDDED_PDF')
        ->and($result->driver)->toBe('gotenberg-embedder');
});
