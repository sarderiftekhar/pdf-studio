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

it('splits an existing pdf file through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_split_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $splitter = new class
    {
        public function split(string $pdfContent, array $ranges): array
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($ranges)->toBe(['1-2', '3-4']);

            return [
                new PdfResult(content: 'FILE_PART_1', driver: 'fpdi-splitter', renderTimeMs: 0),
                new PdfResult(content: 'FILE_PART_2', driver: 'fpdi-splitter', renderTimeMs: 0),
            ];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfSplitter::class, $splitter);

    $results = Pdf::splitFile($pdfPath, ['1-2', '3-4']);

    expect($results)->toHaveCount(2)
        ->and($results[0]->content())->toBe('FILE_PART_1')
        ->and($results[1]->content())->toBe('FILE_PART_2');

    @unlink($pdfPath);
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

it('flattens an existing pdf file through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_flatten_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $flattener = new class
    {
        public function flatten(string $pdfContent): PdfResult
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return new PdfResult(
                content: 'FLATTENED_PDF_FILE',
                driver: 'pdftk-flattener',
                renderTimeMs: 0,
            );
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfFlattener::class, $flattener);

    $result = Pdf::flattenPdfFile($pdfPath);

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toBe('FLATTENED_PDF_FILE');

    @unlink($pdfPath);
});

it('counts pages in an existing pdf through the builder', function () {
    $counter = new class
    {
        public function count(string $pdfContent): int
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return 9;
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfPageCounter::class, $counter);

    expect(Pdf::pageCount('%PDF-fake'))->toBe(9);
});

it('checks whether in-memory content looks like a pdf through the builder', function () {
    $validator = new class
    {
        public function isPdf(string $pdfContent): bool
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return true;
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfValidator::class, $validator);

    expect(Pdf::isPdf('%PDF-fake'))->toBeTrue();
});

it('asserts in-memory pdf content through the builder', function () {
    $validator = new class
    {
        public function assertPdf(string $pdfContent, string $label = 'content'): void
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($label)->toBe('upload');
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfValidator::class, $validator);

    Pdf::assertPdf('%PDF-fake', 'upload');

    expect(true)->toBeTrue();
});

it('counts pages in an existing pdf file through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_page_count_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $counter = new class
    {
        public function count(string $pdfContent): int
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return 11;
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfPageCounter::class, $counter);

    expect(Pdf::pageCountFile($pdfPath))->toBe(11);

    @unlink($pdfPath);
});

it('checks whether a pdf file looks like a pdf through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_is_pdf_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $validator = new class
    {
        public function isPdf(string $pdfContent): bool
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return true;
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfValidator::class, $validator);

    expect(Pdf::isPdfFile($pdfPath))->toBeTrue();

    @unlink($pdfPath);
});

it('asserts a pdf file through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_assert_pdf_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $validator = new class
    {
        public function assertPdf(string $pdfContent, string $label = 'content'): void
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($label)->toBe('source file');
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfValidator::class, $validator);

    Pdf::assertPdfFile($pdfPath, 'source file');

    @unlink($pdfPath);

    expect(true)->toBeTrue();
});

it('inspects in-memory pdf content through the builder', function () {
    $inspector = new class
    {
        public function inspect(string $pdfContent): array
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return [
                'valid' => true,
                'page_count' => 9,
            ];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfInspector::class, $inspector);

    expect(Pdf::inspectPdf('%PDF-fake'))->toBe([
        'valid' => true,
        'page_count' => 9,
    ]);
});

it('inspects a pdf file through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_inspect_pdf_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $inspector = new class
    {
        public function inspect(string $pdfContent): array
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return [
                'valid' => true,
                'page_count' => 11,
            ];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfInspector::class, $inspector);

    expect(Pdf::inspectPdfFile($pdfPath))->toBe([
        'valid' => true,
        'page_count' => 11,
    ]);

    @unlink($pdfPath);
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

it('plans chunk ranges for an existing pdf through the builder', function () {
    $chunker = new class
    {
        public function chunkRanges(string $pdfContent, int $pagesPerChunk): array
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($pagesPerChunk)->toBe(4);

            return ['1-4', '5-8', '9-9'];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfChunker::class, $chunker);

    expect(Pdf::chunkRanges('%PDF-fake', 4))->toBe(['1-4', '5-8', '9-9']);
});

it('builds a chunk plan for an existing pdf through the builder', function () {
    $chunker = new class
    {
        public function chunkPlan(string $pdfContent, int $pagesPerChunk): array
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($pagesPerChunk)->toBe(4);

            return [
                ['index' => 1, 'start' => 1, 'end' => 4, 'pages' => 4, 'range' => '1-4'],
                ['index' => 2, 'start' => 5, 'end' => 8, 'pages' => 4, 'range' => '5-8'],
            ];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfChunker::class, $chunker);

    expect(Pdf::chunkPlan('%PDF-fake', 4))->toBe([
        ['index' => 1, 'start' => 1, 'end' => 4, 'pages' => 4, 'range' => '1-4'],
        ['index' => 2, 'start' => 5, 'end' => 8, 'pages' => 4, 'range' => '5-8'],
    ]);
});

it('chunks an existing pdf file through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_chunk_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $chunker = new class
    {
        public function chunk(string $pdfContent, int $pagesPerChunk): array
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($pagesPerChunk)->toBe(2);

            return [
                new PdfResult(content: 'CHUNK_FILE_1', driver: 'fpdi-splitter', renderTimeMs: 0),
            ];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfChunker::class, $chunker);

    $results = Pdf::chunkFile($pdfPath, 2);

    expect($results)->toHaveCount(1)
        ->and($results[0]->content())->toBe('CHUNK_FILE_1');

    @unlink($pdfPath);
});

it('plans chunk ranges for an existing pdf file through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_chunk_ranges_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $chunker = new class
    {
        public function chunkRanges(string $pdfContent, int $pagesPerChunk): array
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($pagesPerChunk)->toBe(4);

            return ['1-4', '5-8'];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfChunker::class, $chunker);

    expect(Pdf::chunkRangesFile($pdfPath, 4))->toBe(['1-4', '5-8']);

    @unlink($pdfPath);
});

it('builds a chunk plan for an existing pdf file through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_chunk_plan_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $chunker = new class
    {
        public function chunkPlan(string $pdfContent, int $pagesPerChunk): array
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($pagesPerChunk)->toBe(4);

            return [
                ['index' => 1, 'start' => 1, 'end' => 4, 'pages' => 4, 'range' => '1-4'],
                ['index' => 2, 'start' => 5, 'end' => 6, 'pages' => 2, 'range' => '5-6'],
            ];
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfChunker::class, $chunker);

    expect(Pdf::chunkPlanFile($pdfPath, 4))->toBe([
        ['index' => 1, 'start' => 1, 'end' => 4, 'pages' => 4, 'range' => '1-4'],
        ['index' => 2, 'start' => 5, 'end' => 6, 'pages' => 2, 'range' => '5-6'],
    ]);

    @unlink($pdfPath);
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

it('embeds files into an existing pdf file through the builder', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'pdfstudio_embed_file_');
    file_put_contents($pdfPath, '%PDF-fake');

    $embedder = new class
    {
        public function embed(string $pdfContent, array $files): PdfResult
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($files)->toHaveCount(1);

            return new PdfResult(
                content: 'EMBEDDED_PDF_FILE',
                driver: 'gotenberg-embedder',
                renderTimeMs: 0,
            );
        }
    };

    $this->app->instance(\PdfStudio\Laravel\Manipulation\PdfEmbedder::class, $embedder);

    $result = Pdf::embedFilesIntoFile($pdfPath, [[
        'path' => '/tmp/report.csv',
        'name' => 'report.csv',
        'mime' => 'text/csv',
    ]]);

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toBe('EMBEDDED_PDF_FILE');

    @unlink($pdfPath);
});
