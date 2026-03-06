<?php

namespace PdfStudio\Laravel\Builder\Schema;

class TextBlock extends Block
{
    public function __construct(
        public string $content,
        public string $tag = 'p',
        public string $classes = '',
    ) {}

    public function type(): string
    {
        return 'text';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'text',
            'content' => $this->content,
            'tag' => $this->tag,
            'classes' => $this->classes,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromBlockArray(array $data): self
    {
        return new self(
            content: $data['content'],
            tag: $data['tag'] ?? 'p',
            classes: $data['classes'] ?? '',
        );
    }
}
