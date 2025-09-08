<?php

namespace Overtrue\TextGuard\Pipeline;

class TrimWhitespace implements PipelineStep
{
    public function __invoke(string $text): string
    {
        // Remove leading and trailing whitespace (including full-width spaces)
        return preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $text);
    }
}
