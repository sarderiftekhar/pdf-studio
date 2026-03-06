<?php

namespace PdfStudio\Laravel\Builder\Preview;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PdfStudio\Laravel\Builder\Compiler\SchemaToHtmlCompiler;
use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\SchemaValidator;
use PdfStudio\Laravel\Exceptions\SchemaValidationException;
use PdfStudio\Laravel\PdfBuilder;

class BuilderPreviewController
{
    public function preview(Request $request, SchemaToHtmlCompiler $compiler, PdfBuilder $pdfBuilder): Response
    {
        $schemaData = $request->input('schema');
        $format = $request->input('format', 'html');

        if (! is_array($schemaData)) {
            abort(422, 'Schema is required and must be an object.');
        }

        try {
            $schema = DocumentSchema::fromArray($schemaData);
            (new SchemaValidator)->validate($schema);
        } catch (SchemaValidationException $e) {
            abort(422, $e->getMessage());
        }

        $html = $compiler->compile($schema);

        if ($format === 'pdf') {
            return $pdfBuilder->html($html)->render()->download('preview.pdf');
        }

        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
