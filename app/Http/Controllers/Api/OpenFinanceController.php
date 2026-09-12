<?php

namespace App\Http\Controllers\Api;

use App\DTO\SyncFinancialConnectionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\OpenFinance\ConnectTokenRequest;
use App\Http\Requests\OpenFinance\ListItemsRequest;
use App\Http\Requests\OpenFinance\PluggyWebhookRequest;
use App\Http\Requests\OpenFinance\StoreItemRequest;
use App\Http\Requests\OpenFinance\SyncConnectionRequest;
use App\Http\Resources\FinancialConnectionResource;
use App\Http\Resources\PluggyItemResource;
use App\Jobs\SyncPluggyConnectionJob;
use App\Jobs\SyncPluggyItem;
use App\Jobs\SyncPluggyTransactionsJob;
use App\Repositories\ExternalAccountRepositoryInterface;
use App\Services\FinancialConnectionService;
use App\Services\PluggyItemService;
use App\UseCases\OpenFinance\ConnectTokenUseCase;
use App\UseCases\OpenFinance\DeleteItemUseCase;
use App\UseCases\OpenFinance\ListItemsUseCase;
use App\UseCases\OpenFinance\StoreItemUseCase;
use App\UseCases\OpenFinance\SyncFinancialConnectionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OpenFinanceController extends Controller
{
    public function connectToken(ConnectTokenRequest $request, ConnectTokenUseCase $useCase): JsonResponse
    {
        return response()->json(['accessToken' => $useCase->execute($request->user(), $request->integer('wallet_id'), $request->validated('item_id'))]);
    }

    public function sync(SyncConnectionRequest $request, int $connection, SyncFinancialConnectionUseCase $useCase): JsonResponse
    {
        $useCase->execute(SyncFinancialConnectionDTO::fromArray(['connection_id' => $connection, ...$request->validated()]), $request->user());

        return response()->json(['status' => 'queued']);
    }

    public function index(ListItemsRequest $request, ListItemsUseCase $useCase): mixed
    {
        return FinancialConnectionResource::collection($useCase->execute($request->user()));
    }

    public function store(StoreItemRequest $request, StoreItemUseCase $useCase): PluggyItemResource
    {
        return new PluggyItemResource($useCase->execute($request->user(), $request->integer('wallet_id'), $request->validated('item_id')));
    }

    public function destroy(int $item, Request $request, DeleteItemUseCase $useCase): Response
    {
        $useCase->execute($request->user(), $item);

        return response()->noContent();
    }

    public function webhook(PluggyWebhookRequest $request, PluggyItemService $items, FinancialConnectionService $connections, ExternalAccountRepositoryInterface $externalAccounts): JsonResponse
    {
        $itemId = $request->validated('itemId');
        $connection = $connections->findByProviderExternalId('pluggy', $itemId);
        $event = $request->validated('event');
        if ($connection !== null && in_array($event, ['item/created', 'item/updated'], true)) {
            SyncPluggyConnectionJob::dispatch($connection->id);
        }
        if ($connection !== null && in_array($event, ['transactions/created', 'transactions/updated'], true) && $request->validated('accountId')) {
            $externalAccount = $externalAccounts->findByConnectionAndExternalId($connection->id, $request->validated('accountId'));
            if ($externalAccount !== null) {
                SyncPluggyTransactionsJob::dispatch($externalAccount->id);
            }
        }
        $item = $items->findByPluggyId($itemId);
        if ($item === null) {
            return response()->json(['received' => true]);
        }
        if (in_array($event, ['item/created', 'item/updated'], true)) {
            SyncPluggyItem::dispatch($item->id);
        }
        if ($event === 'item/error') {
            $items->upsert($item->user_id, $item->wallet_id, $item->pluggy_item_id, ['status' => 'LOGIN_ERROR', 'error_code' => $request->input('error.code'), 'error_message' => $request->input('error.message')]);
        }

        return response()->json(['received' => true]);
    }
}
