<?php

namespace App\DTO;

final readonly class CreditCardPurchaseDTO
{
    public function __construct(public int $walletId, public int $creditCardId, public ?int $categoryId, public ?int $merchantId, public string $description, public string $purchaseDate, public int $totalAmount, public int $installmentCount, public int $memberId) {}

    public static function fromArray(array $a, int $memberId): self
    {
        return new self($a['wallet_id'], $a['credit_card_id'], $a['category_id'] ?? null, $a['merchant_id'] ?? null, $a['description'], $a['purchase_date'], $a['total_amount'], $a['installment_count'], $memberId);
    }

    public function toArray(): array
    {
        return ['wallet_id' => $this->walletId, 'credit_card_id' => $this->creditCardId, 'category_id' => $this->categoryId, 'merchant_id' => $this->merchantId, 'description' => $this->description, 'purchase_date' => $this->purchaseDate, 'total_amount' => $this->totalAmount, 'installment_count' => $this->installmentCount];
    }
}
