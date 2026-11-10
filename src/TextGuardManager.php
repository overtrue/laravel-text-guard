<?php

namespace Overtrue\TextGuard;

use InvalidArgumentException;

/**
 * TextGuardManager - Core service for text processing and validation
 *
 * This class serves as the main entry point for all text processing operations.
 * It coordinates between different text processing components and provides
 * a unified interface for text filtering, validation, and analysis.
 */
class TextGuardManager
{
    protected PipelineFactory $pipelineFactory;

    public function __construct(protected array $config)
    {
        $this->pipelineFactory = new PipelineFactory($this->config['pipeline_map'] ?? []);
    }

    /**
     * Filter text using specified preset and overrides
     *
     * @param  string  $text  The text to filter
     * @param  string|null  $preset  The preset configuration to use
     * @param  array  $overrides  Additional configuration overrides
     * @return string The filtered text
     */
    public function filter(string $text, ?string $preset = null, array $overrides = []): string
    {
        $preset ??= $this->config['preset'];
        $presetConfig = $this->getPresetConfig($preset);
        if ($presetConfig === null) {
            throw new InvalidArgumentException("Unknown preset [{$preset}].");
        }
        $steps = $this->mergeStepConfig($presetConfig, $overrides);

        return $this->pipe($text, $steps);
    }

    public function pipe(string $text, array $steps): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            throw new InvalidArgumentException('Text must be valid UTF-8.');
        }
        $pipeline = $this->pipelineFactory->build($steps);

        foreach ($pipeline as $step) {
            $text = $step($text);
        }

        return $text;
    }

    /**
     * Validate text against specified preset rules
     *
     * @param  string  $text  The text to validate
     * @param  string|null  $preset  The preset configuration to use for validation
     * @param  array  $overrides  Additional configuration overrides
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    public function validate(string $text, ?string $preset = null, array $overrides = []): array
    {
        $valid = $this->filter($text, $preset, $overrides) === $text;

        return [
            'valid' => $valid,
            'errors' => $valid ? [] : ['Text does not match the configured pipeline.'],
        ];
    }

    /**
     * Batch filter multiple texts
     *
     * @param  array  $texts  Array of texts to filter
     * @param  string|null  $preset  The preset configuration to use
     * @param  array  $overrides  Additional configuration overrides
     * @return array Array of filtered texts with same keys
     */
    public function batchFilter(array $texts, ?string $preset = null, array $overrides = []): array
    {
        $results = [];

        foreach ($texts as $key => $text) {
            $results[$key] = $this->filter($text, $preset, $overrides);
        }

        return $results;
    }

    /**
     * Register new pipeline step
     *
     * @param  string  $name  The name of the pipeline step
     * @param  string  $class  The class name of the pipeline step
     */
    public function registerPipelineStep(string $name, string $class): void
    {
        $this->pipelineFactory->register($name, $class);
    }

    /**
     * Get all available pipeline step names
     *
     * @return array List of available pipeline step names
     */
    public function getAvailableSteps(): array
    {
        return $this->pipelineFactory->getAvailableSteps();
    }

    /**
     * Get available presets
     *
     * @return array List of available preset names
     */
    public function getAvailablePresets(): array
    {
        return array_keys($this->config['presets'] ?? []);
    }

    /**
     * Get configuration for a specific preset
     *
     * @param  string  $preset  The preset name
     * @return array|null The preset configuration or null if not found
     */
    public function getPresetConfig(string $preset): ?array
    {
        return $this->config['presets'][$preset] ?? null;
    }

    /**
     * Merge preset steps with runtime overrides.
     *
     * Runtime overrides should replace scalar values rather than turning them into arrays.
     */
    protected function mergeStepConfig(array $presetConfig, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            $presetConfig[$key] = is_array($value) && ! array_is_list($value)
                && isset($presetConfig[$key]) && is_array($presetConfig[$key])
                ? $this->mergeStepConfig($presetConfig[$key], $value)
                : $value;
        }

        return $presetConfig;
    }
}
