<?php

namespace Overtrue\TextGuard\Pipeline;

use Normalizer;

class NormalizeUnicode implements PipelineStep
{
    public function __construct(protected ?string $form = 'NFKC') {}

    public function __invoke(string $text): string
    {

        if (! $this->form) {
            return $text;
        }

        if (class_exists(Normalizer::class)) {
            return Normalizer::normalize($text, constant(Normalizer::class.'::'.$this->form)) ?: $text;
        }

        return $text;
    }
}
