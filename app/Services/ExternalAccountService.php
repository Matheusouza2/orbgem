<?php

namespace App\Services;

use App\DTO\AccountDTO;
use App\DTO\CreditCardDTO;
use App\Enums\AccountType;
use App\Models\ExternalAccount;
use App\Models\FinancialConnection;
use App\Repositories\ExternalAccountRepositoryInterface;

class ExternalAccountService
{
    public function __construct(
        private ExternalAccountRepositoryInterface $repository,
        private AccountService $accounts,
        private CreditCardService $cards,
        private WalletService $wallets,
    ) {}

    /** @param array<string, mixed> $remote */
    public function sync(FinancialConnection $connection, array $remote): ExternalAccount
    {
        $type = strtoupper((string) ($remote['type'] ?? 'BANK'));
        $subtype = strtoupper((string) ($remote['subtype'] ?? 'CHECKING_ACCOUNT'));
        $isCard = $type === 'CREDIT' || $subtype === 'CREDIT_CARD';
        $accountable = $this->repository->upsert($connection->id, (string) ($remote['id'] ?? ''), [
            'type' => $type,
            'subtype' => $subtype,
            'last_synced_at' => now(),
            'metadata' => $remote,
        ]);

        if ($accountable->accountable_id === null) {
            $owner = $this->wallets->ownerMember($connection->wallet_id);
            if ($isCard) {
                $card = $this->cards->create(CreditCardDTO::fromArray([
                    'wallet_id' => $connection->wallet_id,
                    'owner_wallet_member_id' => $owner?->id,
                    'name' => $remote['name'] ?? 'Cartão Open Finance',
                    'institution' => $connection->institution_name,
                    'limit' => (int) round((float) ($remote['creditLimit'] ?? 0) * 100),
                    'closing_day' => 1,
                    'due_day' => 10,
                    'active' => true,
                ]));
                $accountable->update(['accountable_type' => $card->getMorphClass(), 'accountable_id' => $card->id]);
            } else {
                $account = $this->accounts->create(AccountDTO::fromArray([
                    'wallet_id' => $connection->wallet_id,
                    'owner_wallet_member_id' => $owner?->id,
                    'name' => $remote['name'] ?? 'Conta Open Finance',
                    'institution' => $connection->institution_name,
                    'account_number' => $remote['number'] ?? null,
                    'type' => $subtype === 'SAVINGS_ACCOUNT' ? AccountType::SAVINGS->value : AccountType::CHECKING->value,
                    'initial_balance' => (int) round((float) ($remote['balance'] ?? 0) * 100),
                    'is_default' => false,
                    'show_in_dashboard' => true,
                    'ignore_in_totals' => false,
                    'active' => true,
                ]));
                $accountable->update(['accountable_type' => $account->getMorphClass(), 'accountable_id' => $account->id]);
            }
        }

        return $accountable->refresh();
    }
}
