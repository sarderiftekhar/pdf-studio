<?php

namespace PdfStudio\Laravel\Builder\Schema;

class ImageBlock extends Block
{
    public function __construct(
        public string $src,
        public string $alt = '',
        public string $classes = '',
    ) {}

    public function type(): string
    {
        return 'image';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'image',
            'src' => $this->src,
            'alt' => $this->alt,
            'classes' => $this->classes,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromBlockArray(array $data): self
    {
        return new self(
            src: $data['src'],
            alt: $data['alt'] ?? '',
            classes: $data['classes'] ?? '',
        );
    }
}
