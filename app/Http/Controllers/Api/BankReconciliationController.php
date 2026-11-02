<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankReconciliation\ImportBankReconciliationRequest;
use App\Http\Requests\BankReconciliation\PreviewBankReconciliationRequest;
use App\Http\Resources\BankReconciliationResource;
use App\Models\User;
use App\UseCases\BankReconciliation\ImportBankReconciliationUseCase;
use App\UseCases\BankReconciliation\PreviewBankReconciliationUseCase;
use App\UseCases\BankReconciliation\ShowBankReconciliationUseCase;
use Illuminate\Http\JsonResponse;

class BankReconciliationController extends Controller
{
    public function store(PreviewBankReconciliationRequest $request, PreviewBankReconciliationUseCase $useCase): BankReconciliationResource
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        return new BankReconciliationResource($useCase->execute($data['account_id'], $data['month'], $data['file'], $user));
    }

    public function show(int $reconciliation, ShowBankReconciliationUseCase $useCase): BankReconciliationResource
    {
        /** @var User $user */
        $user = request()->user();

        return new BankReconciliationResource($useCase->execute($reconciliation, $user));
    }

    public function import(ImportBankReconciliationRequest $request, int $reconciliation, ImportBankReconciliationUseCase $useCase): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $result = $useCase->execute($reconciliation, $data['entry_ids'], (bool) ($data['acknowledge_warnings'] ?? false), $user);

        return response()->json([
            'data' => new BankReconciliationResource($result['reconciliation']),
            'imported' => $result['imported'],
            'already_imported' => $result['already_imported'],
            'skipped' => $result['skipped'],
        ]);
    }
}
