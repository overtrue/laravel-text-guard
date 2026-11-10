<?php

namespace Tests;

use Overtrue\TextGuard\Rules\Filtered;
use Overtrue\TextGuard\Rules\Sanitized;
use Overtrue\TextGuard\TextGuard;

class FeatureTest extends TestCase
{
    public function test_text_filter_basic_functionality()
    {
        $dirtyText = '  Hello   World  '.json_decode('"\u200B"').'  ';
        $cleanText = TextGuard::filter($dirtyText);

        $this->assertEquals('Hello World', $cleanText);
    }

    public function test_username_preset()
    {
        $dirtyText = 'ＵｓｅｒＮａｍｅ１２３！！！';
        $cleanText = TextGuard::filter($dirtyText, 'username');

        $this->assertEquals('UserName123!!!', $cleanText);
    }

    public function test_filtered_rule()
    {
        $validator = validator(['nickname' => '  Test  '.json_decode('"\u200B"').'  '], [
            'nickname' => [new Filtered('safe')],
        ]);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('nickname'));
    }

    public function test_sanitized_rule()
    {
        $validator = validator(['content' => 'Normal text'], [
            'content' => [new Sanitized(0.8, 1)],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_sanitized_rule_fails_with_invisible_chars()
    {
        $validator = validator(['content' => json_decode('"\u200B\u200B\u200B\u200B\u200B"')], [
            'content' => [new Sanitized(0.8, 1)],
        ]);

        $this->assertFalse($validator->passes());
    }

    public function test_chinese_malicious_attack_scenarios()
    {
        // 测试中文零宽字符攻击
        $maliciousText = '正常文本'.json_decode('"\u200B\u200C\u200D"').'隐藏内容';
        $cleanText = TextGuard::filter($maliciousText, 'safe');
        $this->assertEquals('正常文本隐藏内容', $cleanText);

        // 测试中文全角半角混淆攻击
        $confusingText = 'Ｕｓｅｒ１２３ａｂｃ';
        $cleanText = TextGuard::filter($confusingText, 'username');
        $this->assertEquals('User123abc', $cleanText);

        // 测试中文标点符号攻击 - safe 预设不再折叠重复标点符号
        $punctuationAttack = '测试！！！，，，。。。？？？';
        $cleanText = TextGuard::filter($punctuationAttack, 'safe');
        // safe 预设移除了 collapse_repeated_marks，所以重复标点符号会保留
        $this->assertStringContainsString('测试', $cleanText);
    }

    public function test_chinese_html_injection_attack()
    {
        // 测试中文HTML注入攻击
        $htmlAttack = '<script>alert("中文XSS攻击")</script><p>正常内容</p>';
        $cleanText = TextGuard::filter($htmlAttack, 'rich_text');

        $this->assertStringNotContainsString('<script>', $cleanText);
        $this->assertStringContainsString('正常内容', $cleanText);
    }

    public function test_chinese_control_char_attack()
    {
        // 测试中文控制字符攻击
        $controlCharAttack = "正常文本\x00\x01\x02隐藏内容";
        $cleanText = TextGuard::filter($controlCharAttack, 'safe');

        $this->assertStringNotContainsString("\x00", $cleanText);
        $this->assertStringContainsString('正常文本', $cleanText);
        $this->assertStringContainsString('隐藏内容', $cleanText);
    }

    public function test_safe_preset_with_emoji()
    {
        // Test safe preset with emoji support
        $text = 'Hello World! 你好世界！😀🎉';
        $cleanText = TextGuard::filter($text, 'safe');

        $this->assertStringContainsString('Hello World!', $cleanText);
        $this->assertStringContainsString('你好世界！', $cleanText); // safe 预设不再转换全角半角，保持原样
        $this->assertStringContainsString('😀', $cleanText);
        $this->assertStringContainsString('🎉', $cleanText);
    }

    public function test_nickname_preset_emoji_support()
    {
        // Test nickname preset emoji support
        $emojiText = '测试各种 emoji: 😀😁😂🤣😃😄😅😆😉😊😋😎😍😘🥰😗😙😚☺️🙂🤗🤩🤔🤨😐😑😶🙄😏😣😥😮🤐😯😪😫😴😌😛😜😝🤤😒😓😔😕🙃🤑😲☹️🙁😖😞😟😤😢😭😦😧😨😩🤯😬😰😱🥵🥶😳🤪😵😡😠🤬😷🤒🤕🤢🤮🤧😇🤠🤡🥳🥴🥺🤥🤫🤭🧐🤓😈👿👹👺💀👻👽👾🤖💩😺😸😹😻😼😽🙀😿😾';
        $cleanText = TextGuard::filter($emojiText, 'nickname');

        // Should preserve most emoji characters (some may be filtered)
        $this->assertStringContainsString('😀', $cleanText);
        $this->assertStringContainsString('测试各种 emoji:', $cleanText);
        // Check if it contains some common emojis
        $this->assertStringContainsString('😁', $cleanText);
    }

    public function test_nickname_preset_chinese_punctuation()
    {
        // Test nickname preset Chinese punctuation support
        $chinesePunctuationText = '中文标点符号测试：。、！？：；﹑•＂…\'\'""〝〞¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）';
        $cleanText = TextGuard::filter($chinesePunctuationText, 'nickname');

        // Should preserve most Chinese punctuation (some may be converted)
        $this->assertStringContainsString('。、', $cleanText);
        $this->assertStringContainsString('\'\'""', $cleanText);
        // Check if the text contains some punctuation (may be converted)
        $this->assertNotEmpty($cleanText);
    }

    public function test_strict_preset_html_handling()
    {
        // Test strict preset HTML handling
        $htmlText = '&lt;script&gt;alert("XSS")&lt;/script&gt;<p>正常内容</p>';
        $cleanText = TextGuard::filter($htmlText, 'strict');

        // Should decode HTML entities and remove HTML tags
        $this->assertStringNotContainsString('<script>', $cleanText);
        $this->assertStringNotContainsString('<p>', $cleanText);
        $this->assertStringContainsString('正常内容', $cleanText);
    }

    public function test_strict_preset_malicious_content()
    {
        // Test strict preset malicious content handling
        $maliciousText = '正常内容<script>alert("XSS")</script>隐藏字符'.json_decode('"\u200B\u200C\u200D"').'更多内容';
        $cleanText = TextGuard::filter($maliciousText, 'strict');

        $this->assertStringNotContainsString('<script>', $cleanText);
        $this->assertStringContainsString('正常内容', $cleanText);
        $this->assertStringContainsString('更多内容', $cleanText);
        // Zero-width characters should be removed
        $this->assertStringNotContainsString(json_decode('"\u200B"'), $cleanText);
    }

    public function test_strict_preset_unicode_normalization()
    {
        // Test strict preset Unicode normalization
        $unicodeText = 'Ｈｅｌｌｏ　Ｗｏｒｌｄ！１２３';
        $cleanText = TextGuard::filter($unicodeText, 'strict');

        // Should convert full-width characters to half-width
        $this->assertEquals('Hello World!123', $cleanText);
    }

    public function test_strict_preset_no_emoji()
    {
        // Test strict preset disallows emoji
        $emojiText = 'Hello World! 你好世界！😀🎉';
        $cleanText = TextGuard::filter($emojiText, 'strict');

        // Strict mode should remove emoji
        $this->assertStringContainsString('Hello World!', $cleanText);
        $this->assertStringContainsString('你好世界!', $cleanText);
        $this->assertStringNotContainsString('😀', $cleanText);
        $this->assertStringNotContainsString('🎉', $cleanText);
    }
}
