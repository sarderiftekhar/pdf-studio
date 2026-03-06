<?php

namespace PdfStudio\Laravel\Commands;

use Illuminate\Console\Command;
use PdfStudio\Laravel\Templates\TemplateRegistry;

class TemplateListCommand extends Command
{
    protected $signature = 'pdf-studio:templates';

    protected $description = 'List all registered PDF templates';

    public function handle(TemplateRegistry $registry): int
    {
        $templates = $registry->all();

        if (empty($templates)) {
            $this->info('No templates registered.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($templates as $template) {
            $rows[] = [
                $template->name,
                $template->view,
                $template->description ?? '-',
            ];
        }

        $this->table(['Name', 'View', 'Description'], $rows);

        return self::SUCCESS;
    }
}
