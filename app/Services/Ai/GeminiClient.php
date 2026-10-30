<?php

namespace App\Services\Ai;

use App\Exceptions\AiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GeminiClient
{
    /** Tenta o modelo principal e, se ele estiver indisponível, descontinuado ou sobrecarregado, o modelo reserva. */
    public function generateJson(string $systemInstruction, string $prompt): array
    {
        $key = config('services.gemini.api_key');
        if (! is_string($key) || $key === '') {
            throw AiException::notConfigured('o Gemini');
        }

        $models = array_values(array_unique(array_filter([config('services.gemini.model'), config('services.gemini.fallback_model')])));
        $failure = null;

        foreach ($models as $model) {
            try {
                $response = $this->request($key, (string) $model, $systemInstruction, $prompt);
            } catch (ConnectionException $exception) {
                throw new AiException('Não foi possível conectar ao Gemini.', 0, $exception);
            }

            if ($response->successful()) {
                $decoded = json_decode((string) $response->json('candidates.0.content.parts.0.text'), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                $failure = AiException::invalidResponse('O Gemini');

                continue;
            }

            $failure = AiException::requestFailed('O Gemini', $response->status());
        }

        throw $failure ?? AiException::invalidResponse('O Gemini');
    }

    private function request(string $key, string $model, string $systemInstruction, string $prompt): Response
    {
        return Http::baseUrl((string) config('services.gemini.base_url'))
            ->acceptJson()
            ->withHeaders(['x-goog-api-key' => $key])
            ->connectTimeout(5)
            ->timeout((int) config('services.gemini.timeout'))
            ->post("/v1beta/models/{$model}:generateContent", [
                'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => (float) config('services.gemini.temperature'), 'responseMimeType' => 'application/json'],
            ]);
    }
}
