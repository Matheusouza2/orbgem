<?php

namespace App\Services;

use App\Exceptions\BrapiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BrapiClient
{
    /** @return array<string, float> */
    public function cdi(string $startDate, string $endDate): array
    {
        $token = config('services.brapi.token');
        if (! is_string($token) || $token === '') {
            throw BrapiException::notConfigured();
        }

        try {
            $response = Http::baseUrl((string) config('services.brapi.base_url', 'https://brapi.dev'))->acceptJson()->withToken($token)->connectTimeout(3)->timeout(10)->get('/api/v2/macro', ['symbols' => 'cdi', 'startDate' => $startDate, 'endDate' => $endDate, 'sortOrder' => 'asc', 'limit' => 100]);
        } catch (ConnectionException $exception) {
            throw new BrapiException('Não foi possível conectar à BRAPI.', 0, $exception);
        }
        if (! $response->successful()) {
            throw BrapiException::requestFailed($response->status());
        }
        $observations = $response->json('results.0.observations');
        if (! is_array($observations)) {
            throw BrapiException::invalidResponse();
        }

        return collect($observations)->filter(fn ($observation): bool => is_array($observation) && isset($observation['date'], $observation['value']))->mapWithKeys(fn (array $observation): array => [(string) $observation['date'] => (float) $observation['value']])->all();
    }

    /** @return array<string, mixed> */
    public function quote(string $symbol): array
    {
        $token = config('services.brapi.token');

        if (! is_string($token) || $token === '') {
            throw BrapiException::notConfigured();
        }

        try {
            $response = Http::baseUrl((string) config('services.brapi.base_url', 'https://brapi.dev'))
                ->acceptJson()
                ->withToken($token)
                ->timeout(10)
                ->get('/api/v2/stocks/quote', ['symbols' => strtoupper(trim($symbol))]);
        } catch (ConnectionException $exception) {
            throw new BrapiException('Não foi possível conectar à BRAPI.', 0, $exception);
        }

        if (! $response->successful()) {
            throw BrapiException::requestFailed($response->status());
        }

        $data = $response->json('results.0.data');

        if (! is_array($data)) {
            throw BrapiException::invalidResponse();
        }

        return $data;
    }
}
