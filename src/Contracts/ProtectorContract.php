<?php

namespace PdfStudio\Laravel\Contracts;

interface ProtectorContract
{
    /**
     * @param  array<string>  $permissions
     */
    public function protect(
        string $pdfContent,
        ?string $userPassword = null,
        ?string $ownerPassword = null,
        array $permissions = [],
    ): string;
}
