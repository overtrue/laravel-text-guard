<?php

namespace Overtrue\TextGuard\Pipeline;

class CollapseSpaces implements PipelineStep
{
    public function __invoke(string $text): string
    {
        // Collapse multiple consecutive spaces into single space
        return preg_replace('/\s+/', ' ', $text);
    }
}
