<?php

namespace Tests\Unit;

use Overtrue\TextGuard\TextGuard;
use Overtrue\TextGuard\TextGuardable;
use Tests\TestCase;
use Tests\User;

class TextGuardableGlobalDisableTest extends TestCase
{
    protected function tearDown(): void
    {
        // 确保测试后恢复全局状态
        TextGuardable::enableTextGuard();
        parent::tearDown();
    }

    public function test_global_disable_text_guard()
    {
        // 全局禁用 TextGuard
        TextGuardable::disableTextGuard();

        $this->assertTrue(TextGuardable::isTextGuardDisabled());

        // 使用具体的测试类
        $user = new class extends User
        {
            use TextGuardable;

            protected $fillable = ['name', 'bio'];

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = ['name' => 'safe', 'bio' => 'safe'];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        // 直接设置属性，避免触发 saving 事件
        $user->name = '  Test Name  ';
        $user->bio = '  Test Bio  ';

        $user->test_filter_text_guard_fields();

        // 全局禁用后应该不进行过滤
        $this->assertEquals('  Test Name  ', $user->name);
        $this->assertEquals('  Test Bio  ', $user->bio);
    }

    public function test_global_enable_text_guard()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name'];

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = ['name' => 'safe'];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        // 先全局禁用
        TextGuardable::disableTextGuard();
        $this->assertTrue(TextGuardable::isTextGuardDisabled());

        // 再全局启用
        TextGuardable::enableTextGuard();
        $this->assertFalse(TextGuardable::isTextGuardDisabled());

        $user->name = '  Test Name  ';
        $user->test_filter_text_guard_fields();

        // 全局启用后应该进行过滤
        $this->assertEquals('Test Name', $user->name);
    }

    public function test_without_global_text_guard_callback()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name', 'bio'];

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = ['name' => 'safe', 'bio' => 'safe'];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user->fill([
            'name' => '  Test Name  ',
            'bio' => '  Test Bio  ',
        ]);

        // 使用 withoutTextGuard 回调
        $result = TextGuardable::withoutTextGuard(function () use ($user) {
            $user->test_filter_text_guard_fields();

            return $user->name;
        });

        // 回调内应该不进行过滤
        $this->assertEquals('  Test Name  ', $result);
        $this->assertEquals('  Test Name  ', $user->name);
        $this->assertEquals('  Test Bio  ', $user->bio);

        // 回调外应该恢复原状态
        $this->assertFalse(TextGuardable::isTextGuardDisabled());
    }

    public function test_global_disable_overrides_instance_disable()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name'];

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = ['name' => 'safe'];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        // 全局禁用
        TextGuardable::disableTextGuard();

        $user->name = '  Test Name  ';
        $user->test_filter_text_guard_fields();

        // 全局禁用应该覆盖实例级别的启用
        $this->assertEquals('  Test Name  ', $user->name);
    }

    public function test_global_disable_handles_exceptions()
    {
        $user = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name'];

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = ['name' => 'safe'];
            }
        };

        $this->assertFalse(TextGuardable::isTextGuardDisabled());

        try {
            TextGuardable::withoutTextGuard(function () {
                $this->assertTrue(TextGuardable::isTextGuardDisabled());
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Test exception', $e->getMessage());
        }

        // 即使抛出异常，也应该恢复原状态
        $this->assertFalse(TextGuardable::isTextGuardDisabled());
    }

    public function test_multiple_models_affected_by_global_disable()
    {
        $user1 = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['name'];

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = ['name' => 'safe'];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        $user2 = new class extends User
        {
            use \Overtrue\TextGuard\TextGuardable;

            protected $fillable = ['bio'];

            public function __construct()
            {
                parent::__construct();
                $this->textGuardFields = ['bio' => 'safe'];
            }

            public function test_filter_text_guard_fields()
            {
                $this->filterTextGuardFields();
            }
        };

        // 全局禁用
        TextGuardable::disableTextGuard();

        $user1->name = '  Test Name  ';
        $user2->bio = '  Test Bio  ';

        $user1->test_filter_text_guard_fields();
        $user2->test_filter_text_guard_fields();

        // 两个模型都应该不进行过滤
        $this->assertEquals('  Test Name  ', $user1->name);
        $this->assertEquals('  Test Bio  ', $user2->bio);
    }
}
