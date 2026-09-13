<?php

namespace App\UseCases\CreditCard;

use App\DTO\CreditCardInvoiceListDTO;
use App\DTO\CreditCardPurchaseDTO;
use App\DTO\TransactionDTO;
use App\Enums\CreditCardInvoiceStatus;
use App\Enums\FinancialInstrumentType;
use App\Enums\InstallmentStatus;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\CreditCardPurchase;
use App\Models\User;
use App\Services\CreditCardInvoiceService;
use App\Services\CreditCardPurchaseService;
use App\Services\CreditCardService;
use App\Services\InstallmentService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCreditCardPurchaseUseCase
{
    public function __construct(private CreditCardPurchaseService $purchases, private CreditCardService $cards, private CreditCardInvoiceService $invoices, private InstallmentService $installments, private TransactionService $transactions, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(CreditCardPurchaseDTO $dto, User $user): CreditCardPurchase
    {
        return DB::transaction(function () use ($dto, $user): CreditCardPurchase {
            $wallet = $this->wallets->find($dto->walletId);
            if ($wallet === null) {
                throw new ModelNotFoundException;
            }
            $member = $this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);
            $card = $this->cards->lock($dto->creditCardId);
            if ($card === null || $card->wallet_id !== $dto->walletId || ! $card->active) {
                throw ValidationException::withMessages(['credit_card_id' => 'The credit card is invalid for this wallet.']);
            }
            $purchase = $this->purchases->create($dto);
            $baseDate = Carbon::parse($dto->dueDate ?? $dto->purchaseDate);
            $base = $baseDate->copy()->startOfMonth();
            if ($baseDate->day >= $card->closing_day) {
                $base->addMonth();
            }
            $baseAmount = intdiv($dto->totalAmount, $dto->installmentCount);
            $remainder = $dto->totalAmount - ($baseAmount * $dto->installmentCount);
            for ($i = 0; $i < $dto->installmentCount; $i++) {
                $reference = $base->copy()->addMonths($i);
                $closing = $reference->copy()->endOfMonth()->day(min($card->closing_day, $reference->copy()->endOfMonth()->day))->startOfDay();
                $due = $reference->copy()->endOfMonth()->day(min($card->due_day, $reference->copy()->endOfMonth()->day))->startOfDay();
                $invoice = $this->invoices->forWallet(new CreditCardInvoiceListDTO($dto->walletId, $card->id, null))->firstWhere('reference_month', $reference->format('Y-m'));
                if ($invoice === null) {
                    $invoice = $this->invoices->create(['wallet_id' => $dto->walletId, 'credit_card_id' => $card->id, 'reference_month' => $reference->format('Y-m'), 'closing_date' => $closing, 'due_date' => $due, 'status' => CreditCardInvoiceStatus::OPEN->value]);
                }
                $amount = $baseAmount + ($i === $dto->installmentCount - 1 ? $remainder : 0);
                $installment = $this->installments->create(['credit_card_purchase_id' => $purchase->id, 'credit_card_invoice_id' => $invoice->id, 'number' => $i + 1, 'amount' => $amount, 'competence_date' => $reference->toDateString(), 'due_date' => $due->toDateString(), 'status' => InstallmentStatus::PENDING->value]);
                $this->transactions->create(TransactionDTO::fromArray(['wallet_id' => $dto->walletId, 'account_id' => null, 'category_id' => $dto->categoryId, 'merchant_id' => $dto->merchantId, 'description' => $dto->description.' ('.($i + 1).'/'.$dto->installmentCount.')', 'type' => TransactionType::EXPENSE->value, 'effect' => TransactionEffect::NONE->value, 'amount' => $amount, 'financial_instrument_type' => FinancialInstrumentType::CREDIT_CARD->value, 'transaction_date' => $dto->purchaseDate, 'competence_date' => $reference->toDateString(), 'due_date' => $due->toDateString(), 'status' => TransactionStatus::PROJECTED->value, 'is_third_party' => $dto->isThirdParty, 'credit_card_invoice_id' => $invoice->id, 'installment_id' => $installment->id], $member->id));
            }

            return $purchase->load('installments');
        });
    }
}
