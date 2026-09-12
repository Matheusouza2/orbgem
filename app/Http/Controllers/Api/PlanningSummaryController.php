<?php

namespace App\Http\Controllers\Api;

use App\DTO\MonthlySummaryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\MonthlySummaryRequest;
use App\Http\Resources\MonthlySummaryResource;
use App\UseCases\Transaction\MonthlySummaryUseCase;

class PlanningSummaryController extends Controller
{
    public function __invoke(MonthlySummaryRequest $request, MonthlySummaryUseCase $useCase): MonthlySummaryResource
    {
        return new MonthlySummaryResource($useCase->execute(MonthlySummaryDTO::fromArray($request->validated()), $request->user()));
    }
}
