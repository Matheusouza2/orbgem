<?php

namespace App\Services\Ai;

use App\Exceptions\AiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GroqClient
{
    /** @param  list<array{role: string, content: string}>  $messages */
    public function chat(array $messages, int $maxTokens = 1500): string
    {
        $key = config('services.groq.api_key');
        if (! is_string($key) || $key === '') {
            throw AiException::notConfigured('o Groq');
        }

        try {
            $response = Http::baseUrl((string) config('services.groq.base_url'))
                ->acceptJson()
                ->withToken($key)
                ->connectTimeout(5)
                ->timeout((int) config('services.groq.timeout'))
                ->post('/chat/completions', [
                    'model' => (string) config('services.groq.model'),
                    'messages' => $messages,
                    'temperature' => (float) config('services.groq.temperature'),
                    'reasoning_effort' => (string) config('services.groq.reasoning_effort'),
                    'max_completion_tokens' => $maxTokens,
                ]);
        } catch (ConnectionException $exception) {
            throw new AiException('Não foi possível conectar ao Groq.', 0, $exception);
        }

        if (! $response->successful()) {
            throw AiException::requestFailed('O Groq', $response->status());
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw AiException::invalidResponse('O Groq');
        }

        return trim($content);
    }
}
