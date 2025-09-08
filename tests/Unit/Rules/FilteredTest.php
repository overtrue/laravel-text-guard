<?php

namespace Tests\Unit\Rules;

use Overtrue\TextGuard\Rules\Filtered;
use Tests\TestCase;

class FilteredTest extends TestCase
{
    public function test_filtered_rule_passes_with_valid_string()
    {
        $rule = new Filtered('safe');
        $fails = false;

        $rule->validate('test', '  Hello World  ', function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails);
    }

    public function test_filtered_rule_fails_with_non_string()
    {
        $rule = new Filtered('safe');
        $fails = false;
        $message = '';

        $rule->validate('test', 123, function ($msg) use (&$fails, &$message) {
            $fails = true;
            $message = $msg;
        });

        $this->assertTrue($fails);
        $this->assertStringContainsString('must be a string', $message);
    }

    public function test_filtered_rule_fails_when_empty_after_filtering()
    {
        $rule = new Filtered('safe', true);
        $fails = false;
        $message = '';

        $rule->validate('test', '   ', function ($msg) use (&$fails, &$message) {
            $fails = true;
            $message = $msg;
        });

        $this->assertTrue($fails);
        $this->assertStringContainsString('is empty after filtering', $message);
    }

    public function test_filtered_rule_allows_empty_when_must_not_be_empty_false()
    {
        $rule = new Filtered('safe', false);
        $fails = false;

        $rule->validate('test', '   ', function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails);
    }

    public function test_filtered_rule_with_chinese_malicious_content()
    {
        $rule = new Filtered('safe');
        $fails = false;

        // 测试中文零宽字符攻击
        $maliciousText = '正常文本'.json_decode('"\u200B\u200C\u200D"').'隐藏内容';
        $rule->validate('test', $maliciousText, function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails);
        $this->assertEquals('正常文本隐藏内容', request()->input('test'));
    }
}
