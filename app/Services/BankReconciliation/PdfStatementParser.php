<?php

namespace App\Services\BankReconciliation;

use App\DTO\BankStatementData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class PdfStatementParser
{
    public function parse(string $path, string $month): BankStatementData
    {
        if (file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
            throw ValidationException::withMessages(['file' => 'O arquivo selecionado não é um PDF válido.']);
        }

        $process = new Process(['pdftotext', '-enc', 'UTF-8', '-layout', '-nopgbrk', $path, '-']);
        $process->setTimeout(30);
        $process->run();

        $text = $process->getOutput();
        if (! $process->isSuccessful() && trim($text) === '') {
            $diagnostic = trim(str_replace($path, '[uploaded-pdf]', $process->getErrorOutput()));
            Log::warning('Bank reconciliation PDF extraction failed.', [
                'exit_code' => $process->getExitCode(),
                'diagnostic' => mb_substr($diagnostic, 0, 500),
            ]);

            throw ValidationException::withMessages(['file' => 'O leitor de PDF não conseguiu extrair o conteúdo. Tente exportar o extrato novamente ou envie o arquivo OFX.']);
        }

        return $this->parseText($text);
    }

    private function parseText(string $text): BankStatementData
    {
        if (trim($text) === '') {
            throw ValidationException::withMessages(['file' => 'Este PDF não possui texto selecionável. Envie o arquivo OFX ou um PDF com texto.']);
        }

        $accountNumber = null;
        $periodStart = null;
        $periodEnd = null;
        $openingBalance = $this->labeledMoney($text, 'Saldo inicial');
        $closingBalance = $this->labeledMoney($text, 'Saldo final');
        $totalCredits = $this->labeledMoney($text, 'Entradas');
        $totalDebits = $this->labeledMoney($text, 'Sa[ií]das');

        if (preg_match('/Conta:\s*([\d.\/-]+)/iu', $text, $matches) === 1) {
            $accountNumber = preg_replace('/\D/', '', $matches[1]);
        }
        if (preg_match('/Per[ií]odo:\s*De\s+(\d{2}-\d{2}-\d{4})\s+(?:a|al|até)\s+(\d{2}-\d{2}-\d{4})/iu', $text, $matches) === 1) {
            $periodStart = $this->brDate($matches[1]);
            $periodEnd = $this->brDate($matches[2]);
        }

        $rows = [];
        $pending = null;
        $orphanDescription = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if (preg_match('/^(?:EXTRATO DE CONTA|CPF\/CNPJ:|Per[ií]odo:|DETALHE DOS MOVIMENTOS|Data\s+Descri|ID da opera|Saldo inicial|Saldo final|Entradas:|Sa[ií]das:|Data de gera[cç][aã]o:|\d+\/\d+|Mercado Pago|Voc[eê] tem)/iu', $line)) {
                $orphanDescription = [];
                continue;
            }
            if (preg_match('/^(\d{2}-\d{2}-\d{4})\s*(.*)$/u', $line, $matches) === 1) {
                if ($pending !== null) {
                    $pending = null;
                }
                $date = $this->brDate($matches[1]);
                $parsed = $this->parseMovementColumns($matches[2]);
                if ($parsed === null) {
                    $pending = ['date' => $date, 'description' => trim(implode(' ', [...$orphanDescription, trim($matches[2])]))];
                } else {
                    $description = trim(implode(' ', [...$orphanDescription, $parsed['description']]));
                    $rows[] = ['external_id' => $parsed['external_id'], 'date' => $date, 'description' => $description !== '' ? $description : 'Movimento bancário', 'signed_amount' => $parsed['signed_amount'], 'balance_after' => $parsed['balance_after']];
                }
                $orphanDescription = [];
                continue;
            }

            if ($pending !== null && $line !== '') {
                $parsed = $this->parseMovementColumns($line);
                if ($parsed !== null) {
                    $rows[] = ['external_id' => $parsed['external_id'], 'date' => $pending['date'], 'description' => trim($pending['description'].' '.$parsed['description']), 'signed_amount' => $parsed['signed_amount'], 'balance_after' => $parsed['balance_after']];
                    $pending = null;
                } elseif (! preg_match('/^(?:Data\s+Descri|ID da opera|Saldo|Entradas|Sa[ií]das|DETALHE DOS|\d+\/\d+)\b/iu', $line)) {
                    $pending['description'] = trim($pending['description'].' '.$line);
                }
            } elseif ($pending === null && $line !== '' && ! str_contains($line, 'R$')
                && ! preg_match('/^(?:Data\s+Descri|ID da opera|Saldo|Entradas|Sa[ií]das|DETALHE DOS|EXTRATO DE CONTA|Per[ií]odo:|CPF\/CNPJ:|Data de gera[cç][aã]o:|\d+\/\d+|Mercado Pago|Voc[eê] tem)/iu', $line)) {
                $orphanDescription[] = $line;
            }
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Nenhum lançamento reconhecido no PDF. Confira se o extrato usa linhas com data, descrição, valor e saldo.']);
        }

        return new BankStatementData($accountNumber, $periodStart, $periodEnd, $openingBalance, $closingBalance, $totalCredits, $totalDebits, $rows);
    }

    /** @return array{external_id: ?string, description: string, signed_amount: int, balance_after: ?int}|null */
    private function parseMovementColumns(string $line): ?array
    {
        preg_match_all('/R\$\s*(-?\s*[\d.]+,\d{2})/u', $line, $matches, PREG_OFFSET_CAPTURE);
        if (count($matches[0] ?? []) < 2) {
            return null;
        }

        $amountIndex = count($matches[0]) - 2;
        $balanceIndex = count($matches[0]) - 1;
        $prefix = trim(substr($line, 0, $matches[0][$amountIndex][1]));
        $amount = $this->brMoney($matches[1][$amountIndex][0]);
        $balance = $this->brMoney($matches[1][$balanceIndex][0]);
        $externalId = null;
        if (preg_match('/(?:^|\s)(\d{9,15})\s*$/u', $prefix, $idMatches, PREG_OFFSET_CAPTURE) === 1) {
            $externalId = $idMatches[1][0];
            $prefix = trim(substr($prefix, 0, $idMatches[0][1]));
        }

        $description = trim(preg_replace('/\s{2,}/u', ' ', $prefix) ?? $prefix);
        if ($amount === null || $balance === null) {
            return null;
        }

        return ['external_id' => $externalId, 'description' => $description, 'signed_amount' => $amount, 'balance_after' => $balance];
    }

    private function labeledMoney(string $text, string $label): ?int
    {
        if (preg_match('/'. $label .':?\s*R\$\s*(-?\s*[\d.]+,\d{2})/iu', $text, $matches) !== 1) {
            return null;
        }

        return $this->brMoney($matches[1]);
    }

    private function brMoney(string $value): ?int
    {
        $normalized = str_replace([' ', '.'], '', trim($value));
        $normalized = str_replace(',', '.', $normalized);
        if (preg_match('/^-?\d+\.\d{2}$/', $normalized) !== 1) {
            return null;
        }

        return (int) round((float) $normalized * 100);
    }

    private function brDate(string $value): string
    {
        try {
            return Carbon::createFromFormat('!d-m-Y', $value)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'O extrato contém uma data inválida.']);
        }
    }
}
