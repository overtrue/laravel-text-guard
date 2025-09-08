<?php

namespace Tests\Unit;

use Overtrue\TextGuard\Pipeline\TrimWhitespace;
use Overtrue\TextGuard\TextGuardManager;
use Tests\TestCase;

class SimplifiedConfigTest extends TestCase
{
    public function test_simplified_pipeline_map_config()
    {
        $config = [
            'preset' => 'test',
            'pipeline_map' => [
                'trim_whitespace' => TrimWhitespace::class,
            ],
            'presets' => [
                'test' => [
                    'trim_whitespace' => true,
                ],
            ],
        ];

        $filter = new TextGuardManager($config);

        $result = $filter->filter('  Hello World  ', 'test');

        $this->assertEquals('Hello World', $result);
    }

    public function test_register_with_simplified_syntax()
    {
        $config = [
            'preset' => 'test',
            'pipeline_map' => [],
            'presets' => [
                'test' => [
                    'custom_trim' => true,
                ],
            ],
        ];

        $filter = new TextGuardManager($config);

        // 使用简化语法注册
        $filter->registerPipelineStep('custom_trim', TrimWhitespace::class);

        $result = $filter->filter('  Hello World  ', 'test');

        $this->assertEquals('Hello World', $result);
    }
}
