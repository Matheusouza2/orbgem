<?php

namespace Tests\Unit;

use App\Infrastructure\Pluggy\PluggyTransactionMapper;
use PHPUnit\Framework\TestCase;

class PluggyTransactionMapperTest extends TestCase
{
    public function test_it_normalizes_a_debit_transaction_and_keeps_installment_metadata(): void
    {
        $transaction = (new PluggyTransactionMapper)->map([
            'id' => 'transaction-1',
            'description' => 'Compra supermercado',
            'amount' => 125.50,
            'type' => 'DEBIT',
            'date' => '2026-09-12',
            'status' => 'POSTED',
            'category' => 'Supermarket',
            'installmentNumber' => 2,
            'totalInstallments' => 6,
            'totalAmount' => 753.00,
        ], 10, 20, null);

        self::assertSame('transaction-1', $transaction->externalId);
        self::assertSame('Compra supermercado', $transaction->description);
        self::assertSame(12550, $transaction->amount);
        self::assertSame('EXPENSE', $transaction->type->value);
        self::assertSame(2, $transaction->creditCardMetadata['installment_number']);
        self::assertSame(6, $transaction->creditCardMetadata['total_installments']);
    }
}
