<?php

namespace PdfStudio\Laravel\Builder\Schema;

abstract class Block
{
    abstract public function type(): string;

    /** @return array<string, mixed> */
    abstract public function toArray(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return match ($data['type']) {
            'text' => TextBlock::fromBlockArray($data),
            'image' => ImageBlock::fromBlockArray($data),
            'table' => TableBlock::fromBlockArray($data),
            'columns' => ColumnsBlock::fromBlockArray($data),
            'spacer' => SpacerBlock::fromBlockArray($data),
            default => throw new \InvalidArgumentException("Unknown block type: {$data['type']}"),
        };
    }
}
