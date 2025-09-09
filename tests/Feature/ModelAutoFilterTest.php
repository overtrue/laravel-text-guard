<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\User;

class ModelAutoFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_auto_filtering_on_save()
    {
        // Create a test model that uses TextGuardable
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio'];

            protected $table = 'users';

            public function getTextGuardFields(): array
            {
                return [
                    'name' => 'username',
                    'bio' => 'safe',
                ];
            }
        };

        // Test data with malicious content
        $maliciousData = [
            'name' => 'ＵｓｅｒＮａｍｅ１２３！！！',
            'bio' => '正常文本'.json_decode('"\u200B\u200C\u200D"').'隐藏内容',
        ];

        // Save the model - should trigger automatic filtering
        $user->fill($maliciousData);
        $user->save();

        // Verify the data was filtered
        $this->assertEquals('UserName123!!!', $user->name);
        $this->assertEquals('正常文本隐藏内容', $user->bio);

        // Verify the filtered data was saved to database
        $savedUser = $user->fresh();
        $this->assertEquals('UserName123!!!', $savedUser->name);
        $this->assertEquals('正常文本隐藏内容', $savedUser->bio);
    }

    public function test_model_auto_filtering_on_update()
    {
        // Create a test model that uses TextGuardable
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio'];

            protected $table = 'users';

            public function getTextGuardFields(): array
            {
                return [
                    'name' => 'username',
                    'bio' => 'safe',
                ];
            }
        };

        // Create initial clean data
        $user->fill([
            'name' => 'CleanName',
            'bio' => 'Clean bio text',
        ]);
        $user->save();

        // Update with malicious content
        $maliciousData = [
            'name' => '  ＵｓｅｒＮａｍｅ１２３  ',
            'bio' => '正常文本'.json_decode('"\u200B"').'隐藏内容',
        ];

        $user->update($maliciousData);

        // Verify the data was filtered
        $this->assertEquals('UserName123', $user->name);
        $this->assertEquals('正常文本隐藏内容', $user->bio);

        // Verify the filtered data was saved to database
        $savedUser = $user->fresh();
        $this->assertEquals('UserName123', $savedUser->name);
        $this->assertEquals('正常文本隐藏内容', $savedUser->bio);
    }

    public function test_model_auto_filtering_with_html_content()
    {
        // Create a test model that uses TextGuardable with rich_text preset
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'description'];

            protected $table = 'users';

            public function getTextGuardFields(): array
            {
                return [
                    'name' => 'username',
                    'description' => 'rich_text',
                ];
            }
        };

        $htmlData = [
            'name' => 'ＵｓｅｒＮａｍｅ１２３',
            'description' => '<script>alert("XSS攻击")</script><p>正常内容</p><b>粗体文本</b>',
        ];

        $user->fill($htmlData);
        $user->save();

        // Verify the data was filtered
        $this->assertEquals('UserName123', $user->name);
        $this->assertStringNotContainsString('<script>', $user->description);
        $this->assertStringContainsString('<p>正常内容</p>', $user->description);
        $this->assertStringContainsString('<b>粗体文本</b>', $user->description);
    }

    public function test_model_auto_filtering_ignores_unchanged_fields()
    {
        // Create a test model that uses TextGuardable
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio'];

            protected $table = 'users';

            public function getTextGuardFields(): array
            {
                return [
                    'name' => 'username',
                    'bio' => 'safe',
                ];
            }
        };

        // Create initial data
        $user->fill([
            'name' => 'CleanName',
            'bio' => 'Clean bio text',
        ]);
        $user->save();

        // Update only one field
        $user->name = '  ＵｓｅｒＮａｍｅ１２３  ';
        $user->save();

        // Verify only the changed field was filtered
        $this->assertEquals('UserName123', $user->name);
        $this->assertEquals('Clean bio text', $user->bio); // Should remain unchanged
    }

    public function test_model_auto_filtering_with_null_values()
    {
        // Create a test model that uses TextGuardable
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio'];

            protected $table = 'users';

            public function getTextGuardFields(): array
            {
                return ['name', 'bio'];
            }
        };

        $user->fill([
            'name' => '',
            'bio' => '',
        ]);
        $user->save();

        // Verify empty values are handled correctly
        $this->assertEquals('', $user->name);
        $this->assertEquals('', $user->bio);
    }
}
