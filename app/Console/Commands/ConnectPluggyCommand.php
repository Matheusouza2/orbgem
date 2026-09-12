<?php

namespace App\Console\Commands;

use App\DTO\RegisterPluggyConnectionDTO;
use App\Exceptions\PluggyException;
use App\Services\WalletService;
use App\UseCases\OpenFinance\RegisterPluggyConnectionUseCase;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ConnectPluggyCommand extends Command
{
    protected $signature = 'pluggy:connect {--item=} {--wallet=}';

    protected $description = 'Registra um Item Pluggy existente e agenda sua sincronização';

    public function handle(RegisterPluggyConnectionUseCase $useCase, WalletService $wallets): int
    {
        $itemId = (string) $this->option('item');
        $walletId = (int) $this->option('wallet');
        if ($itemId === '' || $walletId < 1) {
            $this->error('Informe --item e --wallet.');

            return self::FAILURE;
        }

        $wallet = $wallets->find($walletId);
        if ($wallet === null) {
            $this->error('A carteira informada não existe.');

            return self::FAILURE;
        }

        try {
            $connection = $useCase->execute(RegisterPluggyConnectionDTO::fromArray(['item_id' => $itemId, 'wallet_id' => $walletId]));
        } catch (ModelNotFoundException|PluggyException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line('Connection ID: '.$connection->id);
        $this->line('Pluggy Item ID: '.$connection->external_id);
        $this->line('Institution: '.($connection->institution_name ?? 'Não informada'));
        $this->line('Wallet: '.$wallet->name.' (#'.$wallet->id.')');
        $this->line('Synchronization status: queued');

        return self::SUCCESS;
    }
}
