<?php

namespace PdfStudio\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PdfStudio\Laravel\Models\RenderJob;
use PdfStudio\Laravel\PdfBuilder;

class RenderPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $view,
        public array $data = [],
        public string $outputPath = '',
        public ?string $disk = null,
        public ?string $driver = null,
        public array $options = [],
        public ?string $jobId = null,
    ) {
        $this->tries = (int) config('pdf-studio.queue.retries', 3);
        $this->timeout = (int) config('pdf-studio.queue.timeout', 120);
        $this->onQueue(config('pdf-studio.queue.queue', 'default'));
        $this->onConnection(config('pdf-studio.queue.connection'));
    }

    public function handle(Application $app): void
    {
        $startTime = microtime(true);

        try {
            $builder = $app->make(PdfBuilder::class);
            $builder->view($this->view)->data($this->data);

            if ($this->driver !== null) {
                $builder->driver($this->driver);
            }

            if (isset($this->options['format'])) {
                $builder->format($this->options['format']);
            }

            if (isset($this->options['landscape'])) {
                $builder->landscape((bool) $this->options['landscape']);
            }

            if (isset($this->options['margins'])) {
                $margins = $this->options['margins'];
                $builder->margins(
                    $margins['top'] ?? null,
                    $margins['right'] ?? null,
                    $margins['bottom'] ?? null,
                    $margins['left'] ?? null,
                );
            }

            $result = $builder->render()->save($this->outputPath, $this->disk);

            if ($this->jobId !== null) {
                RenderJob::where('id', $this->jobId)->update([
                    'status' => 'completed',
                    'bytes' => $result->bytes,
                    'render_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'output_path' => $result->path,
                    'completed_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            if ($this->jobId !== null) {
                RenderJob::where('id', $this->jobId)->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            throw $e;
        }
    }
}
