<?php

namespace App\Services\Ai;

class PersonalDataScrubber
{
    /** Remove e-mails, CPF/CNPJ, telefones, URLs e sequências longas de dígitos antes de enviar texto a um provedor de IA. */
    public function scrub(string $text): string
    {
        $patterns = [
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i' => '[email]',
            '/https?:\/\/\S+/i' => '[url]',
            '/\b\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}\b/' => '[documento]',
            '/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/' => '[documento]',
            '/(?<!\d)(?:\+?55\s?)?\(?\d{2}\)?\s?9?\d{4}[-\s]?\d{4}(?!\d)/' => '[telefone]',
            '/\d{8,}/' => '[numero]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text) ?? '';
    }
}
