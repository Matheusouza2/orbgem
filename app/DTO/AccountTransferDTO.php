<?php

namespace App\DTO;

use App\Enums\PaymentChannel;

final readonly class AccountTransferDTO
{
    public function __construct(
        public int $walletId,
        public int $fromAccountId,
        public int $toAccountId,
        public int $amount,
        public string $transactionDate,
        public string $competenceDate,
        public ?string $notes,
        public ?PaymentChannel $paymentChannel,
    ) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(
            $attributes['wallet_id'],
            $attributes['from_account_id'],
            $attributes['to_account_id'],
            $attributes['amount'],
            $attributes['transaction_date'],
            $attributes['competence_date'],
            $attributes['notes'] ?? null,
            isset($attributes['payment_channel']) ? PaymentChannel::from($attributes['payment_channel']) : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'from_account_id' => $this->fromAccountId,
            'to_account_id' => $this->toAccountId,
            'amount' => $this->amount,
            'transaction_date' => $this->transactionDate,
            'competence_date' => $this->competenceDate,
            'notes' => $this->notes,
            'payment_channel' => $this->paymentChannel,
        ];
    }
}
