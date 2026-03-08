<?php

namespace PdfStudio\Laravel\Manipulation;

use mikehaertl\pdftk\Pdf;
use PdfStudio\Laravel\Contracts\ProtectorContract;
use PdfStudio\Laravel\Exceptions\ManipulationException;

class PdfProtector implements ProtectorContract
{
    /**
     * @param  array<string>  $permissions
     */
    public function protect(
        string $pdfContent,
        ?string $userPassword = null,
        ?string $ownerPassword = null,
        array $permissions = [],
    ): string {
        if (!class_exists(Pdf::class)) {
            throw new ManipulationException(
                'Password protection requires mikehaertl/php-pdftk. Install it with: composer require mikehaertl/php-pdftk'
            );
        }

        $inputFile = tempnam(sys_get_temp_dir(), 'pdfstudio_protect_in_');
        file_put_contents($inputFile, $pdfContent);

        try {
            $pdf = new Pdf($inputFile);

            $encryptionArgs = [];

            if ($ownerPassword !== null) {
                $encryptionArgs['owner_pw'] = $ownerPassword;
            }

            if ($userPassword !== null) {
                $encryptionArgs['user_pw'] = $userPassword;
            }

            if (!empty($permissions)) {
                $encryptionArgs['allow'] = $permissions;
            }

            $pdf->allow($permissions)
                ->setPassword($ownerPassword)
                ->setUserPassword($userPassword);

            $outputFile = tempnam(sys_get_temp_dir(), 'pdfstudio_protect_out_');

            if (!$pdf->saveAs($outputFile)) {
                throw new ManipulationException('pdftk password protection failed: '.($pdf->getError() ?? 'Unknown error'));
            }

            $content = file_get_contents($outputFile);
            @unlink($outputFile);

            if ($content === false) {
                throw new ManipulationException('Failed to read pdftk protected output file.');
            }

            return $content;
        } finally {
            @unlink($inputFile);
        }
    }

    public function output(): string
    {
        throw new ManipulationException('Use protect() to produce output.');
    }
}
