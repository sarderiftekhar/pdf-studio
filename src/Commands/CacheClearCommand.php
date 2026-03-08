<?php

namespace PdfStudio\Laravel\Commands;

use Illuminate\Console\Command;
use PdfStudio\Laravel\Cache\CssCache;
use PdfStudio\Laravel\Cache\RenderCache;

class CacheClearCommand extends Command
{
    protected $signature = 'pdf-studio:cache-clear {--render : Clear the render result cache instead of CSS cache}';

    protected $description = 'Clear the PDF Studio compiled CSS cache';

    public function handle(CssCache $cssCache, RenderCache $renderCache): int
    {
        if ($this->option('render')) {
            $renderCache->flush();
            $this->info('PDF Studio render cache cleared successfully.');
        } else {
            $cssCache->flush();
            $this->info('PDF Studio CSS cache cleared successfully.');
        }

        return self::SUCCESS;
    }
}
