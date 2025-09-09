<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\User;

class TextGuardableListConfigTest extends TestCase
{
    public function test_list_configuration_with_default_preset()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio', 'description'];

            public function getTextGuardFields(): array
            {
                return ['name', 'bio', 'description'];
            }

            public function getTextGuardDefaultPreset(): string
            {
                return 'safe';
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->fill([
            'name' => '  Test Name  ',
            'bio' => '  Test Bio  ',
            'description' => '  Test Description  ',
        ]);

        $user->test_filter_text_guard_fields();

        // 所有字段都应该使用默认的 'safe' 预设进行过滤
        $this->assertEquals('Test Name', $user->name);
        $this->assertEquals('Test Bio', $user->bio);
        $this->assertEquals('Test Description', $user->description);
    }

    public function test_mixed_configuration()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio', 'description'];

            public function getTextGuardFields(): array
            {
                return [
                    'name',  // 使用默认预设
                    'bio' => 'safe',  // 指定预设
                    'description' => 'rich_text',  // 指定预设
                ];
            }

            public function getTextGuardDefaultPreset(): string
            {
                return 'username';
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->fill([
            'name' => 'ＵｓｅｒＮａｍｅ１２３',  // 应该使用 username 预设
            'bio' => '  Test Bio  ',  // 应该使用 safe 预设
            'description' => '<script>alert("test")</script><p>Valid content</p>',  // 应该使用 rich_text 预设
        ]);

        $user->test_filter_text_guard_fields();

        // name 使用默认的 username 预设（全角转半角）
        $this->assertEquals('UserName123', $user->name);

        // bio 使用指定的 safe 预设（去除空格）
        $this->assertEquals('Test Bio', $user->bio);

        // description 使用指定的 rich_text 预设（移除 script 标签）
        $this->assertStringNotContainsString('<script>', $user->description);
        $this->assertStringContainsString('<p>Valid content</p>', $user->description);
    }

    public function test_get_fields_from_list_configuration()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            public function getTextGuardFields(): array
            {
                return ['name', 'bio', 'description'];
            }
        };

        $fields = $user->getTextGuardFields();
        $this->assertEquals(['name', 'bio', 'description'], $fields);
    }

    public function test_empty_list_configuration()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            public function getTextGuardFields(): array
            {
                return [];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->fill(['name' => '  Test Name  ']);
        $user->test_filter_text_guard_fields();

        // 空配置不应该进行任何过滤
        $this->assertEquals('  Test Name  ', $user->name);
    }
}
