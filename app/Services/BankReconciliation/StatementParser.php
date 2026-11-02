<?php

namespace App\Services\BankReconciliation;

use App\DTO\BankStatementData;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class StatementParser
{
    public function __construct(
        private OfxStatementParser $ofx,
        private PdfStatementParser $pdf,
    ) {}

    public function parse(UploadedFile $file, string $month): BankStatementData
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'ofx') {
            return $this->ofx->parse((string) file_get_contents($file->getRealPath()), $month);
        }

        if ($extension === 'pdf') {
            return $this->pdf->parse($file->getRealPath(), $month);
        }

        throw ValidationException::withMessages(['file' => 'Envie um arquivo PDF ou OFX.']);
    }
}
