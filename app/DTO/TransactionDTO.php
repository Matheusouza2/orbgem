<?php

namespace App\DTO;

use App\Enums\FinancialInstrumentType;
use App\Enums\InstallmentPeriodicity;
use App\Enums\PaymentChannel;
use App\Enums\TransactionEffect;
use App\Enums\TransactionRecurrence;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;

final readonly class TransactionDTO
{
    public function __construct(
        public int $walletId, public ?int $accountId, public ?int $categoryId, public ?int $merchantId,
        public string $description, public TransactionType $type, public TransactionEffect $effect,
        public int $amount, public FinancialInstrumentType $financialInstrumentType,
        public string $transactionDate, public string $competenceDate, public ?string $dueDate, public TransactionRecurrence $recurrenceType,
        public ?int $installmentInitial, public ?int $installmentCount, public ?InstallmentPeriodicity $installmentPeriodicity, public bool $autoPostOnDueDate,
        public ?string $paidAt, public TransactionStatus $status, public ?PaymentChannel $paymentChannel,
        public ?string $notes, public bool $isThirdParty, public ?int $recurringTransactionId, public ?int $creditCardInvoiceId,
        public ?int $installmentId, public ?string $transferGroupId, public ?int $reversalOfTransactionId,
        public int $memberId, public ?int $financialCommitmentId = null, public ?int $importBatchId = null, public ?string $importRowHash = null,
    ) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes, int $memberId = 0): self
    {
        return new self(
            $attributes['wallet_id'], $attributes['account_id'] ?? null, $attributes['category_id'] ?? null, $attributes['merchant_id'] ?? null,
            $attributes['description'], TransactionType::from($attributes['type']), TransactionEffect::from($attributes['effect']),
            $attributes['amount'], FinancialInstrumentType::from($attributes['financial_instrument_type']),
            $attributes['transaction_date'], $attributes['competence_date'], $attributes['due_date'] ?? null,
            TransactionRecurrence::from($attributes['recurrence_type'] ?? TransactionRecurrence::NONE->value), $attributes['installment_initial'] ?? null,
            $attributes['installment_count'] ?? null, isset($attributes['installment_periodicity']) ? InstallmentPeriodicity::from($attributes['installment_periodicity']) : null,
            $attributes['auto_post_on_due_date'] ?? false,
            $attributes['paid_at'] ?? null, TransactionStatus::from($attributes['status']),
            isset($attributes['payment_channel']) ? PaymentChannel::from($attributes['payment_channel']) : null,
            $attributes['notes'] ?? null, (bool) ($attributes['is_third_party'] ?? false), $attributes['recurring_transaction_id'] ?? null,
            $attributes['credit_card_invoice_id'] ?? null, $attributes['installment_id'] ?? null,
            $attributes['transfer_group_id'] ?? null, $attributes['reversal_of_transaction_id'] ?? null, $memberId,
            $attributes['financial_commitment_id'] ?? null, $attributes['import_batch_id'] ?? null, $attributes['import_row_hash'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['wallet_id' => $this->walletId, 'account_id' => $this->accountId, 'category_id' => $this->categoryId, 'merchant_id' => $this->merchantId, 'description' => $this->description, 'type' => $this->type, 'effect' => $this->effect, 'amount' => $this->amount, 'financial_instrument_type' => $this->financialInstrumentType, 'transaction_date' => $this->transactionDate, 'competence_date' => $this->competenceDate, 'due_date' => $this->dueDate, 'recurrence_type' => $this->recurrenceType, 'installment_initial' => $this->installmentInitial, 'installment_count' => $this->installmentCount, 'installment_periodicity' => $this->installmentPeriodicity, 'auto_post_on_due_date' => $this->autoPostOnDueDate, 'paid_at' => $this->paidAt, 'status' => $this->status, 'payment_channel' => $this->paymentChannel, 'notes' => $this->notes, 'is_third_party' => $this->isThirdParty, 'recurring_transaction_id' => $this->recurringTransactionId, 'financial_commitment_id' => $this->financialCommitmentId, 'import_batch_id' => $this->importBatchId, 'import_row_hash' => $this->importRowHash, 'credit_card_invoice_id' => $this->creditCardInvoiceId, 'installment_id' => $this->installmentId, 'transfer_group_id' => $this->transferGroupId, 'reversal_of_transaction_id' => $this->reversalOfTransactionId, 'created_by_member_id' => $this->memberId, 'updated_by_member_id' => $this->memberId];
    }

    public function withMemberId(int $memberId): self
    {
        return new self($this->walletId, $this->accountId, $this->categoryId, $this->merchantId, $this->description, $this->type, $this->effect, $this->amount, $this->financialInstrumentType, $this->transactionDate, $this->competenceDate, $this->dueDate, $this->recurrenceType, $this->installmentInitial, $this->installmentCount, $this->installmentPeriodicity, $this->autoPostOnDueDate, $this->paidAt, $this->status, $this->paymentChannel, $this->notes, $this->isThirdParty, $this->recurringTransactionId, $this->creditCardInvoiceId, $this->installmentId, $this->transferGroupId, $this->reversalOfTransactionId, $memberId, $this->financialCommitmentId, $this->importBatchId, $this->importRowHash);
    }
}
