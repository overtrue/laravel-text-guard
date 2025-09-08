<?php

namespace Overtrue\TextGuard\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Overtrue\TextGuard\TextGuard;

class Filtered implements ValidationRule
{
    public function __construct(
        protected string $preset = 'safe',
        protected bool $mustNotBeEmpty = true
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(':attribute must be a string.');

            return;
        }

        $clean = TextGuard::filter($value, $this->preset);

        // Write cleaned result back: let controller get clean value directly
        request()->merge([$attribute => $clean]);

        if ($this->mustNotBeEmpty && mb_strlen(trim($clean)) === 0) {
            $fail(':attribute is empty after filtering.');
        }
    }
}
