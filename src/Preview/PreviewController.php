<?php

namespace PdfStudio\Laravel\Preview;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use PdfStudio\Laravel\Contracts\PreviewDataProviderContract;
use PdfStudio\Laravel\Facades\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PreviewController extends Controller
{
    public function __construct(
        protected Application $app,
    ) {}

    public function show(Request $request, string $template): Response|StreamedResponse
    {
        if (! View::exists($template)) {
            abort(404, "Template [{$template}] not found.");
        }

        $data = $this->resolveData($template);
        $format = $request->query('format', 'html');

        if ($format === 'pdf') {
            return Pdf::view($template)
                ->data($data)
                ->stream("{$template}.pdf");
        }

        // HTML preview — render the view directly
        $html = view($template, $data)->render();

        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveData(string $template): array
    {
        /** @var array<string, string> $providers */
        $providers = $this->app['config']->get('pdf-studio.preview.data_providers', []);

        if (! isset($providers[$template])) {
            return [];
        }

        $providerClass = $providers[$template];

        /** @var PreviewDataProviderContract $provider */
        $provider = $this->app->make($providerClass);

        return $provider->data();
    }
}
