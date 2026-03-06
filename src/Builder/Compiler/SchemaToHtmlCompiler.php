<?php

namespace PdfStudio\Laravel\Builder\Compiler;

use PdfStudio\Laravel\Builder\Schema\Block;
use PdfStudio\Laravel\Builder\Schema\ColumnsBlock;
use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\Schema\ImageBlock;
use PdfStudio\Laravel\Builder\Schema\SpacerBlock;
use PdfStudio\Laravel\Builder\Schema\TableBlock;
use PdfStudio\Laravel\Builder\Schema\TextBlock;

class SchemaToHtmlCompiler
{
    public function compile(DocumentSchema $schema): string
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
            $blocksHtml .= $this->compileBlock($block);
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"></head>
        <body style="{$bodyStyle}">
        {$blocksHtml}
        </body>
        </html>
        HTML;
    }

    protected function compileBlock(Block $block): string
    {
        return match (true) {
            $block instanceof TextBlock => $this->compileText($block),
            $block instanceof ImageBlock => $this->compileImage($block),
            $block instanceof TableBlock => $this->compileTable($block),
            $block instanceof ColumnsBlock => $this->compileColumns($block),
            $block instanceof SpacerBlock => $this->compileSpacer($block),
            default => '',
        };
    }

    protected function compileText(TextBlock $block): string
    {
        $tag = $block->tag;
        $classes = $block->classes !== '' ? " class=\"{$block->classes}\"" : '';

        return "<{$tag}{$classes}>{$block->content}</{$tag}>\n";
    }

    protected function compileImage(ImageBlock $block): string
    {
        $classes = $block->classes !== '' ? " class=\"{$block->classes}\"" : '';

        return "<img src=\"{$block->src}\" alt=\"{$block->alt}\"{$classes} />\n";
    }

    protected function compileTable(TableBlock $block): string
    {
        $classes = $block->classes !== '' ? " class=\"{$block->classes}\"" : '';
        $html = "<table{$classes}>\n<thead>\n<tr>\n";

        foreach ($block->headers as $header) {
            $html .= "<th>{$header}</th>\n";
        }

        $html .= "</tr>\n</thead>\n<tbody>\n";
        $html .= "@foreach(\${$block->rowBinding->variable} as \$item)\n<tr>\n";

        foreach ($block->cellBindings as $binding) {
            $html .= "<td>{{ \${$binding->variable}->{$binding->path} }}</td>\n";
        }

        $html .= "</tr>\n@endforeach\n</tbody>\n</table>\n";

        return $html;
    }

    protected function compileColumns(ColumnsBlock $block): string
    {
        $colCount = count($block->columns);
        $classes = "grid grid-cols-{$colCount} gap-{$block->gap}";
        if ($block->classes !== '') {
            $classes .= " {$block->classes}";
        }

        $html = "<div class=\"{$classes}\">\n";

        foreach ($block->columns as $column) {
            $html .= "<div>\n";
            foreach ($column as $innerBlock) {
                $html .= $this->compileBlock($innerBlock);
            }
            $html .= "</div>\n";
        }

        $html .= "</div>\n";

        return $html;
    }

    protected function compileSpacer(SpacerBlock $block): string
    {
        return "<div style=\"height: {$block->height};\"></div>\n";
    }
}
