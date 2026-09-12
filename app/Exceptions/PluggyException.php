<?php

namespace App\Exceptions;

use RuntimeException;

class PluggyException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('A integração Open Finance não está configurada.');
    }

    public static function failed(int $status, string $message = 'A Pluggy retornou um erro.'): self
    {
        return new self($message, $status);
    }
}
