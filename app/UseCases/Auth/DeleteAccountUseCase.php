<?php

namespace App\UseCases\Auth;

use App\Enums\WalletMemberRole;
use App\Infrastructure\Pluggy\PluggyClient;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeleteAccountUseCase
{
    public function __construct(private PluggyClient $pluggy) {}

    public function execute(User $user): void
    {
        $ownedWallets = $this->ownedWallets($user);
        $this->assertNoSharedOwnedWallet($user, $ownedWallets);
        $this->disconnectProviders($ownedWallets);

        DB::transaction(function () use ($user, $ownedWallets): void {
            $ownedWallets->each(fn (Wallet $wallet) => $this->purgeWallet($wallet));

            $user->tokens()->delete();
            $user->pushTokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            if ($user->walletMemberships()->exists()) {
                $this->anonymize($user);

                return;
            }

            $user->delete();
        });
    }

    /** @return Collection<int, Wallet> */
    private function ownedWallets(User $user): Collection
    {
        return Wallet::query()
            ->whereHas('members', fn ($query) => $query->where('user_id', $user->id)->where('role', WalletMemberRole::OWNER->value))
            ->get();
    }

    /** @param Collection<int, Wallet> $ownedWallets */
    private function assertNoSharedOwnedWallet(User $user, Collection $ownedWallets): void
    {
        $shared = $ownedWallets->filter(fn (Wallet $wallet) => $wallet->members()->where('user_id', '!=', $user->id)->exists());

        if ($shared->isNotEmpty()) {
            throw ValidationException::withMessages([
                'account' => [__('Antes de excluir a conta, transfira a propriedade ou remova os membros das carteiras compartilhadas: :wallets.', ['wallets' => $shared->pluck('name')->implode(', ')])],
            ]);
        }
    }

    /** @param Collection<int, Wallet> $ownedWallets */
    private function disconnectProviders(Collection $ownedWallets): void
    {
        $ownedWallets->each(function (Wallet $wallet): void {
            $wallet->financialConnections()->get()->each(function ($connection): void {
                try {
                    $this->pluggy->deleteItem($connection->external_id);
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
        });
    }

    /**
     * Several ledger tables reference each other with restrictOnDelete, and MariaDB
     * does not defer those checks while the wallet cascade runs, so dependents go first.
     */
    private function purgeWallet(Wallet $wallet): void
    {
        $id = $wallet->id;
        $invoices = DB::table('credit_card_invoices')->where('wallet_id', $id)->select('id');
        $purchases = DB::table('credit_card_purchases')->where('wallet_id', $id)->select('id');

        DB::table('invoice_payments')->whereIn('credit_card_invoice_id', $invoices)->delete();
        DB::table('installments')->whereIn('credit_card_purchase_id', $purchases)->delete();
        DB::table('transactions')->where('wallet_id', $id)->update(['reversal_of_transaction_id' => null]);

        foreach (['transactions', 'credit_card_purchases', 'credit_card_invoices', 'recurring_transactions', 'attachments', 'credit_cards', 'merchants', 'accounts'] as $table) {
            DB::table($table)->where('wallet_id', $id)->delete();
        }

        $wallet->delete();
    }

    /**
     * Memberships in other people's wallets are referenced by ledger rows
     * (restrictOnDelete), so personal data is erased while the row remains.
     */
    private function anonymize(User $user): void
    {
        $user->forceFill([
            'name' => 'Usuário removido',
            'email' => 'deleted-'.$user->id.'-'.Str::lower(Str::random(8)).'@deleted.invalid',
            'password' => Str::random(64),
            'remember_token' => null,
            'email_verified_at' => null,
        ])->save();
    }
}
