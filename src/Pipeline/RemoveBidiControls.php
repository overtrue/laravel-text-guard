<?php

namespace Overtrue\TextGuard\Pipeline;

class RemoveBidiControls implements PipelineStep
{
    public function __invoke(string $text): string
    {
        // Unicode Bidi_Control; ordinary RTL letters and joiners are not controls.
        return preg_replace('/[\x{061C}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $text) ?? $text;
    }
}
