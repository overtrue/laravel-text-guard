<?php

namespace Overtrue\TextGuard\Pipeline;

use InvalidArgumentException;

class TruncateLength implements PipelineStep
{
    public function __construct(protected array $options = [])
    {
        $this->options += ['max' => 5000, 'unit' => 'codepoint'];
        if (! is_int($this->options['max']) || $this->options['max'] < 0) {
            throw new InvalidArgumentException('Truncation max must be a non-negative integer.');
        }
        if (! in_array($this->options['unit'], ['codepoint', 'grapheme'], true)) {
            throw new InvalidArgumentException('Truncation unit must be codepoint or grapheme.');
        }
    }

    public function __invoke(string $text): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            throw new InvalidArgumentException('Text must be valid UTF-8.');
        }
        if ($this->options['max'] === 0) {
            return '';
        }

        return $this->options['unit'] === 'grapheme'
            ? grapheme_substr($text, 0, $this->options['max'])
            : mb_substr($text, 0, $this->options['max'], 'UTF-8');
    }
}
