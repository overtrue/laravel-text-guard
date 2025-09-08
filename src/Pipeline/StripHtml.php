<?php

namespace Overtrue\TextGuard\Pipeline;

class StripHtml implements PipelineStep
{
    public function __invoke(string $text): string
    {
        // Remove all HTML tags
        return strip_tags($text);
    }
}
