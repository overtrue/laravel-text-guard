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

    public function test_runtime_override_replaces_scalar_config()
    {
        $config = [
            'preset' => 'test',
            'pipeline_map' => [
                'unicode_normalization' => \Overtrue\TextGuard\Pipeline\NormalizeUnicode::class,
            ],
            'presets' => [
                'test' => [
                    'unicode_normalization' => 'NFKC',
                ],
            ],
        ];

        $filter = new TextGuardManager($config);
        $result = $filter->filter('ｔｅｓｔ', 'test', ['unicode_normalization' => 'NFC']);

        $this->assertSame('ｔｅｓｔ', $result);
    }

    public function test_runtime_override_merges_nested_arrays_without_losing_defaults()
    {
        $config = [
            'preset' => 'test',
            'pipeline_map' => [
                'character_whitelist' => \Overtrue\TextGuard\Pipeline\CharacterWhitelist::class,
            ],
            'presets' => [
                'test' => [
                    'character_whitelist' => [
                        'enabled' => true,
                        'allow_emoji' => true,
                        'emoji_ranges' => [
                            'emoticons' => true,
                            'misc_symbols' => true,
                            'transport_map' => true,
                            'misc_symbols_2' => true,
                            'dingbats' => true,
                        ],
                    ],
                ],
            ],
        ];

        $filter = new TextGuardManager($config);
        $result = $filter->filter('😀🎉', 'test', [
            'character_whitelist' => [
                'emoji_ranges' => [
                    'emoticons' => false,
                ],
            ],
        ]);

        $this->assertSame('🎉', $result);
    }
}
