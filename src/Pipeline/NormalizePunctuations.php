<?php

namespace Overtrue\TextGuard\Pipeline;

class NormalizePunctuations implements PipelineStep
{
    public function __construct(protected ?string $locale = 'zh') {}

    public function __invoke(string $text): string
    {

        if (! $this->locale) {
            return $text;
        }

        $replacements = match ($this->locale) {
            'en' => [
                '，' => ',',
                '。' => '.',
                '？' => '?',
                '！' => '!',
                '：' => ':',
                '；' => ';',
                '“' => '"',
                '”' => '"',
                '‘' => "'",
                '’' => "'",
                '（' => '(',
                '）' => ')',
                '【' => '[',
                '】' => ']',
                '《' => '<',
                '》' => '>',
            ],
            'zh' => [
                ',' => '，',
                '.' => '。',
                '?' => '？',
                '!' => '！',
                ':' => '：',
                ';' => '；',
                '"' => '＂',
                "'" => '＇',
                '(' => '（',
                ')' => '）',
                '[' => '【',
                ']' => '】',
                '<' => '《',
                '>' => '》',
            ],
            default => []
        };

        return strtr($text, $replacements);
    }
}
