<?php

namespace App\Data;

class ExportedFinanceDocumentPdf
{
    public function __construct(
        public readonly string $path,
        public readonly string $filename,
    ) {}
}
