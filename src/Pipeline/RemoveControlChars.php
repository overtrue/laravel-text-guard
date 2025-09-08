<?php

namespace Overtrue\TextGuard\Pipeline;

class RemoveControlChars implements PipelineStep
{
    public function __invoke(string $text): string
    {
        // Remove control characters, but preserve newlines and tabs
        return preg_replace('/[\x{00}-\x{08}\x{0B}\x{0C}\x{0E}-\x{1F}\x{7F}]/u', '', $text);
    }
}
