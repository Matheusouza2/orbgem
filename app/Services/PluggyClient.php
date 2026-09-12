<?php

namespace App\Services;

use App\Exceptions\PluggyException;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PluggyClient
{
    private ?string $refreshedApiKey = null;

    public function createConnectToken(string $clientUserId, string $webhookUrl, ?string $itemId = null): string
    {
        $response = $this->send(fn (PendingRequest $request): Response => $request->post('/connect_token', array_filter(['itemId' => $itemId, 'options' => ['clientUserId' => $clientUserId, 'webhookUrl' => $webhookUrl, 'avoidDuplicates' => true]], fn ($value): bool => $value !== null)));

        return $this->decode($response)['accessToken'] ?? throw PluggyException::failed($response->status(), 'A Pluggy não retornou um Connect Token.');
    }

    /** @return array<string, mixed> */
    public function item(string $itemId): array
    {
        return $this->decode($this->send(fn (PendingRequest $request): Response => $request->get('/items/'.$itemId)));
    }

    /** @return array<int, array<string, mixed>> */
    public function accounts(string $itemId): array
    {
        return $this->decode($this->send(fn (PendingRequest $request): Response => $request->get('/accounts', ['itemId' => $itemId])))['results'] ?? [];
    }

    public function deleteItem(string $itemId): void
    {
        $this->ensureSuccessful($this->send(fn (PendingRequest $request): Response => $request->delete('/items/'.$itemId)));
    }

    private function request(?string $apiKey = null): PendingRequest
    {
        $apiKey ??= $this->refreshedApiKey ?? $this->apiKey();

        return Http::baseUrl((string) config('services.pluggy.base_url', 'https://api.pluggy.ai'))->acceptJson()->withHeaders(['X-API-KEY' => $apiKey])->timeout(15);
    }

    private function send(Closure $callback): Response
    {
        $response = $callback($this->request());
        if (! in_array($response->status(), [401, 403], true) || ! $this->hasClientCredentials()) {
            return $response;
        }

        $this->forgetCachedApiKey();
        $this->refreshedApiKey = $this->authenticate();

        return $callback($this->request($this->refreshedApiKey));
    }

    private function apiKey(): string
    {
        if ($this->hasClientCredentials()) {
            return Cache::remember($this->apiKeyCacheKey(), now()->addMinutes(110), fn (): string => $this->authenticate());
        }

        $apiKey = config('services.pluggy.api_key');
        if (is_string($apiKey) && $apiKey !== '') {
            return $apiKey;
        }

        if (! $this->hasClientCredentials()) {
            throw PluggyException::notConfigured();
        }

        return $this->authenticate();
    }

    private function apiKeyCacheKey(): string
    {
        return 'pluggy.api_key.'.hash('sha256', (string) config('services.pluggy.client_id'));
    }

    private function forgetCachedApiKey(): void
    {
        if ($this->hasClientCredentials()) {
            Cache::forget($this->apiKeyCacheKey());
        }
    }

    private function authenticate(): string
    {
        $response = Http::baseUrl((string) config('services.pluggy.base_url', 'https://api.pluggy.ai'))->acceptJson()->timeout(15)->post('/auth', [
            'clientId' => config('services.pluggy.client_id'),
            'clientSecret' => config('services.pluggy.client_secret'),
        ]);
        $this->ensureSuccessful($response);
        $apiKey = $response->json('apiKey');
        if (! is_string($apiKey) || $apiKey === '') {
            throw PluggyException::failed($response->status(), 'A Pluggy não retornou uma API Key.');
        }

        return $apiKey;
    }

    private function hasClientCredentials(): bool
    {
        return is_string(config('services.pluggy.client_id'))
            && config('services.pluggy.client_id') !== ''
            && is_string(config('services.pluggy.client_secret'))
            && config('services.pluggy.client_secret') !== '';
    }

    /** @return array<string, mixed> */
    private function decode(Response $response): array
    {
        $this->ensureSuccessful($response);
        $data = $response->json();

        return is_array($data) ? $data : throw PluggyException::failed($response->status(), 'A Pluggy retornou um payload inválido.');
    }

    private function ensureSuccessful(Response $response): void
    {
        if (! $response->successful()) {
            throw PluggyException::failed($response->status(), (string) ($response->json('message') ?? 'Não foi possível comunicar com a Pluggy.'));
        }
    }
}
