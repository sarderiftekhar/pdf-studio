<?php

namespace PdfStudio\Laravel\Layout;

class PageNumberGenerator
{
    public function header(string $format = 'Page {page} of {total}', string $align = 'center'): string
    {
        return $this->buildHtml($format, $align);
    }

    public function footer(string $format = '{page}/{total}', string $align = 'right'): string
    {
        return $this->buildHtml($format, $align);
    }

    protected function buildHtml(string $format, string $align): string
    {
        $html = str_replace(
            ['{page}', '{total}'],
            ['<span class="pageNumber"></span>', '<span class="totalPages"></span>'],
            $format
        );

        return '<div style="font-size: 10px; width: 100%; text-align: ' . $align . ';">' . $html . '</div>';
    }
}
