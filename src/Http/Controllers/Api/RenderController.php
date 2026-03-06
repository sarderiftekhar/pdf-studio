<?php

namespace PdfStudio\Laravel\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PdfStudio\Laravel\Models\RenderJob;
use PdfStudio\Laravel\PdfBuilder;

class RenderController
{
    public function sync(Request $request, PdfBuilder $pdfBuilder): Response
    {
        $workspace = $request->attributes->get('workspace');

        $builder = $this->configurePdfBuilder($pdfBuilder, $request);
        $result = $builder->render();

        RenderJob::create([
            'workspace_id' => $workspace->id,
            'status' => 'completed',
            'view' => $request->input('view'),
            'html' => $request->input('html'),
            'data' => $request->input('data', []),
            'options' => $request->input('options', []),
            'driver' => $request->input('driver'),
            'bytes' => $result->bytes,
            'render_time_ms' => $result->renderTimeMs,
            'completed_at' => now(),
        ]);

        $filename = $request->input('filename', 'document.pdf');

        return $result->download($filename);
    }

    public function async(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');

        $job = RenderJob::create([
            'workspace_id' => $workspace->id,
            'status' => 'pending',
            'view' => $request->input('view'),
            'html' => $request->input('html'),
            'data' => $request->input('data', []),
            'options' => $request->input('options', []),
            'driver' => $request->input('driver'),
            'output_path' => $request->input('output_path'),
            'output_disk' => $request->input('output_disk'),
        ]);

        \PdfStudio\Laravel\Jobs\RenderPdfJob::dispatch(
            view: $job->view ?? '',
            data: $job->data ?? [],
            outputPath: $job->output_path ?? '',
            disk: $job->output_disk,
            driver: $job->driver,
            options: $job->options ?? [],
        );

        return new JsonResponse([
            'id' => $job->id,
            'status' => 'pending',
        ], 202);
    }

    public function status(Request $request, string $jobId): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');

        $job = RenderJob::where('id', $jobId)
            ->where('workspace_id', $workspace->id)
            ->first();

        if ($job === null) {
            abort(404, 'Render job not found.');
        }

        return new JsonResponse([
            'id' => $job->id,
            'status' => $job->status,
            'bytes' => $job->bytes,
            'render_time_ms' => $job->render_time_ms,
            'error' => $job->error,
            'completed_at' => $job->completed_at?->toIso8601String(),
        ]);
    }

    protected function configurePdfBuilder(PdfBuilder $pdfBuilder, Request $request): PdfBuilder
    {
        if ($request->has('view')) {
            $pdfBuilder->view($request->input('view'));
        } elseif ($request->has('html')) {
            $pdfBuilder->html($request->input('html'));
        }

        if ($request->has('data')) {
            $pdfBuilder->data($request->input('data'));
        }

        if ($request->has('driver')) {
            $pdfBuilder->driver($request->input('driver'));
        }

        $options = $request->input('options', []);
        if (isset($options['format'])) {
            $pdfBuilder->format($options['format']);
        }
        if (isset($options['landscape'])) {
            $pdfBuilder->landscape((bool) $options['landscape']);
        }

        return $pdfBuilder;
    }
}
