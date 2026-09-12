<?php

namespace App\Infrastructure\Pluggy;

use App\DTO\PluggyInvestmentTransactionDTO;

class PluggyInvestmentTransactionMapper
{
    /** @param array<string, mixed> $remote */
    public function map(array $remote): ?PluggyInvestmentTransactionDTO
    {
        $description = (string) ($remote['description'] ?? $remote['type'] ?? '');
        $type = (string) ($remote['type'] ?? $remote['category'] ?? '');
        $searchable = strtolower($description.' '.$type);

        if (! str_contains($searchable, 'dividend')
            && ! str_contains($searchable, 'interest')
            && ! str_contains($searchable, 'proceeds')
            && ! str_contains($searchable, 'rendimento')
            && ! str_contains($searchable, 'dividendo')) {
            return null;
        }

        $externalId = (string) ($remote['id'] ?? $remote['transactionId'] ?? '');
        $date = (string) ($remote['date'] ?? $remote['transactionDate'] ?? $remote['settledAt'] ?? '');
        if ($externalId === '' || $date === '') {
            return null;
        }

        return new PluggyInvestmentTransactionDTO(
            externalId: $externalId,
            eventType: $type !== '' ? $type : 'INCOME',
            description: $description !== '' ? $description : 'Rendimento de investimento',
            amount: (int) round(abs((float) ($remote['amount'] ?? $remote['value'] ?? 0)) * 100),
            date: substr($date, 0, 10),
            rawData: $remote,
        );
    }
}
