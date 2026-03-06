<?php

namespace Overtrue\TextGuard\Pipeline;

use Normalizer;

class NormalizeUnicode implements PipelineStep
{
    private const FORM_MAP = [
        'NFC' => Normalizer::FORM_C,
        'NFD' => Normalizer::FORM_D,
        'NFKC' => Normalizer::FORM_KC,
        'NFKD' => Normalizer::FORM_KD,
    ];

    public function __construct(protected ?string $form = 'NFKC') {}

    public function __invoke(string $text): string
    {

        if (! $this->form) {
            return $text;
        }

        if (class_exists(Normalizer::class)) {
            $normalizedForm = strtoupper($this->form);
            $form = self::FORM_MAP[$normalizedForm] ?? null;
            if ($form === null) {
                return $text;
            }

            return Normalizer::normalize($text, $form) ?: $text;
        }

        return $text;
    }
}
