<?php

namespace App\Infrastructure\Pluggy;

use App\DTO\ImportTransactionDTO;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;

class PluggyTransactionMapper
{
    /** @param array<string, mixed> $transaction */
    public function map(array $transaction, int $walletId, int $accountableId, ?string $accountableType): ImportTransactionDTO
    {
        $isCreditCard = $accountableType === 'credit_card';
        $isIncome = strtoupper((string) ($transaction['type'] ?? 'DEBIT')) === 'CREDIT';
        $metadata = is_array($transaction['creditCardMetadata'] ?? null) ? $transaction['creditCardMetadata'] : [];

        return new ImportTransactionDTO(
            externalId: (string) ($transaction['id'] ?? $transaction['transactionId'] ?? ''),
            walletId: $walletId,
            accountId: $isCreditCard ? null : $accountableId,
            description: (string) ($transaction['description'] ?? $transaction['descriptionRaw'] ?? 'Transação Pluggy'),
            amount: (int) round(abs((float) ($transaction['amount'] ?? 0)) * 100),
            date: (string) ($transaction['date'] ?? $transaction['date'] ?? now()->toDateString()),
            type: $isIncome ? TransactionType::INCOME : TransactionType::EXPENSE,
            effect: $isIncome ? TransactionEffect::CREDIT : ($isCreditCard ? TransactionEffect::NONE : TransactionEffect::DEBIT),
            status: strtoupper((string) ($transaction['status'] ?? 'POSTED')) === 'PENDING' ? TransactionStatus::PROJECTED : TransactionStatus::POSTED,
            financialInstrumentType: $isCreditCard ? FinancialInstrumentType::CREDIT_CARD : FinancialInstrumentType::ACCOUNT,
            category: is_string($transaction['category'] ?? null) ? $transaction['category'] : null,
            creditCardMetadata: array_filter([
                'installment_number' => $metadata['installmentNumber'] ?? $transaction['installmentNumber'] ?? null,
                'total_installments' => $metadata['totalInstallments'] ?? $transaction['totalInstallments'] ?? null,
                'total_amount' => isset($metadata['totalAmount'])
                    ? (int) round((float) $metadata['totalAmount'] * 100)
                    : (isset($transaction['totalAmount']) ? (int) round((float) $transaction['totalAmount'] * 100) : null),
                'bill_forecast_date' => $metadata['billForecastDate'] ?? $transaction['billForecastDate'] ?? null,
            ], static fn (mixed $value): bool => $value !== null),
            rawData: $transaction,
        );
    }
}
