<?php

namespace Overtrue\TextGuard\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Sanitized implements ValidationRule
{
    public function __construct(
        protected float $minVisibleRatio = 0.6,
        protected int $minLength = 1
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(':attribute must be a string.');

            return;
        }

        $len = mb_strlen($value);
        if ($len < $this->minLength) {
            $fail(':attribute is too short.');

            return;
        }

        // Simple visibility calculation
        $visible = preg_replace('/[\p{C}\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);
        if ($len > 0 && (mb_strlen($visible) / $len) < $this->minVisibleRatio) {
            $fail(':attribute is not visible enough (contains too many invisible characters).');
        }
    }
}
