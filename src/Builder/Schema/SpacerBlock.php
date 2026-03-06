<?php

namespace PdfStudio\Laravel\Builder\Schema;

class SpacerBlock extends Block
{
    public function __construct(
        public string $height = '1rem',
    ) {}

    public function type(): string
    {
        return 'spacer';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'spacer',
            'height' => $this->height,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromBlockArray(array $data): self
    {
        return new self(
            height: $data['height'] ?? '1rem',
        );
    }
}
