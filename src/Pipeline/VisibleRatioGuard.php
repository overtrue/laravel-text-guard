<?php

namespace Overtrue\TextGuard\Pipeline;

class VisibleRatioGuard implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        $options = array_merge([
            'min_ratio' => 0.6,
        ], $this->options);

        $totalLength = mb_strlen($text);
        if ($totalLength === 0) {
            return $text;
        }

        // Calculate visible character count (remove control characters, zero-width characters, etc.)
        $visibleText = preg_replace('/[\p{C}\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
        $visibleLength = mb_strlen($visibleText);

        $ratio = $visibleLength / $totalLength;

        // If visible character ratio is below threshold, return empty string
        if ($ratio < $options['min_ratio']) {
            return '';
        }

        return $text;
    }
}
