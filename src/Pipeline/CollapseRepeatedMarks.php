<?php

namespace Overtrue\TextGuard\Pipeline;

class CollapseRepeatedMarks implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        $options = array_merge([
            'max_repeat' => 2,
            'charset' => '!?。，、…—',
        ], $this->options);

        $chars = preg_quote($options['charset'], '/');
        $maxRepeat = $options['max_repeat'];

        // Limit repeated punctuation marks to maximum repeat count
        $pattern = '/(['.$chars.'])\1{'.$maxRepeat.',}/u';

        return preg_replace_callback($pattern, function ($matches) use ($maxRepeat) {
            return str_repeat($matches[1], $maxRepeat);
        }, $text);
    }
}
