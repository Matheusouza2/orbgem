<?php

namespace App\UseCases\CreditCard;

use App\DTO\CloseCreditCardInvoiceDTO;
use App\Enums\CreditCardInvoiceStatus;
use App\Enums\InstallmentStatus;
use App\Enums\WalletMemberRole;
use App\Models\CreditCardInvoice;
use App\Models\User;
use App\Services\CreditCardInvoiceService;
use App\Services\InstallmentService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseCreditCardInvoiceUseCase
{
    public function __construct(private CreditCardInvoiceService $invoices, private InstallmentService $installments, private WalletMembershipAuthorization $auth) {}

    public function execute(CloseCreditCardInvoiceDTO $dto, User $user): CreditCardInvoice
    {
        return DB::transaction(function () use ($dto, $user) {
            $invoice = $this->invoices->lock($dto->invoiceId);
            if (! $invoice) {
                throw new ModelNotFoundException;
            }$this->auth->authorize($user, $invoice->wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);
            if ($invoice->status !== CreditCardInvoiceStatus::OPEN) {
                throw ValidationException::withMessages(['invoice' => 'Only open invoices can be closed.']);
            }foreach ($this->installments->pendingForInvoice($invoice->id) as $installment) {
                $installment->status = InstallmentStatus::INVOICED;
                $installment->save();
            }$invoice->status = CreditCardInvoiceStatus::CLOSED;

            return $this->invoices->save($invoice->load('installments'));
        });
    }
}
