<?php

namespace App\Services\BankReconciliation;

use App\DTO\BankStatementData;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class OfxStatementParser
{
    public function parse(string $content, string $month): BankStatementData
    {
        if (! str_contains(strtoupper($content), '<OFX')) {
            throw ValidationException::withMessages(['file' => 'O arquivo não contém um extrato OFX válido.']);
        }

        $accountNumber = $this->tag($content, 'ACCTID');
        $start = $this->date($this->tag($content, 'DTSTART'));
        $end = $this->date($this->tag($content, 'DTEND'));
        $rows = [];
        preg_match_all('/<STMTTRN>(.*?)(?:<\/STMTTRN>|(?=<STMTTRN>|<\/BANKTRANLIST>))/si', $content, $matches);

        foreach ($matches[1] ?? [] as $transaction) {
            $date = $this->date($this->tag($transaction, 'DTPOSTED'));
            $amount = $this->money($this->tag($transaction, 'TRNAMT'));
            $description = trim(implode(' ', array_filter([$this->tag($transaction, 'NAME'), $this->tag($transaction, 'MEMO')])));

            if ($date === null || $amount === null || $description === '') {
                continue;
            }

            $rows[] = [
                'external_id' => $this->tag($transaction, 'FITID'),
                'date' => $date,
                'description' => $description,
                'signed_amount' => $amount,
                'balance_after' => null,
            ];
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Nenhum lançamento reconhecido no arquivo OFX. Confira se o arquivo contém transações.']);
        }

        $ledgerBalance = $this->betweenTags($content, 'LEDGERBAL');

        return new BankStatementData(
            $accountNumber,
            $start,
            $end,
            null,
            $this->money($this->tag($ledgerBalance, 'BALAMT')),
            null,
            null,
            $rows,
        );
    }

    private function tag(string $content, string $name): ?string
    {
        return $this->betweenTags($content, $name);
    }

    private function betweenTags(string $content, string $name): ?string
    {
        if (preg_match('/<'.preg_quote($name, '/').'>\s*([^<\r\n]*)/i', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    private function date(?string $value): ?string
    {
        if ($value === null || preg_match('/^(\d{4})(\d{2})(\d{2})/', $value, $matches) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $matches[1].'-'.$matches[2].'-'.$matches[3])->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function money(?string $value): ?int
    {
        if ($value === null || preg_match('/^-?\d+(?:\.\d+)?$/', trim($value)) !== 1) {
            return null;
        }

        return (int) round((float) $value * 100);
    }
}
