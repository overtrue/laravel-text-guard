<?php

namespace Overtrue\TextGuard\Pipeline;

class RemoveZeroWidth implements PipelineStep
{
    public function __invoke(string $text): string
    {
        // Remove zero-width characters (U+200B..200D, U+FEFF)
        return preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $text);
    }
}
