<?php

namespace App\Http\Controllers\Api;

use App\DTO\BudgetDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planning\CreateBudgetRequest;
use App\Http\Requests\Planning\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\UseCases\Planning\CreateBudgetUseCase;
use App\UseCases\Planning\DeleteBudgetUseCase;
use App\UseCases\Planning\ListBudgetsUseCase;
use App\UseCases\Planning\UpdateBudgetUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BudgetController extends Controller
{
    public function store(CreateBudgetRequest $request, CreateBudgetUseCase $useCase): BudgetResource
    {
        return new BudgetResource($useCase->execute(BudgetDTO::fromArray($request->validated()), $request->user()));
    }

    public function index(Request $request, ListBudgetsUseCase $useCase)
    {
        return BudgetResource::collection($useCase->execute($request->integer('wallet_id'), $request->input('month'), $request->user()));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget, UpdateBudgetUseCase $useCase): BudgetResource
    {
        return new BudgetResource($useCase->execute($budget, BudgetDTO::fromArray($request->validated()), $request->user()));
    }

    public function destroy(Request $request, Budget $budget, DeleteBudgetUseCase $useCase): Response
    {
        $useCase->execute($budget, $request->user());

        return response()->noContent();
    }
}
