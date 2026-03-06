<?php

namespace Overtrue\TextGuard\Pipeline;

class HtmlDecode implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        $options = array_replace_recursive([
            'enabled' => true,
            'flags' => ENT_QUOTES | ENT_HTML5,
            'encoding' => 'UTF-8',
        ], $this->options);

        if (! (bool) $options['enabled']) {
            return $text;
        }

        // Decode HTML entities
        return html_entity_decode($text, (int) $options['flags'], (string) $options['encoding']);
    }
}
