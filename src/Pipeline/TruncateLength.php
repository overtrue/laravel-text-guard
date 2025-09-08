<?php

namespace Overtrue\TextGuard\Pipeline;

class TruncateLength implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        $options = array_merge([
            'max' => 5000,
        ], $this->options);

        if (mb_strlen($text) > $options['max']) {
            return mb_substr($text, 0, $options['max']);
        }

        return $text;
    }
}
