<?php

namespace Tests\Unit\Rules;

use Overtrue\TextGuard\Rules\Sanitized;
use Tests\TestCase;

class SanitizedTest extends TestCase
{
    public function test_sanitized_rule_passes_with_valid_string()
    {
        $rule = new Sanitized(0.8, 1);
        $fails = false;

        $rule->validate('test', 'Normal text content', function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails);
    }

    public function test_sanitized_rule_fails_with_non_string()
    {
        $rule = new Sanitized(0.8, 1);
        $fails = false;
        $message = '';

        $rule->validate('test', 123, function ($msg) use (&$fails, &$message) {
            $fails = true;
            $message = $msg;
        });

        $this->assertTrue($fails);
        $this->assertStringContainsString('must be a string', $message);
    }

    public function test_sanitized_rule_fails_when_too_short()
    {
        $rule = new Sanitized(0.8, 5);
        $fails = false;
        $message = '';

        $rule->validate('test', 'Hi', function ($msg) use (&$fails, &$message) {
            $fails = true;
            $message = $msg;
        });

        $this->assertTrue($fails);
        $this->assertStringContainsString('is too short', $message);
    }

    public function test_sanitized_rule_fails_with_too_many_invisible_chars()
    {
        $rule = new Sanitized(0.8, 1);
        $fails = false;
        $message = '';

        $rule->validate('test', json_decode('"\u200B\u200B\u200B\u200B\u200B"'), function ($msg) use (&$fails, &$message) {
            $fails = true;
            $message = $msg;
        });

        $this->assertTrue($fails);
        $this->assertStringContainsString('not visible enough', $message);
    }

    public function test_sanitized_rule_with_chinese_malicious_attacks()
    {
        $rule = new Sanitized(0.7, 1);
        $fails = false;

        // 测试中文零宽字符攻击 - 应该通过验证
        $maliciousText = '正常文本'.json_decode('"\u200B\u200C"').'内容';
        $rule->validate('test', $maliciousText, function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails);

        // 测试中文控制字符攻击 - 应该通过验证
        $controlAttack = "正常文本\x00\x01内容";
        $rule->validate('test', $controlAttack, function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails);

        // 测试中文HTML注入攻击 - 应该通过验证
        $htmlAttack = '<script>alert("中文XSS")</script>正常内容';
        $rule->validate('test', $htmlAttack, function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails);
    }
}
