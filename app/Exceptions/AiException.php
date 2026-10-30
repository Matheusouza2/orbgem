<?php

namespace App\Exceptions;

use RuntimeException;

class AiException extends RuntimeException
{
    public static function notConfigured(string $provider): self
    {
        return new self("A integração com {$provider} não está configurada.");
    }

    public static function requestFailed(string $provider, int $status): self
    {
        return new self("{$provider} retornou uma resposta HTTP {$status}.", $status);
    }

    public static function invalidResponse(string $provider): self
    {
        return new self("{$provider} retornou uma resposta sem conteúdo utilizável.");
    }
}
