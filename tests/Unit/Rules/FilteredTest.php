<?php

namespace Tests\Unit\Rules;

use Overtrue\TextGuard\Rules\Filtered;
use Overtrue\TextGuard\TextGuard;
use Tests\TestCase;

class FilteredTest extends TestCase
{
    public function test_clean_input_is_validated_without_mutating_the_request(): void
    {
        request()->replace(['unrelated' => 'original']);
        $input = ['items' => [['name' => 'Hello'], ['name' => '世界']]];
        $validator = validator($input, ['items.*.name' => [new Filtered]]);
        $this->assertTrue($validator->passes());
        $this->assertSame($input, $validator->validated());
        $this->assertSame(['unrelated' => 'original'], request()->all());
    }

    public function test_dirty_input_is_rejected_without_mutating_any_input(): void
    {
        $input = ['items' => [['name' => " text\u{200B} "]]];
        request()->replace($input);
        $validator = validator($input, ['items.*.name' => [new Filtered]]);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('items.0.name'));
        $this->assertSame($input, $validator->getData());
        $this->assertSame($input, request()->all());
    }

    public function test_explicit_filtering_produces_validated_output(): void
    {
        $input = ['name' => TextGuard::filter('<b>Hello</b>')];
        $validator = validator($input, ['name' => [new Filtered]]);
        $this->assertSame(['name' => 'Hello'], $validator->validated());
    }

    public function test_non_strings_and_invalid_utf8_fail_validation(): void
    {
        foreach ([123, [], "\xFF"] as $value) {
            $messages = [];
            (new Filtered)->validate('name', $value, function ($message) use (&$messages) {
                $messages[] = $message;
            });
            $this->assertCount(1, $messages);
        }
    }

    public function test_empty_policy_is_independent_of_normalization(): void
    {
        foreach ([['', true, false], ['', false, true], ['   ', true, false], ['   ', false, false]] as [$input, $required, $valid]) {
            $messages = [];
            (new Filtered('safe', $required))->validate('name', $input, function ($message) use (&$messages) {
                $messages[] = $message;
            });
            $this->assertSame($valid, $messages === []);
        }
    }

    public function test_rule_supports_the_same_overrides_as_the_filter(): void
    {
        $validator = validator(['name' => ' Hello '], [
            'name' => [new Filtered('safe', overrides: ['trim_whitespace' => false])],
        ]);
        $this->assertSame(['name' => ' Hello '], $validator->validated());
    }
}
