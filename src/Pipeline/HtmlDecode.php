<?php

namespace Overtrue\TextGuard\Pipeline;

class HtmlDecode implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        $options = array_merge([
            'enabled' => true,
            'flags' => ENT_QUOTES | ENT_HTML5,
            'encoding' => 'UTF-8',
        ], $this->options);

        if (! $options['enabled']) {
            return $text;
        }

        // Decode HTML entities
        return htmlspecialchars_decode($text, $options['flags']);
    }
}
