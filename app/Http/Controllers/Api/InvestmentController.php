<?php

namespace App\Http\Controllers\Api;

use App\DTO\InvestmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\CreateInvestmentIncomeRequest;
use App\Http\Requests\Investment\CreateInvestmentPositionRequest;
use App\Http\Requests\Investment\CreateInvestmentRequest;
use App\Http\Requests\Investment\ListInvestmentIncomeRequest;
use App\Http\Requests\Investment\ListInvestmentRequest;
use App\Http\Requests\Investment\ListInvestmentYieldsRequest;
use App\Http\Requests\Investment\UpdateInvestmentIncomeRequest;
use App\Http\Resources\InvestmentIncomeResource;
use App\Http\Resources\InvestmentPositionHistoryResource;
use App\Http\Resources\InvestmentPositionResource;
use App\Http\Resources\InvestmentResource;
use App\Http\Resources\InvestmentYieldResource;
use App\Models\Investment;
use App\Models\InvestmentPosition;
use App\Models\InvestmentIncome;
use App\UseCases\Investment\CreateInvestmentIncomeUseCase;
use App\UseCases\Investment\CreateInvestmentUseCase;
use App\UseCases\Investment\DeleteInvestmentPositionUseCase;
use App\UseCases\Investment\DeleteInvestmentUseCase;
use App\UseCases\Investment\DeleteInvestmentIncomeUseCase;
use App\UseCases\Investment\ListInvestmentIncomeUseCase;
use App\UseCases\Investment\ListInvestmentPositionHistoryUseCase;
use App\UseCases\Investment\ListInvestmentPositionsUseCase;
use App\UseCases\Investment\ListInvestmentsUseCase;
use App\UseCases\Investment\ListInvestmentYieldsUseCase;
use App\UseCases\Investment\UpdateInvestmentPositionUseCase;
use App\UseCases\Investment\UpdateInvestmentUseCase;
use App\UseCases\Investment\UpdateInvestmentIncomeUseCase;
use App\UseCases\Investment\UpsertInvestmentPositionUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InvestmentController extends Controller
{
    public function store(CreateInvestmentRequest $request, CreateInvestmentUseCase $useCase): InvestmentResource
    {
        return new InvestmentResource($useCase->execute(InvestmentDTO::fromArray($request->validated()), $request->user()));
    }

    public function index(ListInvestmentRequest $request, ListInvestmentsUseCase $useCase): AnonymousResourceCollection
    {
        return InvestmentResource::collection($useCase->execute($request->integer('wallet_id'), $request->user()));
    }

    public function income(ListInvestmentIncomeRequest $request, ListInvestmentIncomeUseCase $useCase): AnonymousResourceCollection
    {
        return InvestmentIncomeResource::collection($useCase->execute(
            $request->integer('wallet_id'),
            $request->user(),
            $request->integer('investment_id') ?: null,
            $request->validated('from'),
            $request->validated('to'),
        ));
    }

    public function storeIncome(CreateInvestmentIncomeRequest $request, CreateInvestmentIncomeUseCase $useCase): InvestmentIncomeResource
    {
        return new InvestmentIncomeResource($useCase->execute(
            $request->integer('investment_id'),
            $request->integer('amount'),
            $request->date('transaction_date')->toDateString(),
            $request->user(),
        ));
    }

    public function updateIncome(UpdateInvestmentIncomeRequest $request, InvestmentIncome $income, UpdateInvestmentIncomeUseCase $useCase): InvestmentIncomeResource
    {
        return new InvestmentIncomeResource($useCase->execute($income, $request->validated(), $request->user()));
    }

    public function deleteIncome(Request $request, InvestmentIncome $income, DeleteInvestmentIncomeUseCase $useCase): Response
    {
        $useCase->execute($income, $request->user());

        return response()->noContent();
    }

    public function positions(Request $request, Investment $investment, ListInvestmentPositionsUseCase $useCase): AnonymousResourceCollection
    {
        return InvestmentPositionResource::collection($useCase->execute($investment, $request->user()));
    }

    public function upsertPosition(CreateInvestmentPositionRequest $request, Investment $investment, UpsertInvestmentPositionUseCase $useCase)
    {
        $position = $useCase->execute($investment, $request->validated(), $request->user());

        return (new InvestmentPositionResource($position))->response()->setStatusCode($position->wasRecentlyCreated ? 201 : 200);
    }

    public function updatePosition(CreateInvestmentPositionRequest $request, InvestmentPosition $position, UpdateInvestmentPositionUseCase $useCase): InvestmentPositionResource
    {
        return new InvestmentPositionResource($useCase->execute($position, $request->validated(), $request->user()));
    }

    public function deletePosition(Request $request, InvestmentPosition $position, DeleteInvestmentPositionUseCase $useCase): Response
    {
        $useCase->execute($position, $request->user());

        return response()->noContent();
    }

    public function positionHistory(ListInvestmentRequest $request, ListInvestmentPositionHistoryUseCase $useCase): AnonymousResourceCollection
    {
        return InvestmentPositionHistoryResource::collection($useCase->execute($request->integer('wallet_id'), $request->user()));
    }

    public function yields(ListInvestmentYieldsRequest $request, Investment $investment, ListInvestmentYieldsUseCase $useCase): AnonymousResourceCollection
    {
        return InvestmentYieldResource::collection($useCase->execute(
            $investment,
            $request->user(),
            $request->validated('from'),
            $request->validated('to'),
        ));
    }

    public function update(CreateInvestmentRequest $request, Investment $investment, UpdateInvestmentUseCase $useCase): InvestmentResource
    {
        return new InvestmentResource($useCase->execute($investment, InvestmentDTO::fromArray($request->validated()), $request->user()));
    }

    public function destroy(Request $request, Investment $investment, DeleteInvestmentUseCase $useCase): Response
    {
        $useCase->execute($investment, $request->user());

        return response()->noContent();
    }
}
