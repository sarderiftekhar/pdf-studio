<?php

namespace PdfStudio\Laravel\Builder\Schema;

class TableBlock extends Block
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, DataBinding>  $cellBindings
     */
    public function __construct(
        public array $headers,
        public DataBinding $rowBinding,
        public array $cellBindings = [],
        public string $classes = '',
    ) {}

    public function type(): string
    {
        return 'table';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'table',
            'headers' => $this->headers,
            'row_binding' => $this->rowBinding->toArray(),
            'cell_bindings' => array_map(fn (DataBinding $b) => $b->toArray(), $this->cellBindings),
            'classes' => $this->classes,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromBlockArray(array $data): self
    {
        return new self(
            headers: $data['headers'],
            rowBinding: DataBinding::fromArray($data['row_binding']),
            cellBindings: array_map(
                fn (array $b) => DataBinding::fromArray($b),
                $data['cell_bindings'] ?? [],
            ),
            classes: $data['classes'] ?? '',
        );
    }
}
