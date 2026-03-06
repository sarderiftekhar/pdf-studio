<?php

namespace PdfStudio\Laravel\Listeners;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use PdfStudio\Laravel\Events\RenderCompleted;
use PdfStudio\Laravel\Events\RenderFailed;
use PdfStudio\Laravel\Events\RenderStarting;

class RenderLogger
{
    protected bool $enabled;

    protected ?string $channel;

    public function __construct(Application $app)
    {
        $this->enabled = (bool) $app['config']->get('pdf-studio.logging.enabled', false);
        $this->channel = $app['config']->get('pdf-studio.logging.channel');
    }

    public function handleStarting(RenderStarting $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->log('info', 'PDF render starting', [
            'driver' => $event->driver,
            'view' => $event->viewName,
        ]);
    }

    public function handleCompleted(RenderCompleted $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->log('info', 'PDF render completed', [
            'driver' => $event->driver,
            'render_time_ms' => round($event->renderTimeMs, 2),
            'bytes' => $event->bytes,
        ]);
    }

    public function handleFailed(RenderFailed $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->log('error', 'PDF render failed', [
            'driver' => $event->driver,
            'error' => $event->exception->getMessage(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $logger = $this->channel ? Log::channel($this->channel) : Log::getFacadeRoot();
        $logger->{$level}("[PdfStudio] {$message}", $context);
    }
}
