<?php

namespace PdfStudio\Laravel\TableOfContents;

use PdfStudio\Laravel\DTOs\TocEntry;
use PdfStudio\Laravel\DTOs\TocOptions;

class TocExtractor
{
    /**
     * @return array<int, TocEntry>
     */
    public function extract(string $html, TocOptions $options): array
    {
        $entries = [];
        $index = 0;

        preg_match_all(
            '/<(h[1-6])([^>]*)>(.*?)<\/\1>/si',
            $html,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as $match) {
            $tag = $match[1];
            $attributes = $match[2];
            $text = strip_tags($match[3]);
            $level = (int) substr($tag, 1);

            if ($level > $options->depth) {
                continue;
            }

            if ($options->mode === 'explicit') {
                if (!preg_match('/\bdata-toc\b/', $attributes)) {
                    continue;
                }
            } else {
                if (preg_match('/data-toc\s*=\s*["\']false["\']/', $attributes)) {
                    continue;
                }
            }

            $anchorId = 'toc-'.$index;
            $entries[] = new TocEntry(
                level: $level,
                text: trim($text),
                pageNumber: 0,
                anchorId: $anchorId,
            );
            $index++;
        }

        return $entries;
    }

    public function injectAnchors(string $html, TocOptions $options): string
    {
        $index = 0;

        return preg_replace_callback(
            '/<(h[1-6])([^>]*)>(.*?)<\/\1>/si',
            function ($match) use ($options, &$index) {
                $tag = $match[1];
                $attributes = $match[2];
                $content = $match[3];
                $level = (int) substr($tag, 1);

                if ($level > $options->depth) {
                    return $match[0];
                }

                if ($options->mode === 'explicit' && !preg_match('/\bdata-toc\b/', $attributes)) {
                    return $match[0];
                }

                if ($options->mode !== 'explicit' && preg_match('/data-toc\s*=\s*["\']false["\']/', $attributes)) {
                    return $match[0];
                }

                $anchorId = 'toc-'.$index;
                $index++;

                return "<{$tag}{$attributes} id=\"{$anchorId}\">{$content}</{$tag}>";
            },
            $html,
        );
    }
}
