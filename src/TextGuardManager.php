<?php

namespace Overtrue\TextGuard;

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
        $preset = $preset ?: $this->config['preset'];
        $steps = array_merge_recursive($this->config['presets'][$preset] ?? [], $overrides);

        $pipeline = $this->pipelineFactory->build($steps);

        foreach ($pipeline as $stepName => $step) {
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
        $preset = $preset ?: $this->config['preset'];
        $presetConfig = $this->getPresetConfig($preset);

        if (! $presetConfig) {
            return [
                'valid' => false,
                'errors' => ["Preset '{$preset}' not found"],
            ];
        }

        $errors = [];
        $steps = array_merge_recursive($presetConfig, $overrides);

        // Check visible ratio guard
        if (isset($steps['visible_ratio_guard']['min_ratio'])) {
            $minRatio = $steps['visible_ratio_guard']['min_ratio'];
            $visibleRatio = $this->calculateVisibleRatio($text);

            if ($visibleRatio < $minRatio) {
                $errors[] = "Visible character ratio ({$visibleRatio}) is below minimum required ({$minRatio})";
            }
        }

        // Check length limits
        if (isset($steps['truncate_length']['max'])) {
            $maxLength = $steps['truncate_length']['max'];

            if (mb_strlen($text) > $maxLength) {
                $errors[] = 'Text length ('.mb_strlen($text).") exceeds maximum allowed ({$maxLength})";
            }
        }

        // Check for control characters if enabled
        if (! empty($steps['remove_control_chars'])) {
            if ($this->hasControlChars($text)) {
                $errors[] = 'Text contains control characters';
            }
        }

        // Check for zero-width characters if enabled
        if (! empty($steps['remove_zero_width'])) {
            if ($this->hasZeroWidthChars($text)) {
                $errors[] = 'Text contains zero-width characters';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
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
     * Calculate visible character ratio
     */
    protected function calculateVisibleRatio(string $text): float
    {
        $total = mb_strlen($text);

        if ($total === 0) {
            return 1.0;
        }

        // Remove control characters (except common whitespace)
        $cleanText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Remove zero-width characters
        $cleanText = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{2060}]/u', '', $cleanText);

        $visible = mb_strlen($cleanText);

        return $visible / $total;
    }

    /**
     * Check if text has control characters
     */
    protected function hasControlChars(string $text): bool
    {
        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $text) === 1;
    }

    /**
     * Check if text has zero-width characters
     */
    protected function hasZeroWidthChars(string $text): bool
    {
        return preg_match('/[\x{200B}-\x{200D}\x{FEFF}\x{2060}]/u', $text) === 1;
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
}
