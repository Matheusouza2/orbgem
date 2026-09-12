<?php

namespace App\UseCases\OpenFinance;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\PluggyClient;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ConnectTokenUseCase
{
    public function __construct(private PluggyClient $client, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(User $user, int $walletId, ?string $itemId = null): string
    {
        $wallet = $this->wallets->find($walletId);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->client->createConnectToken((string) $user->id, route('api.open-finance.webhook'), $itemId);
    }
}
