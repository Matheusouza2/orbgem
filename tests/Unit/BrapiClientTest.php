<?php

namespace Tests\Unit;

use App\Exceptions\BrapiException;
use App\Services\BrapiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrapiClientTest extends TestCase
{
    public function test_it_returns_the_first_quote_data_and_sends_the_bearer_token(): void
    {
        config(['services.brapi.token' => 'test-token']);
        Http::fake(['https://brapi.dev/api/v2/stocks/quote*' => Http::response(['results' => [['data' => ['regularMarketPrice' => 42.50, 'currency' => 'BRL']]]], 200)]);

        $quote = app(BrapiClient::class)->quote(' b3sa3 ');

        $this->assertSame(['regularMarketPrice' => 42.50, 'currency' => 'BRL'], $quote);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://brapi.dev/api/v2/stocks/quote?symbols=B3SA3'
                && $request->header('Authorization')[0] === 'Bearer test-token';
        });
    }

    public function test_it_raises_an_exception_for_a_non_successful_response(): void
    {
        config(['services.brapi.token' => 'test-token']);
        Http::fake(['https://brapi.dev/api/v2/stocks/quote*' => Http::response(['message' => 'Unauthorized'], 401)]);

        $this->expectException(BrapiException::class);
        $this->expectExceptionCode(401);

        app(BrapiClient::class)->quote('B3SA3');
    }
}
