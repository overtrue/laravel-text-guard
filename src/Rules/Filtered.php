<?php

namespace Overtrue\TextGuard\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Overtrue\TextGuard\TextGuard;

class Filtered implements ValidationRule
{
    public function __construct(
        protected string $preset = 'safe',
        protected bool $mustNotBeEmpty = true,
        protected array $overrides = []
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(':attribute must be a string.');

            return;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $fail(':attribute must be valid UTF-8.');

            return;
        }

        $clean = TextGuard::filter($value, $this->preset, $this->overrides);
        if ($this->mustNotBeEmpty && trim($clean) === '') {
            $fail(':attribute is empty after filtering.');
        } elseif ($clean !== $value) {
            $fail(':attribute does not match the configured text pipeline.');
        }
    }
}
