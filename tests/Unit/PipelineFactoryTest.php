<?php

namespace Tests\Unit;

use InvalidArgumentException;
use Overtrue\TextGuard\Pipeline\TrimWhitespace;
use Overtrue\TextGuard\PipelineFactory;
use Tests\TestCase;

class PipelineFactoryTest extends TestCase
{
    public function test_build_pipeline_with_config()
    {
        $pipelineMap = [
            'trim_whitespace' => TrimWhitespace::class,
        ];

        $factory = new PipelineFactory($pipelineMap);
        $steps = ['trim_whitespace' => true];

        $pipeline = $factory->build($steps);

        $this->assertCount(1, $pipeline);
        $this->assertInstanceOf(TrimWhitespace::class, $pipeline['trim_whitespace']);
    }

    public function test_build_pipeline_ignores_empty_steps()
    {
        $pipelineMap = [
            'trim_whitespace' => TrimWhitespace::class,
        ];

        $factory = new PipelineFactory($pipelineMap);
        $steps = ['trim_whitespace' => false];

        $pipeline = $factory->build($steps);

        $this->assertCount(0, $pipeline);
    }

    public function test_register_new_pipeline_step()
    {
        $factory = new PipelineFactory([]);

        $factory->register('custom_step', TrimWhitespace::class);

        $this->assertContains('custom_step', $factory->getAvailableSteps());
    }

    public function test_get_available_steps()
    {
        $pipelineMap = [
            'step1' => TrimWhitespace::class,
            'step2' => TrimWhitespace::class,
        ];

        $factory = new PipelineFactory($pipelineMap);

        $steps = $factory->getAvailableSteps();

        $this->assertCount(2, $steps);
        $this->assertContains('step1', $steps);
        $this->assertContains('step2', $steps);
    }

    public function test_build_pipeline_throws_when_class_does_not_exist()
    {
        $factory = new PipelineFactory([
            'missing_step' => 'App\\TextGuard\\MissingPipeline',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $factory->build(['missing_step' => true]);
    }

    public function test_build_pipeline_throws_when_class_does_not_implement_pipeline_step()
    {
        $factory = new PipelineFactory([
            'invalid_step' => self::class,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $factory->build(['invalid_step' => true]);
    }
}
