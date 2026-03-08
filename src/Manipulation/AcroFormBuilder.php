<?php

namespace PdfStudio\Laravel\Manipulation;

use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\Contracts\AcroFormContract;
use PdfStudio\Laravel\Output\PdfResult;

class AcroFormBuilder
{
    /** @var array<string, string> */
    protected array $fieldValues = [];

    protected bool $flatten = false;

    public function __construct(
        protected Application $app,
        protected string $pdfPath,
    ) {}

    /**
     * @param  array<string, string>  $fieldValues
     */
    public function fill(array $fieldValues): static
    {
        $this->fieldValues = array_merge($this->fieldValues, $fieldValues);

        return $this;
    }

    public function flatten(bool $flatten = true): static
    {
        $this->flatten = $flatten;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function fields(): array
    {
        $filler = $this->app->make(AcroFormContract::class);

        return $filler->fields($this->pdfPath);
    }

    public function output(): PdfResult
    {
        $filler = $this->app->make(AcroFormContract::class);

        return $filler->fill($this->pdfPath, $this->fieldValues, $this->flatten);
    }
}
