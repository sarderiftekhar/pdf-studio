<?php

namespace PdfStudio\Laravel\Commands;

use Illuminate\Console\Command;
use PdfStudio\Laravel\Cache\CssCache;

class CacheClearCommand extends Command
{
    protected $signature = 'pdf-studio:cache-clear';

    protected $description = 'Clear the PDF Studio compiled CSS cache';

    public function handle(CssCache $cache): int
    {
        $cache->flush();

        $this->info('PDF Studio CSS cache cleared successfully.');

        return self::SUCCESS;
    }
}
