<?php

namespace Overtrue\TextGuard\Pipeline;

interface PipelineStep
{
    /**
     * Process text
     */
    public function __invoke(string $text): string;
}
