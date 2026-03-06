<?php

namespace Overtrue\TextGuard;

use InvalidArgumentException;
use Overtrue\TextGuard\Pipeline\PipelineStep;

class PipelineFactory
{
    public function __construct(protected array $pipelineMap) {}

    /**
     * Build processing pipeline
     */
    public function build(array $steps): array
    {
        $pipeline = [];

        foreach ($this->pipelineMap as $stepName => $class) {
            if (! empty($steps[$stepName])) {
                $stepConfig = $steps[$stepName];
                $pipeline[$stepName] = $this->createStep($class, $stepConfig);
            }
        }

        return $pipeline;
    }

    /**
     * Create pipeline step instance
     */
    protected function createStep(string $class, mixed $config): PipelineStep
    {
        if (! class_exists($class)) {
            throw new InvalidArgumentException("Pipeline step class [{$class}] does not exist.");
        }

        if (! is_a($class, PipelineStep::class, true)) {
            throw new InvalidArgumentException("Pipeline step [{$class}] must implement ".PipelineStep::class.'.');
        }

        // Normalize configuration
        $normalizedConfig = $this->normalizeConfig($config);

        // Create instance based on configuration type
        return match (true) {
            empty($normalizedConfig) => new $class,
            isset($normalizedConfig['value']) => new $class($normalizedConfig['value']),
            default => new $class($normalizedConfig),
        };
    }

    /**
     * Normalize configuration to array format
     */
    protected function normalizeConfig(mixed $config): array
    {
        if (is_array($config)) {
            return $config;
        }

        if (is_string($config)) {
            return ['value' => $config];
        }

        if (is_bool($config) && $config) {
            return ['enabled' => true];
        }

        return [];
    }

    /**
     * Register new pipeline step
     */
    public function register(string $name, string $class): void
    {
        $this->pipelineMap[$name] = $class;
    }

    /**
     * Get all available pipeline step names
     */
    public function getAvailableSteps(): array
    {
        return array_keys($this->pipelineMap);
    }
}
