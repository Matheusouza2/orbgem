<?php

namespace Tests\Unit;

use App\Exceptions\PluggyException;
use App\Infrastructure\Pluggy\PluggyClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PluggyClientTest extends TestCase
{
    public function test_it_authenticates_and_caches_the_api_key(): void
    {
        config([
            'services.pluggy.base_url' => 'https://api.pluggy.ai',
            'services.pluggy.client_id' => 'client-id',
            'services.pluggy.client_secret' => 'client-secret',
        ]);
        Cache::flush();
        Http::fake(['https://api.pluggy.ai/auth' => Http::response(['apiKey' => 'api-key'], 200)]);

        $client = app(PluggyClient::class);

        self::assertSame('api-key', $client->authenticate());
        self::assertSame('api-key', $client->authenticate());
        Http::assertSentCount(1);
    }

    public function test_it_returns_an_item_and_sends_the_api_key(): void
    {
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        Http::fake(['https://api.pluggy.ai/items/item-1' => Http::response(['id' => 'item-1', 'status' => 'UPDATED'], 200)]);

        $item = app(PluggyClient::class)->getItem('item-1');

        self::assertSame('item-1', $item['id']);
        Http::assertSent(fn ($request): bool => $request->hasHeader('X-API-KEY', 'test-api-key'));
    }

    public function test_it_throws_a_pluggy_exception_for_non_successful_responses(): void
    {
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        Http::fake(['https://api.pluggy.ai/items/item-1' => Http::response(['message' => 'Not found'], 404)]);

        $this->expectException(PluggyException::class);

        app(PluggyClient::class)->getItem('item-1');
    }
}
