<?php

namespace App\UseCases\CreditCard;

use App\DTO\PayCreditCardInvoiceDTO;
use App\DTO\TransactionDTO;
use App\Enums\CreditCardInvoiceStatus;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\CreditCardInvoice;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CreditCardInvoiceService;
use App\Services\InvoicePaymentService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayCreditCardInvoiceUseCase
{
    public function __construct(private CreditCardInvoiceService $invoices, private InvoicePaymentService $payments, private TransactionService $transactions, private AccountService $accounts, private WalletMembershipAuthorization $auth) {}

    public function execute(PayCreditCardInvoiceDTO $dto, User $user): CreditCardInvoice
    {
        return DB::transaction(function () use ($dto, $user) {
            $invoice = $this->invoices->lock($dto->invoiceId);
            if (! $invoice) {
                throw new ModelNotFoundException;
            }$member = $this->auth->authorize($user, $invoice->wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);
            $account = $this->accounts->lockForTransfer([$dto->accountId])->get($dto->accountId);
            if (! $account || $account->wallet_id !== $invoice->wallet_id) {
                throw ValidationException::withMessages(['account_id' => 'The account must belong to the invoice wallet.']);
            }$total = $invoice->totalAmount();
            $paid = $this->payments->totalForInvoice($invoice->id);
            if ($dto->amount <= 0 || $paid + $dto->amount > $total) {
                throw ValidationException::withMessages(['amount' => 'The payment exceeds the invoice total.']);
            }$now = Carbon::parse($dto->paymentDate)->startOfDay();
            $transaction = $this->transactions->create(TransactionDTO::fromArray(['wallet_id' => $invoice->wallet_id, 'account_id' => $account->id, 'description' => 'Pagamento de fatura '.$invoice->reference_month, 'type' => TransactionType::TRANSFER->value, 'effect' => TransactionEffect::DEBIT->value, 'amount' => $dto->amount, 'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value, 'transaction_date' => $now->toDateString(), 'competence_date' => $now->toDateString(), 'due_date' => null, 'paid_at' => $now->toDateTimeString(), 'status' => TransactionStatus::POSTED->value, 'credit_card_invoice_id' => $invoice->id], $member->id));
            $this->payments->create(['credit_card_invoice_id' => $invoice->id, 'transaction_id' => $transaction->id, 'amount' => $dto->amount, 'paid_at' => $now]);
            if ($paid + $dto->amount === $total) {
                $this->transactions->effectivateForInvoice($invoice->id, $now);
                $invoice->status = CreditCardInvoiceStatus::PAID;
                $invoice->paid_at = $now;
                $this->invoices->save($invoice);
            }

            return $this->invoices->find($invoice->id);
        });
    }
}
