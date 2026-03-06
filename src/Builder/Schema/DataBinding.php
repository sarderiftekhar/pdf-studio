<?php

namespace PdfStudio\Laravel\Builder\Schema;

class DataBinding
{
    public function __construct(
        public string $variable,
        public string $path,
    ) {}

    /** @return array{variable: string, path: string} */
    public function toArray(): array
    {
        return [
            'variable' => $this->variable,
            'path' => $this->path,
        ];
    }

    /** @param array{variable: string, path: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            variable: $data['variable'],
            path: $data['path'],
        );
    }
}
