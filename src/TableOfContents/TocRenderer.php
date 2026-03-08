<?php

namespace PdfStudio\Laravel\TableOfContents;

use PdfStudio\Laravel\DTOs\TocEntry;
use PdfStudio\Laravel\DTOs\TocOptions;

class TocRenderer
{
    /**
     * @param  array<int, TocEntry>  $entries
     */
    public function render(array $entries, TocOptions $options): string
    {
        // Use fallback (pure HTML) — works without Blade views being registered
        return $this->renderFallback($entries, $options);
    }

    /**
     * @param  array<int, TocEntry>  $entries
     */
    protected function renderFallback(array $entries, TocOptions $options): string
    {
        $html = '<div class="pdf-toc" style="page-break-after: always;">';
        $html .= '<h1 style="text-align: center; margin-bottom: 1.5em;">'.e($options->title).'</h1>';

        foreach ($entries as $entry) {
            $indent = ($entry->level - 1) * 20;
            $fontSize = $entry->level === 1 ? '14px' : '12px';
            $fontWeight = $entry->level === 1 ? 'bold' : 'normal';

            $html .= '<div class="toc-entry toc-level-'.$entry->level.'" style="display: flex; margin-left: '.$indent.'px; margin-bottom: 0.4em; font-size: '.$fontSize.'; font-weight: '.$fontWeight.';">';
            $html .= '<a href="#'.$entry->anchorId.'" style="text-decoration: none; color: #333; flex: 1;">'.e($entry->text).'</a>';
            $html .= '<span class="toc-dots" style="flex: 1; border-bottom: 1px dotted #ccc; margin: 0 4px; position: relative; top: -4px;"></span>';
            $html .= '<span class="toc-page" style="white-space: nowrap;">'.$entry->pageNumber.'</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
