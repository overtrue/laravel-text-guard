<?php

namespace Tests\Unit;

use Overtrue\TextGuard\Pipeline\CollapseRepeatedMarks;
use Overtrue\TextGuard\Pipeline\CollapseSpaces;
use Overtrue\TextGuard\Pipeline\FullwidthToHalfwidth;
use Overtrue\TextGuard\Pipeline\NormalizePunctuations;
use Overtrue\TextGuard\Pipeline\NormalizeUnicode;
use Overtrue\TextGuard\Pipeline\RemoveControlChars;
use Overtrue\TextGuard\Pipeline\RemoveZeroWidth;
use Overtrue\TextGuard\Pipeline\StripHtml;
use Overtrue\TextGuard\Pipeline\TrimWhitespace;
use Overtrue\TextGuard\Pipeline\TruncateLength;
use Overtrue\TextGuard\Pipeline\VisibleRatioGuard;
use Overtrue\TextGuard\Pipeline\WhitelistHtml;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    public function test_trim_whitespace()
    {
        $filter = new TrimWhitespace;
        $result = $filter('  Hello World  　  ');

        $this->assertEquals('Hello World', $result);
    }

    public function test_collapse_spaces()
    {
        $filter = new CollapseSpaces;
        $result = $filter('Hello    World   Test');

        $this->assertEquals('Hello World Test', $result);
    }

    public function test_remove_control_chars()
    {
        $filter = new RemoveControlChars;
        $result = $filter('Hello'.chr(0).'World'.chr(1).'Test');

        $this->assertEquals('HelloWorldTest', $result);
    }

    public function test_remove_zero_width()
    {
        $filter = new RemoveZeroWidth;
        $text = 'Hello'.json_decode('"\u200B"').'World'.json_decode('"\u200C"').'Test';
        $result = $filter($text);

        $this->assertEquals('HelloWorldTest', $result);
    }

    public function test_normalize_unicode()
    {
        $filter = new NormalizeUnicode('NFKC');
        $result = $filter('café');

        $this->assertIsString($result);
    }

    public function test_fullwidth_to_halfwidth()
    {
        $filter = new FullwidthToHalfwidth(['ascii' => true, 'digits' => true]);
        $result = $filter('Ｈｅｌｌｏ１２３');

        $this->assertEquals('Hello123', $result);
    }

    public function test_normalize_punctuations_zh()
    {
        $filter = new NormalizePunctuations('zh');
        $result = $filter('Hello, world!');

        $this->assertEquals('Hello， world！', $result);
    }

    public function test_normalize_punctuations_en()
    {
        $filter = new NormalizePunctuations('en');
        $result = $filter('Hello， world！');

        $this->assertEquals('Hello, world!', $result);
    }

    public function test_strip_html()
    {
        $filter = new StripHtml;
        $result = $filter('<p>Hello <b>World</b></p>');

        $this->assertEquals('Hello World', $result);
    }

    public function test_whitelist_html()
    {
        $filter = new WhitelistHtml([
            'tags' => ['p', 'b'],
            'attrs' => ['href'],
            'protocols' => ['http', 'https'],
        ]);
        $result = $filter("<p>Hello <b>World</b> <script>alert('xss')</script></p>");

        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('World', $result);
        $this->assertStringNotContainsString('script', $result);
    }

    public function test_collapse_repeated_marks()
    {
        $filter = new CollapseRepeatedMarks(['max_repeat' => 2, 'charset' => '!?']);
        $result = $filter('Hello!!!! World???');

        $this->assertEquals('Hello!! World??', $result);
    }

    public function test_visible_ratio_guard()
    {
        $filter = new VisibleRatioGuard(['min_ratio' => 0.8]);
        $result = $filter('Normal text');

        $this->assertEquals('Normal text', $result);
    }

    public function test_visible_ratio_guard_fails()
    {
        $filter = new VisibleRatioGuard(['min_ratio' => 0.8]);
        $result = $filter(json_decode('"\u200B\u200B\u200B\u200B\u200B"'));

        $this->assertEquals('', $result);
    }

    public function test_truncate_length()
    {
        $filter = new TruncateLength(['max' => 10]);
        $result = $filter('This is a very long text');

        $this->assertEquals('This is a ', $result);
    }

    public function test_chinese_malicious_attacks()
    {
        // 测试中文零宽字符攻击
        $zeroWidthFilter = new RemoveZeroWidth;
        $maliciousText = '正常文本'.json_decode('"\u200B\u200C\u200D\uFEFF"').'隐藏内容';
        $result = $zeroWidthFilter($maliciousText);
        $this->assertEquals('正常文本隐藏内容', $result);

        // 测试中文控制字符攻击
        $controlCharFilter = new RemoveControlChars;
        $controlAttack = "正常文本\x00\x01\x02\x1F隐藏内容";
        $result = $controlCharFilter($controlAttack);
        $this->assertEquals('正常文本隐藏内容', $result);

        // 测试中文HTML注入攻击
        $htmlFilter = new WhitelistHtml(['tags' => ['p', 'b']]);
        $htmlAttack = '<script>alert("中文XSS")</script><p>正常内容</p>';
        $result = $htmlFilter($htmlAttack);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('正常内容', $result);

        // 测试中文重复标点攻击
        $repeatFilter = new CollapseRepeatedMarks(['max_repeat' => 2, 'charset' => '！？。，']);
        $repeatAttack = '测试！！！！！！！！！！！！';
        $result = $repeatFilter($repeatAttack);
        $this->assertEquals('测试！！', $result);
    }
}
