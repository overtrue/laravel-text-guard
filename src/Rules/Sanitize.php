<?php

namespace Overtrue\TextGuard\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;
use Overtrue\TextGuard\TextGuard;

/**
 * Filter the attribute through a TextGuard preset and write the result into the
 * validator data only. The HTTP request is never modified.
 */
class Sanitize implements ValidationRule, ValidatorAwareRule
{
    protected ?Validator $validator = null;

    public function __construct(
        protected string $preset = 'safe',
        protected array $overrides = []
    ) {}

    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->validator === null) {
            return;
        }

        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8')) {
            return;
        }

        $clean = TextGuard::filter($value, $this->preset, $this->overrides);
        $data = $this->validator->getData();
        data_set($data, $attribute, $clean);
        $this->validator->setData($data);
    }
}
