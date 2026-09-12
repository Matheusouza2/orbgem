<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrapiQuoteRequest;
use App\Http\Resources\BrapiQuoteResource;
use App\Services\BrapiClient;

class BrapiQuoteController extends Controller
{
    public function __invoke(BrapiQuoteRequest $request, BrapiClient $client): BrapiQuoteResource
    {
        return new BrapiQuoteResource($client->quote($request->validated('symbol')));
    }
}
