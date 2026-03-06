<?php

namespace PdfStudio\Laravel\Builder\Schema;

class ColumnsBlock extends Block
{
    /**
     * @param  array<int, array<int, Block>>  $columns
     */
    public function __construct(
        public array $columns,
        public string $gap = '4',
        public string $classes = '',
    ) {}

    public function type(): string
    {
        return 'columns';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'columns',
            'columns' => array_map(
                fn (array $col) => array_map(fn (Block $b) => $b->toArray(), $col),
                $this->columns,
            ),
            'gap' => $this->gap,
            'classes' => $this->classes,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromBlockArray(array $data): self
    {
        return new self(
            columns: array_map(
                fn (array $col) => array_map(fn (array $b) => Block::fromArray($b), $col),
                $data['columns'],
            ),
            gap: $data['gap'] ?? '4',
            classes: $data['classes'] ?? '',
        );
    }
}
