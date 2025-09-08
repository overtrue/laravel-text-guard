<?php

namespace Overtrue\TextGuard\Pipeline;

class FullwidthToHalfwidth implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        $options = array_merge([
            'ascii' => true,
            'digits' => true,
            'latin' => true,
            'punct' => false,
        ], $this->options);

        if ($options['ascii']) {
            // Convert full-width ASCII to half-width
            $text = mb_convert_kana($text, 'a');
        }

        if ($options['digits']) {
            // Convert full-width digits to half-width
            $text = mb_convert_kana($text, 'n');
        }

        if ($options['latin']) {
            // Convert full-width Latin letters to half-width
            $text = mb_convert_kana($text, 'r');
        }

        if ($options['punct']) {
            // Convert full-width punctuation to half-width
            $text = mb_convert_kana($text, 's');
        }

        return $text;
    }
}
