<?php

namespace Tests\Unit;

use App\Enums\TransactionEffect;
use App\Services\AccountBalanceCalculator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AccountBalanceCalculatorTest extends TestCase
{
    public function test_balance_uses_effect_and_ignores_non_posted_input_by_contract(): void
    {
        $credit = (object) ['effect' => TransactionEffect::CREDIT, 'amount' => 2500];
        $debit = (object) ['effect' => TransactionEffect::DEBIT, 'amount' => 700];
        $none = (object) ['effect' => TransactionEffect::NONE, 'amount' => 9999];

        $balance = app(AccountBalanceCalculator::class)->calculate(1000, new Collection([$credit, $debit, $none]));

        $this->assertSame(2800, $balance);
    }
}
