<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\User;

class TextGuardableTest extends TestCase
{
    public function test_basic_auto_filtering()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name'];

            public function getTextGuardFields(): array
            {
                return ['name'];
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
            'name' => '  Test User  '.json_decode('"\u200B"').'  ',
        ]);

        // Simulate the saving event
        $user->test_filter_text_guard_fields();

        $this->assertEquals('Test User ', $user->name);
    }

    public function test_different_presets_for_different_fields()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio', 'description'];

            public function getTextGuardFields(): array
            {
                return [
                    'name' => 'username',
                    'bio' => 'safe',
                    'description' => 'rich_text',
                ];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->fill([
            'name' => 'ＵｓｅｒＮａｍｅ１２３！！！',
            'bio' => '  Normal bio text  ',
            'description' => '<script>alert("test")</script><p>Valid content</p>',
        ]);

        $user->test_filter_text_guard_fields();

        // Username preset should convert fullwidth to halfwidth
        $this->assertEquals('UserName123!!!', $user->name);

        // Safe preset should trim whitespace
        $this->assertEquals('Normal bio text', $user->bio);

        // Rich text preset should strip script tags but keep valid HTML
        $this->assertStringNotContainsString('<script>', $user->description);
        $this->assertStringContainsString('<p>Valid content</p>', $user->description);
    }

    public function test_manual_field_filtering()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name'];

            public function getTextGuardFields(): array
            {
                return ['name' => 'safe'];
            }
        };

        $user->name = '  Test Name  ';

        $filtered = $user->filterField('name', 'safe');
        $this->assertEquals('Test Name', $filtered);

        // Original value should not be changed
        $this->assertEquals('  Test Name  ', $user->name);
    }

    public function test_only_filters_dirty_fields()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio'];

            public function getTextGuardFields(): array
            {
                return ['name' => 'safe', 'bio' => 'safe'];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        // Set initial values
        $user->name = 'Clean Name';
        $user->bio = 'Clean Bio';

        // Mark as not dirty (simulate loaded from database)
        $user->syncOriginal();

        // Change only name
        $user->name = '  Dirty Name  ';

        $user->test_filter_text_guard_fields();

        // Only name should be filtered
        $this->assertEquals('Dirty Name', $user->name);
        $this->assertEquals('Clean Bio', $user->bio);
    }

    public function test_handles_null_values()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name'];

            public function getTextGuardFields(): array
            {
                return ['name' => 'safe'];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->name = null;

        $user->test_filter_text_guard_fields();

        // Null values should remain null
        $this->assertNull($user->name);
    }

    public function test_handles_empty_strings()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name'];

            public function getTextGuardFields(): array
            {
                return ['name'];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->name = '';

        $user->test_filter_text_guard_fields();

        // Empty strings should remain empty
        $this->assertEquals('', $user->name);
    }

    public function test_chinese_text_filtering()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio'];

            public function getTextGuardFields(): array
            {
                return [
                    'name' => 'username',
                    'bio' => 'safe',
                ];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->fill([
            'name' => 'ＵｓｅｒＮａｍｅ１２３',
            'bio' => '正常文本'.json_decode('"\u200B\u200C\u200D"').'隐藏内容',
        ]);

        $user->test_filter_text_guard_fields();

        // Username preset should convert fullwidth to halfwidth
        $this->assertEquals('UserName123', $user->name);

        // Safe preset should remove zero-width characters
        $this->assertEquals('正常文本隐藏内容', $user->bio);
    }

    public function test_property_based_configuration()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio'];

            protected $textGuardFields = [
                'name' => 'username',
                'bio' => 'safe',
            ];

            protected $textGuardDefaultPreset = 'safe';

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->fill([
            'name' => 'ＵｓｅｒＮａｍｅ１２３',
            'bio' => '  Test Bio  ',
        ]);

        $user->test_filter_text_guard_fields();

        // Username preset should convert fullwidth to halfwidth
        $this->assertEquals('UserName123', $user->name);

        // Safe preset should trim whitespace
        $this->assertEquals('Test Bio', $user->bio);
    }

    public function test_property_based_list_configuration()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio', 'description'];

            protected $textGuardFields = ['name', 'bio', 'description'];

            protected $textGuardDefaultPreset = 'safe';

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

        // All fields should use the default 'safe' preset
        $this->assertEquals('Test Name', $user->name);
        $this->assertEquals('Test Bio', $user->bio);
        $this->assertEquals('Test Description', $user->description);
    }

    public function test_property_based_mixed_configuration()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio', 'description'];

            protected $textGuardFields = [
                'name',  // use default preset
                'bio' => 'safe',  // specify preset
                'description' => 'rich_text',  // specify preset
            ];

            protected $textGuardDefaultPreset = 'username';

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->fill([
            'name' => 'ＵｓｅｒＮａｍｅ１２３',  // should use 'username' preset
            'bio' => '  Test Bio  ',  // should use 'safe' preset
            'description' => '<script>alert("test")</script><p>Valid content</p>',  // should use 'rich_text' preset
        ]);

        $user->test_filter_text_guard_fields();

        // Name should use default 'username' preset (convert fullwidth to halfwidth)
        $this->assertEquals('UserName123', $user->name);

        // Bio should use 'safe' preset (trim whitespace)
        $this->assertEquals('Test Bio', $user->bio);

        // Description should use 'rich_text' preset (strip script tags but keep valid HTML)
        $this->assertStringNotContainsString('<script>', $user->description);
        $this->assertStringContainsString('<p>Valid content</p>', $user->description);
    }
}
