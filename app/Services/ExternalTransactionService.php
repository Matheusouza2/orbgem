<?php

namespace App\Services;

use App\DTO\CreditCardInvoiceListDTO;
use App\DTO\ImportTransactionDTO;
use App\DTO\TransactionDTO;
use App\Enums\TransactionRecurrence;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\ExternalAccount;
use App\Models\ExternalTransaction;
use App\Repositories\ExternalTransactionRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExternalTransactionService
{
    public function __construct(
        private ExternalTransactionRepositoryInterface $repository,
        private TransactionService $transactions,
        private WalletService $wallets,
        private CreditCardService $cards,
        private CreditCardInvoiceService $invoices,
    ) {}

    public function sync(ExternalAccount $externalAccount, ImportTransactionDTO $dto): ExternalTransaction
    {
        return DB::transaction(function () use ($externalAccount, $dto): ExternalTransaction {
            $existing = $this->repository->findBySourceAndExternalId('pluggy', $dto->externalId);
            $member = $this->wallets->ownerMember($dto->walletId);

            if ($member === null) {
                throw new \LogicException('A wallet must have an owner to import transactions.');
            }

            $invoice = $this->invoiceForCreditCard($externalAccount, $dto);

            if ($existing !== null && $existing->transaction_id === null) {
                return $this->repository->upsert('pluggy', $dto->externalId, [
                    'external_account_id' => $externalAccount->id,
                    'imported_at' => now(),
                    'raw_data' => $dto->rawData,
                ]);
            }

            $transactionData = [
                'wallet_id' => $dto->walletId,
                'account_id' => $dto->accountId,
                'category_id' => null,
                'description' => $dto->description,
                'type' => $dto->type->value,
                'effect' => $dto->effect->value,
                'amount' => $dto->amount,
                'financial_instrument_type' => $dto->financialInstrumentType->value,
                'transaction_date' => $dto->date,
                'competence_date' => $dto->date,
                'due_date' => $dto->date,
                'recurrence_type' => TransactionRecurrence::NONE->value,
                'status' => $dto->status->value,
                'notes' => $this->notes($dto),
            ];
            if ($invoice !== null) {
                $transactionData['credit_card_invoice_id'] = $invoice->id;
            }
            $transaction = $existing?->transaction;
            if ($transaction === null) {
                $transaction = $this->transactions->create(TransactionDTO::fromArray($transactionData, $member->id));
            } elseif ($invoice !== null && $transaction->credit_card_invoice_id === null) {
                $transaction->update(['credit_card_invoice_id' => $invoice->id]);
            }

            return $this->repository->upsert('pluggy', $dto->externalId, [
                'external_account_id' => $externalAccount->id,
                'transaction_id' => $transaction->id,
                'imported_at' => now(),
                'raw_data' => $dto->rawData,
            ]);
        });
    }

    private function notes(ImportTransactionDTO $dto): ?string
    {
        if ($dto->creditCardMetadata === []) {
            return null;
        }

        return 'Importado da Pluggy: '.json_encode($dto->creditCardMetadata, JSON_THROW_ON_ERROR);
    }

    private function invoiceForCreditCard(ExternalAccount $externalAccount, ImportTransactionDTO $dto): ?CreditCardInvoice
    {
        if ($externalAccount->accountable_type !== CreditCard::class || $externalAccount->accountable_id === null) {
            return null;
        }

        $card = $this->cards->find((int) $externalAccount->accountable_id);
        if ($card === null || $card->wallet_id !== $dto->walletId) {
            return null;
        }

        $reference = Carbon::parse($dto->date)->startOfMonth();
        if (Carbon::parse($dto->date)->day > $card->closing_day) {
            $reference->addMonth();
        }

        $invoice = $this->invoices->forWallet(new CreditCardInvoiceListDTO($dto->walletId, $card->id))
            ->firstWhere('reference_month', $reference->format('Y-m'));

        if ($invoice !== null) {
            return $invoice;
        }

        $closing = $reference->copy()->endOfMonth()->day(min($card->closing_day, $reference->copy()->endOfMonth()->day))->startOfDay();
        $due = $reference->copy()->endOfMonth()->day(min($card->due_day, $reference->copy()->endOfMonth()->day))->startOfDay();

        return $this->invoices->create([
            'wallet_id' => $dto->walletId,
            'credit_card_id' => $card->id,
            'reference_month' => $reference->format('Y-m'),
            'closing_date' => $closing,
            'due_date' => $due,
            'status' => 'OPEN',
        ]);
    }
}
