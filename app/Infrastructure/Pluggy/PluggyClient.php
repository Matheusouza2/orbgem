<?php

namespace App\Infrastructure\Pluggy;

use App\Exceptions\PluggyException;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PluggyClient
{
    public function authenticate(): string
    {
        if (! $this->hasClientCredentials()) {
            $apiKey = config('services.pluggy.api_key');

            if (is_string($apiKey) && $apiKey !== '') {
                return $apiKey;
            }

            throw PluggyException::notConfigured();
        }

        return Cache::remember($this->apiKeyCacheKey(), now()->addMinutes(110), function (): string {
            $response = Http::baseUrl($this->baseUrl())
                ->acceptJson()
                ->timeout(15)
                ->post('/auth', [
                    'clientId' => config('services.pluggy.client_id'),
                    'clientSecret' => config('services.pluggy.client_secret'),
                ]);

            $this->ensureSuccessful($response);
            $apiKey = $response->json('apiKey');

            if (! is_string($apiKey) || $apiKey === '') {
                throw PluggyException::failed($response->status(), 'A Pluggy não retornou uma API Key.');
            }

            return $apiKey;
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function getItems(): array
    {
        return $this->results('/items');
    }

    /** @return array<string, mixed> */
    public function getItem(string $itemId): array
    {
        return $this->decode($this->send(fn (PendingRequest $request): Response => $request->get('/items/'.$itemId)));
    }

    /** @return array<int, array<string, mixed>> */
    public function getAccounts(string $itemId): array
    {
        return $this->results('/accounts', ['itemId' => $itemId]);
    }

    /** @return array<int, array<string, mixed>> */
    public function getTransactions(string $accountId, ?string $from = null, ?string $to = null): array
    {
        return $this->paginated('/transactions', array_filter([
            'accountId' => $accountId,
            'from' => $from,
            'to' => $to,
        ], static fn (mixed $value): bool => $value !== null));
    }

    /** @return array<int, array<string, mixed>> */
    public function getInvestments(string $itemId): array
    {
        return $this->paginated('/investments', ['itemId' => $itemId]);
    }

    public function createConnectToken(string $clientUserId, string $webhookUrl, ?string $itemId = null): string
    {
        $response = $this->send(fn (PendingRequest $request): Response => $request->post('/connect_token', array_filter([
            'itemId' => $itemId,
            'options' => [
                'clientUserId' => $clientUserId,
                'webhookUrl' => $webhookUrl,
                'avoidDuplicates' => true,
            ],
        ], static fn (mixed $value): bool => $value !== null)));

        $data = $this->decode($response);

        return $data['accessToken'] ?? throw PluggyException::failed($response->status(), 'A Pluggy não retornou um Connect Token.');
    }

    public function deleteItem(string $itemId): void
    {
        $this->ensureSuccessful($this->send(fn (PendingRequest $request): Response => $request->delete('/items/'.$itemId)));
    }

    /** @param array<string, mixed> $query */
    private function results(string $path, array $query = []): array
    {
        $data = $this->decode($this->send(fn (PendingRequest $request): Response => $request->get($path, $query)));

        return is_array($data['results'] ?? null) ? $data['results'] : [];
    }

    /** @param array<string, mixed> $query @return array<int, array<string, mixed>> */
    private function paginated(string $path, array $query): array
    {
        $page = 1;
        $all = [];
        do {
            $data = $this->decode($this->send(fn (PendingRequest $request): Response => $request->get($path, [...$query, 'pageSize' => 500, 'page' => $page])));
            $results = is_array($data['results'] ?? null) ? $data['results'] : [];
            $all = [...$all, ...$results];
            $totalPages = (int) ($data['totalPages'] ?? 0);
            $hasNext = $totalPages > $page ? true : count($results) === 500;
            $page++;
        } while ($hasNext);

        return $all;
    }

    private function request(?string $apiKey = null): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->withHeaders(['X-API-KEY' => $apiKey ?? $this->authenticate()])
            ->timeout(30);
    }

    /** @param Closure(PendingRequest): Response $callback */
    private function send(Closure $callback): Response
    {
        $response = $callback($this->request());
        if (! in_array($response->status(), [401, 403], true) || ! $this->hasClientCredentials()) {
            return $response;
        }

        Cache::forget($this->apiKeyCacheKey());

        return $callback($this->request($this->authenticate()));
    }

    private function baseUrl(): string
    {
        return (string) config('services.pluggy.base_url', 'https://api.pluggy.ai');
    }

    private function apiKeyCacheKey(): string
    {
        return 'pluggy.api_key.'.hash('sha256', (string) config('services.pluggy.client_id'));
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
