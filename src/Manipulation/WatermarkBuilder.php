<?php

namespace PdfStudio\Laravel\Manipulation;

use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\Contracts\WatermarkerContract;
use PdfStudio\Laravel\DTOs\WatermarkOptions;
use PdfStudio\Laravel\Output\PdfResult;

class WatermarkBuilder
{
    protected WatermarkOptions $options;

    public function __construct(
        protected Application $app,
        protected string $pdfContent,
    ) {
        $this->options = new WatermarkOptions;
    }

    public function text(string $text): static
    {
        $this->options->text = $text;

        return $this;
    }

    public function image(string $path): static
    {
        $this->options->imagePath = $path;

        return $this;
    }

    public function opacity(float $opacity): static
    {
        $this->options->opacity = $opacity;

        return $this;
    }

    public function rotation(int $degrees): static
    {
        $this->options->rotation = $degrees;

        return $this;
    }

    public function position(string $position): static
    {
        $this->options->position = $position;

        return $this;
    }

    public function apply(): PdfResult
    {
        $watermarker = $this->app->make(WatermarkerContract::class);
        $content = $watermarker->apply($this->pdfContent, $this->options);

        return new PdfResult(
            content: $content,
            driver: 'watermarker',
            renderTimeMs: 0,
        );
    }
}
