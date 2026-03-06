<?php

namespace PdfStudio\Laravel\Builder\Schema;

class DocumentSchema
{
    /**
     * @param  array<int, Block>  $blocks
     */
    public function __construct(
        public string $version = '1.0',
        public array $blocks = [],
        public ?StyleTokens $styleTokens = null,
    ) {
        $this->styleTokens ??= new StyleTokens;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'blocks' => array_map(fn (Block $b) => $b->toArray(), $this->blocks),
            'style_tokens' => $this->styleTokens->toArray(),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            version: $data['version'] ?? '1.0',
            blocks: array_map(fn (array $b) => Block::fromArray($b), $data['blocks'] ?? []),
            styleTokens: isset($data['style_tokens'])
                ? StyleTokens::fromArray($data['style_tokens'])
                : null,
        );
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public static function fromJson(string $json): self
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return self::fromArray($data);
    }
}
