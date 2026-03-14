<?php

namespace PdfStudio\Laravel\Manipulation;

class PdfMetadataReader
{
    /**
     * @return array<string, string>
     */
    public function read(string $content): array
    {
        $infoReference = $this->extractInfoReference($content);

        if ($infoReference === null) {
            return [];
        }

        $objectBody = $this->extractObjectBody($content, $infoReference);

        if ($objectBody === null) {
            return [];
        }

        return $this->parseInfoDictionary($objectBody);
    }

    protected function extractInfoReference(string $content): ?string
    {
        if (preg_match('/trailer\s*<<.*?\/Info\s+(\d+\s+\d+)\s+R.*?>>/si', $content, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    protected function extractObjectBody(string $content, string $reference): ?string
    {
        $pattern = sprintf('/%s\s+obj(.*?)endobj/si', preg_quote($reference, '/'));

        if (preg_match($pattern, $content, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function parseInfoDictionary(string $body): array
    {
        $metadata = [];

        if (preg_match_all('/\/([A-Za-z]+)\s+(\((?:\\\\.|[^\\\\)])*\)|<[^>]+>)/s', $body, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $this->decodePdfString($match[2]);

                if ($value !== '') {
                    $metadata[$key] = $value;
                }
            }
        }

        return $metadata;
    }

    protected function decodePdfString(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '(') && str_ends_with($value, ')')) {
            $inner = substr($value, 1, -1);
            $inner = preg_replace('/\\\\([()\\\\])/', '$1', $inner) ?? $inner;
            $inner = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $inner);

            return trim($inner);
        }

        if (str_starts_with($value, '<') && str_ends_with($value, '>')) {
            $hex = preg_replace('/\s+/', '', substr($value, 1, -1)) ?? '';

            if ($hex === '' || strlen($hex) % 2 !== 0 || !ctype_xdigit($hex)) {
                return '';
            }

            $decoded = hex2bin($hex);

            return $decoded === false ? '' : trim($decoded);
        }

        return '';
    }
}
