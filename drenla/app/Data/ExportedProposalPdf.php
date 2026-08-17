<?php

namespace App\Data;

class ExportedProposalPdf
{
    public function __construct(
        public readonly string $path,
        public readonly string $filename,
    ) {}
}
