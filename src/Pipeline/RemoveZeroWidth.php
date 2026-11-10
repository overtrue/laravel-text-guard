<?php

namespace Overtrue\TextGuard\Pipeline;

class RemoveZeroWidth implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        // ZWNJ and ZWJ also carry meaning in writing systems and emoji sequences.
        $pattern = ($this->options['preserve_joiners'] ?? false)
            ? '/[\x{200B}\x{FEFF}\x{2060}]/u'
            : '/[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{2060}]/u';

        return preg_replace($pattern, '', $text) ?? $text;
    }
}
