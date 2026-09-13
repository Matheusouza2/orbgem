<?php

namespace App\Http\Controllers\Api;

use App\DTO\InvestmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\CreateInvestmentRequest;
use App\Http\Requests\Investment\ListInvestmentIncomeRequest;
use App\Http\Requests\Investment\ListInvestmentRequest;
use App\Http\Requests\Investment\ListInvestmentYieldsRequest;
use App\Http\Resources\InvestmentIncomeResource;
use App\Http\Resources\InvestmentResource;
use App\Http\Resources\InvestmentYieldResource;
use App\Models\Investment;
use App\UseCases\Investment\CreateInvestmentUseCase;
use App\UseCases\Investment\DeleteInvestmentUseCase;
use App\UseCases\Investment\ListInvestmentIncomeUseCase;
use App\UseCases\Investment\ListInvestmentsUseCase;
use App\UseCases\Investment\ListInvestmentYieldsUseCase;
use App\UseCases\Investment\UpdateInvestmentUseCase;
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
