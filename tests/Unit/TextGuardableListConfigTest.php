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

            public function __construct()
            {
                parent::__construct();
                // 使用索引数组格式（list 写法）
                $this->textGuardFields = ['name', 'bio', 'description'];
                $this->textGuardDefaultPreset = 'safe';
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

            public function __construct()
            {
                parent::__construct();
                // 混合配置：部分字段使用默认预设，部分字段指定预设
                $this->textGuardFields = [
                    'name',  // 使用默认预设
                    'bio' => 'safe',  // 指定预设
                    'description' => 'rich_text',  // 指定预设
                ];
                $this->textGuardDefaultPreset = 'username';
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

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = ['name', 'bio', 'description'];
            }
        };

        $fields = $user->getTextGuardFields();
        $this->assertEquals([
            'name' => 'safe',
            'bio' => 'safe',
            'description' => 'safe',
        ], $fields);
    }

    public function test_dynamic_field_management_with_list_configuration()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            public function __construct()
            {
                parent::__construct();
                // 初始化为索引数组格式
                $this->textGuardFields = ['name', 'bio'];
                $this->textGuardDefaultPreset = 'safe';
            }
        };

        // 添加字段（应该转换为关联数组格式）
        $user->addTextGuardField('description', 'rich_text');

        $this->assertEquals([
            'name' => 'safe',
            'bio' => 'safe',
            'description' => 'rich_text',
        ], $user->getTextGuardFields());
        $this->assertEquals([
            0 => 'name',
            1 => 'bio',
            'description' => 'rich_text',
        ], $user->getTextGuardFieldsConfig());

        // 移除字段
        $user->removeTextGuardField('bio');

        $this->assertEquals([
            'name' => 'safe',
            'description' => 'rich_text',
        ], $user->getTextGuardFields());
        $this->assertEquals([
            0 => 'name',
            'description' => 'rich_text',
        ], $user->getTextGuardFieldsConfig());
    }

    public function test_remove_field_from_list_configuration()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = ['name', 'bio', 'description'];
            }
        };

        // 从索引数组中移除字段
        $user->removeTextGuardField('bio');

        $this->assertEquals([
            'name' => 'safe',
            'description' => 'safe',
        ], $user->getTextGuardFields());
        $this->assertEquals(['name', 'description'], $user->getTextGuardFieldsConfig());
    }

    public function test_empty_list_configuration()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = [];
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
