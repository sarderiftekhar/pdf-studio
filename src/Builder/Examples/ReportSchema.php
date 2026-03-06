<?php

namespace PdfStudio\Laravel\Builder\Examples;

use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\Schema\SpacerBlock;
use PdfStudio\Laravel\Builder\Schema\StyleTokens;
use PdfStudio\Laravel\Builder\Schema\TextBlock;

class ReportSchema
{
    public static function create(): DocumentSchema
    {
        return new DocumentSchema(
            version: '1.0',
            blocks: [
                new TextBlock(content: '{{ $title }}', tag: 'h1', classes: 'text-3xl font-bold'),
                new TextBlock(content: 'Prepared on {{ $date }}', tag: 'p', classes: 'text-sm text-gray-500'),
                new SpacerBlock(height: '2rem'),
                new TextBlock(content: 'Executive Summary', tag: 'h2', classes: 'text-xl font-semibold'),
                new TextBlock(content: '{{ $summary }}', tag: 'p', classes: 'leading-relaxed'),
                new SpacerBlock(height: '1.5rem'),
                new TextBlock(content: 'Key Findings', tag: 'h2', classes: 'text-xl font-semibold'),
                new TextBlock(content: '{{ $findings }}', tag: 'p', classes: 'leading-relaxed'),
                new SpacerBlock(height: '1.5rem'),
                new TextBlock(content: 'Recommendations', tag: 'h2', classes: 'text-xl font-semibold'),
                new TextBlock(content: '{{ $recommendations }}', tag: 'p', classes: 'leading-relaxed'),
            ],
            styleTokens: new StyleTokens(
                primaryColor: '#2d3748',
                fontFamily: 'Georgia, serif',
                lineHeight: '1.6',
            ),
        );
    }
}
