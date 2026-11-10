<?php

namespace Overtrue\TextGuard;

use InvalidArgumentException;
use Overtrue\TextGuard\Pipeline\PipelineStep;

class PipelineFactory
{
    public function __construct(protected array $pipelineMap) {}

    public function build(array $steps): array
    {
        $pipeline = [];

        foreach ($steps as $name => $config) {
            if (! array_key_exists($name, $this->pipelineMap)) {
                throw new InvalidArgumentException("Unknown pipeline step [{$name}].");
            }
            if ($config === false) {
                continue;
            }
            $pipeline[$name] = $this->createStep($this->pipelineMap[$name], $config);
        }

        return $pipeline;
    }

    protected function createStep(string $class, mixed $config): PipelineStep
    {
        if (! class_exists($class)) {
            throw new InvalidArgumentException("Pipeline step class [{$class}] does not exist.");
        }
        if (! is_a($class, PipelineStep::class, true)) {
            throw new InvalidArgumentException("Pipeline step [{$class}] must implement ".PipelineStep::class.'.');
        }

        return match (true) {
            $config === true => new $class,
            is_string($config), is_array($config) => new $class($config),
            default => throw new InvalidArgumentException('Step configuration must be a boolean, string or array.'),
        };
    }

    public function register(string $name, string $class): void
    {
        $this->pipelineMap[$name] = $class;
    }

    public function getAvailableSteps(): array
    {
        return array_keys($this->pipelineMap);
    }
}
