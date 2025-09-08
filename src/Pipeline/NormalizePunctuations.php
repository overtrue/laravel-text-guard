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
                '"' => '"',
                '"' => '"',
                '' => "'",
                '' => "'",
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
                '"' => '"',
                '"' => '"',
                "'" => "'",
                "'" => "'",
                '(' => '（',
                ')' => '）',
                '[' => '【',
                ']' => '】',
                '<' => '《',
                '>' => '》',
            ],
            default => []
        };

        $filteredReplacements = array_filter($replacements, fn ($value, $key) => $value !== '' && $key !== '', ARRAY_FILTER_USE_BOTH);

        return strtr($text, $filteredReplacements);
    }
}
