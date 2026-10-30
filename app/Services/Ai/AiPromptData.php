<?php

namespace App\Services\Ai;

/** Prepara os fatos para os prompts: valores em reais (não centavos) e texto livre sem dados pessoais. */
class AiPromptData
{
    public function __construct(private PersonalDataScrubber $scrubber) {}

    /** @param array<string, mixed> $data */
    public function prepare(array $data): array
    {
        $prepared = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $prepared[$key] = $this->prepare($value);
            } elseif (is_string($key) && str_ends_with($key, '_cents') && is_int($value)) {
                $prepared[substr($key, 0, -6).'_brl'] = round($value / 100, 2);
            } else {
                $prepared[$key] = is_string($value) ? $this->scrubber->scrub($value) : $value;
            }
        }

        return $prepared;
    }

    /** @param array<string, mixed> $data */
    public function json(array $data): string
    {
        return json_encode($this->prepare($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
