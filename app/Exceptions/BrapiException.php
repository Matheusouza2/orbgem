<?php

namespace App\Exceptions;

use RuntimeException;

class BrapiException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('A integração com a BRAPI não está configurada.');
    }

    public static function requestFailed(int $status): self
    {
        return new self("A BRAPI retornou uma resposta HTTP {$status}.", $status);
    }

    public static function invalidResponse(): self
    {
        return new self('A BRAPI retornou uma resposta sem dados de cotação.');
    }
}
