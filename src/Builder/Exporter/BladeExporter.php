<?php

namespace PdfStudio\Laravel\Builder\Exporter;

use PdfStudio\Laravel\Builder\Schema\Block;
use PdfStudio\Laravel\Builder\Schema\ColumnsBlock;
use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\Schema\ImageBlock;
use PdfStudio\Laravel\Builder\Schema\SpacerBlock;
use PdfStudio\Laravel\Builder\Schema\TableBlock;
use PdfStudio\Laravel\Builder\Schema\TextBlock;

class BladeExporter
{
    public function export(DocumentSchema $schema): string
    {
        $tokens = $schema->styleTokens;
        $bodyStyle = sprintf(
            'font-family: %s; font-size: %s; line-height: %s; color: %s;',
            $tokens->fontFamily,
            $tokens->fontSize,
            $tokens->lineHeight,
            $tokens->primaryColor,
        );

        $blocksHtml = '';
        foreach ($schema->blocks as $block) {
            $blocksHtml .= $this->exportBlock($block);
        }

        return <<<BLADE
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"></head>
        <body style="{$bodyStyle}">
        {$blocksHtml}
        </body>
        </html>
        BLADE;
    }

    protected function exportBlock(Block $block): string
    {
        return match (true) {
            $block instanceof TextBlock => $this->exportText($block),
            $block instanceof ImageBlock => $this->exportImage($block),
            $block instanceof TableBlock => $this->exportTable($block),
            $block instanceof ColumnsBlock => $this->exportColumns($block),
            $block instanceof SpacerBlock => $this->exportSpacer($block),
            default => '',
        };
    }

    protected function exportText(TextBlock $block): string
    {
        $tag = $block->tag;
        $classes = $block->classes !== '' ? " class=\"{$block->classes}\"" : '';

        return "<{$tag}{$classes}>{$block->content}</{$tag}>\n";
    }

    protected function exportImage(ImageBlock $block): string
    {
        $classes = $block->classes !== '' ? " class=\"{$block->classes}\"" : '';

        return "<img src=\"{$block->src}\" alt=\"{$block->alt}\"{$classes} />\n";
    }

    protected function exportTable(TableBlock $block): string
    {
        $classes = $block->classes !== '' ? " class=\"{$block->classes}\"" : '';
        $blade = "<table{$classes}>\n<thead>\n<tr>\n";

        foreach ($block->headers as $header) {
            $blade .= "<th>{$header}</th>\n";
        }

        $blade .= "</tr>\n</thead>\n<tbody>\n";
        $blade .= "@foreach(\${$block->rowBinding->variable} as \$item)\n<tr>\n";

        foreach ($block->cellBindings as $binding) {
            $blade .= "<td>{{ \${$binding->variable}->{$binding->path} }}</td>\n";
        }

        $blade .= "</tr>\n@endforeach\n</tbody>\n</table>\n";

        return $blade;
    }

    protected function exportColumns(ColumnsBlock $block): string
    {
        $colCount = count($block->columns);
        $classes = "grid grid-cols-{$colCount} gap-{$block->gap}";
        if ($block->classes !== '') {
            $classes .= " {$block->classes}";
        }

        $blade = "<div class=\"{$classes}\">\n";

        foreach ($block->columns as $column) {
            $blade .= "<div>\n";
            foreach ($column as $innerBlock) {
                $blade .= $this->exportBlock($innerBlock);
            }
            $blade .= "</div>\n";
        }

        $blade .= "</div>\n";

        return $blade;
    }

    protected function exportSpacer(SpacerBlock $block): string
    {
        return "<div style=\"height: {$block->height};\"></div>\n";
    }
}
