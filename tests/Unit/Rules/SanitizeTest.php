<?php

namespace Tests\Unit\Rules;

use Overtrue\TextGuard\Rules\Filtered;
use Overtrue\TextGuard\Rules\Sanitize;
use Overtrue\TextGuard\TextGuard;
use Tests\TestCase;

class SanitizeTest extends TestCase
{
    public function test_sanitize_writes_clean_value_into_validator_data(): void
    {
        $dirty = '  Test  '.json_decode('"\u200B"').'  ';
        $validator = validator(['nickname' => $dirty], [
            'nickname' => [new Sanitize('safe')],
        ]);

        $this->assertTrue($validator->passes());
        $this->assertSame('Test', $validator->validated()['nickname']);
    }

    public function test_sanitize_does_not_touch_the_global_request(): void
    {
        $dirty = '  Dirty  ';
        request()->replace(['nickname' => $dirty, 'other' => 'keep']);

        $validator = validator(['nickname' => $dirty], [
            'nickname' => [new Sanitize('safe')],
        ]);
        $validator->passes();

        $this->assertSame($dirty, request()->input('nickname'));
        $this->assertSame('Dirty', $validator->validated()['nickname']);
    }

    public function test_sanitize_with_filtered_rule_accepts_cleaned_value(): void
    {
        $dirty = '<b>Hi</b>';
        $validator = validator(['name' => $dirty], [
            'name' => ['required', 'string', new Sanitize('safe'), new Filtered('safe')],
        ]);

        $this->assertTrue($validator->passes());
        $this->assertSame('Hi', $validator->validated()['name']);
    }

    public function test_sanitize_skips_non_string_values_without_failing(): void
    {
        $validator = validator(['name' => 123], [
            'name' => [new Sanitize('safe')],
        ]);

        $this->assertTrue($validator->passes());
        $this->assertSame(123, $validator->validated()['name']);
    }

    public function test_unknown_preset_throws_during_sanitize(): void
    {
        $validator = validator(['name' => 'x'], [
            'name' => [new Sanitize('missing-preset')],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $validator->passes();
    }

    public function test_sanitize_respects_overrides_like_filter(): void
    {
        $validator = validator(['name' => ' Hello '], [
            'name' => [new Sanitize('safe', ['trim_whitespace' => false])],
        ]);
        $validator->passes();

        $this->assertSame(' Hello ', $validator->validated()['name']);
        $this->assertSame('Hello', TextGuard::filter(' Hello ', 'safe'));
    }

    public function test_sanitize_writes_nested_wildcard_attributes(): void
    {
        $validator = validator(['items' => [['name' => '  A  ']]], [
            'items.*.name' => [new Sanitize('safe')],
        ]);

        $this->assertTrue($validator->passes());
        $this->assertSame('A', $validator->validated()['items'][0]['name']);
    }
}
