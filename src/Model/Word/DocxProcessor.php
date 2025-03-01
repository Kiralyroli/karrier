<?php

namespace App\Model\Word;

use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\TemplateProcessor;

class DocxProcessor
{
    /**
     * @param array $values
     * @param string $templatePath
     * @param string $outputPath
     * @return bool
     */
    public function generateDocx(array $values, string $templatePath, string $outputPath): bool
    {
        try {
            $templateProcessor = new TemplateProcessor($templatePath);
        } catch (CopyFileException | CreateTemporaryFileException $e) {
            return false;
        }

        foreach ($values as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        $templateProcessor->saveAs($outputPath);

        return true;
    }
}