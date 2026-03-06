<?php

namespace PdfStudio\Laravel\Builder\Examples;

use PdfStudio\Laravel\Builder\Schema\ColumnsBlock;
use PdfStudio\Laravel\Builder\Schema\DataBinding;
use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\Schema\SpacerBlock;
use PdfStudio\Laravel\Builder\Schema\StyleTokens;
use PdfStudio\Laravel\Builder\Schema\TableBlock;
use PdfStudio\Laravel\Builder\Schema\TextBlock;

class InvoiceSchema
{
    public static function create(): DocumentSchema
    {
        return new DocumentSchema(
            version: '1.0',
            blocks: [
                new ColumnsBlock(
                    columns: [
                        [
                            new TextBlock(content: '{{ $company_name }}', tag: 'h1', classes: 'text-2xl font-bold'),
                            new TextBlock(content: '{{ $company_address }}', tag: 'p', classes: 'text-sm text-gray-600'),
                        ],
                        [
                            new TextBlock(content: 'INVOICE', tag: 'h2', classes: 'text-xl text-right'),
                            new TextBlock(content: '#{{ $invoice_number }}', tag: 'p', classes: 'text-right text-gray-600'),
                        ],
                    ],
                    gap: '8',
                ),
                new SpacerBlock(height: '2rem'),
                new ColumnsBlock(
                    columns: [
                        [
                            new TextBlock(content: 'Bill To:', tag: 'p', classes: 'font-semibold'),
                            new TextBlock(content: '{{ $customer_name }}', tag: 'p'),
                        ],
                        [
                            new TextBlock(content: 'Date: {{ $date }}', tag: 'p', classes: 'text-right'),
                            new TextBlock(content: 'Due: {{ $due_date }}', tag: 'p', classes: 'text-right'),
                        ],
                    ],
                    gap: '8',
                ),
                new SpacerBlock(height: '1rem'),
                new TableBlock(
                    headers: ['Description', 'Qty', 'Price', 'Total'],
                    rowBinding: new DataBinding(variable: 'items', path: 'line_items'),
                    cellBindings: [
                        new DataBinding(variable: 'item', path: 'description'),
                        new DataBinding(variable: 'item', path: 'quantity'),
                        new DataBinding(variable: 'item', path: 'price'),
                        new DataBinding(variable: 'item', path: 'total'),
                    ],
                    classes: 'w-full border-collapse',
                ),
                new SpacerBlock(height: '1rem'),
                new TextBlock(content: 'Total: {{ $total }}', tag: 'p', classes: 'text-xl font-bold text-right'),
            ],
            styleTokens: new StyleTokens(
                primaryColor: '#1a1a1a',
                fontFamily: 'Inter, sans-serif',
            ),
        );
    }
}
