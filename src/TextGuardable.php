<?php

namespace Overtrue\TextGuard;

use Illuminate\Database\Eloquent\Model;

/**
 * TextGuardable trait provides automatic text filtering for model attributes
 *
 * Usage:
 * 1. Add the trait to your model
 * 2. Override getTextGuardFields() method to specify which fields to filter and their presets
 * 3. Optionally override getTextGuardDefaultPreset() method to set the default preset
 *
 * Example:
 * ```php
 * class User extends Model
 * {
 *     use TextGuardable;
 *
 *     // Method 1: Override getTextGuardFields() method
 *     protected function getTextGuardFields(): array
 *     {
 *         return [
 *             'name' => 'username',
 *             'bio' => 'safe',
 *             'description' => 'rich_text'
 *         ];
 *     }
 *
 *     // Method 2: Use properties (still supported for backward compatibility)
 *     protected $textGuardFields = ['name', 'bio', 'description'];
 *     protected $textGuardDefaultPreset = 'safe';
 *
 *     // Method 3: Override getTextGuardDefaultPreset() method
 *     protected function getTextGuardDefaultPreset(): string
 *     {
 *         return 'username';
 *     }
 * }
 * ```
 */
trait TextGuardable
{
    /**
     * Global flag to disable text guard processing
     */
    private static bool $textGuardDisabled = false;

    // /**
    //  * The fields that should be automatically filtered and their presets
    //  * Format: ['field_name' => 'preset_name', ...] or ['field_name', ...]
    //  *
    //  * @var array
    //  */
    // protected $textGuardFields = [];

    // /**
    //  * The default preset to use when no specific preset is defined for a field
    //  *
    //  * @deprecated Use getTextGuardDefaultPreset() method instead
    //  * @var string
    //  */
    // protected $textGuardDefaultPreset = 'safe';

    /**
     * Get the text guard fields configuration
     * Override this method in your model to define which fields to filter
     */
    public function getTextGuardFields(): array
    {
        return $this->textGuardFields ?? [];
    }

    /**
     * Get the default preset for text guard fields
     * Override this method in your model to define the default preset
     */
    public function getTextGuardDefaultPreset(): string
    {
        return $this->textGuardDefaultPreset ?? 'safe';
    }

    /**
     * Boot the trait
     */
    protected static function bootTextGuardable()
    {
        static::saving(function (Model $model) {
            $model->filterTextGuardFields();
        });
    }

    /**
     * Filter the specified text guard fields
     */
    public function filterTextGuardFields(): void
    {
        $fields = $this->getTextGuardFields();
        if (empty($fields) || static::isTextGuardDisabled()) {
            return;
        }

        $fieldsToFilter = $this->normalizeTextGuardFields();

        foreach ($fieldsToFilter as $field => $preset) {
            if ($this->isDirty($field) && ! is_null($this->getAttribute($field))) {
                $originalValue = $this->getOriginal($field);
                $currentValue = $this->getAttribute($field);

                // Only filter if the value has changed or is being set for the first time
                if ($originalValue !== $currentValue) {
                    $filteredValue = TextGuard::filter((string) $currentValue, $preset);
                    $this->setAttribute($field, $filteredValue);
                }
            }
        }
    }

    /**
     * Get valid preset names from configuration
     */
    protected function getValidPresets(): array
    {
        $config = config('text-guard.presets', []);

        return array_keys($config);
    }

    /**
     * Normalize text guard fields configuration to field => preset mapping
     */
    protected function normalizeTextGuardFields(): array
    {
        $fields = [];
        $textGuardFields = $this->getTextGuardFields();
        $defaultPreset = $this->getTextGuardDefaultPreset();

        // If it's an indexed array format (list), each field uses the default preset
        if (array_is_list($textGuardFields)) {
            foreach ($textGuardFields as $field) {
                $fields[$field] = $defaultPreset;
            }

            return $fields;
        }

        // If it's a mixed configuration, special handling is needed
        foreach ($textGuardFields as $key => $value) {
            if (is_numeric($key)) {
                // This is a value in the indexed array, used as field name with default preset
                $fields[$value] = $defaultPreset;
            } else {
                // This is a key-value pair in the associative array
                if (is_string($value) && in_array($value, $this->getValidPresets())) {
                    // The value is a valid preset name
                    $fields[$key] = $value;
                } else {
                    // The value is not a preset name, use default preset
                    $fields[$key] = $defaultPreset;
                }
            }
        }

        return $fields;
    }

    /**
     * Manually filter a field value
     */
    public function filterField(string $field, ?string $preset = null): string
    {
        $preset = $preset ?? $this->textGuardDefaultPreset;
        $value = $this->getAttribute($field);

        if (is_null($value)) {
            return '';
        }

        return TextGuard::filter((string) $value, $preset);
    }

    /**
     * Get only the field names (without presets)
     */
    public function getTextGuardFieldNames(): array
    {
        return array_keys($this->normalizeTextGuardFields());
    }

    /**
     * Get the text guard fields configuration
     */
    public function getTextGuardFieldsConfig(): array
    {
        return $this->getTextGuardFields();
    }

    /**
     * Add a field to be automatically filtered
     *
     * @return $this
     */
    public function addTextGuardField(string $field, ?string $preset = null): self
    {
        if (! isset($this->textGuardFields)) {
            $this->textGuardFields = [];
        }
        $this->textGuardFields[$field] = $preset ?? $this->getTextGuardDefaultPreset();

        return $this;
    }

    /**
     * Remove a field from automatic filtering
     *
     * @return $this
     */
    public function removeTextGuardField(string $field): self
    {
        if (! isset($this->textGuardFields)) {
            return $this;
        }

        // If it's an indexed array format, directly remove the element
        if (array_is_list($this->textGuardFields)) {
            $this->textGuardFields = array_values(array_filter($this->textGuardFields, fn ($f) => $f !== $field));
        } else {
            // Handle mixed format: first check if it exists as a key
            if (isset($this->textGuardFields[$field])) {
                unset($this->textGuardFields[$field]);
            } else {
                // If it exists as a value (in the indexed array part), remove it
                $this->textGuardFields = array_filter($this->textGuardFields, fn ($value, $key) => ! (is_numeric($key) && $value === $field), ARRAY_FILTER_USE_BOTH);
            }
        }

        return $this;
    }

    /**
     * Disable text guard filtering for all models
     */
    public static function disableTextGuard(): void
    {
        self::$textGuardDisabled = true;
    }

    /**
     * Enable text guard filtering for all models
     */
    public static function enableTextGuard(): void
    {
        self::$textGuardDisabled = false;
    }

    /**
     * Check if text guard is disabled
     */
    public static function isTextGuardDisabled(): bool
    {
        return self::$textGuardDisabled;
    }

    /**
     * Execute a callback with text guard temporarily disabled
     *
     * @return mixed
     */
    public static function withoutTextGuard(callable $callback)
    {
        $wasDisabled = self::isTextGuardDisabled();
        self::disableTextGuard();

        try {
            return $callback();
        } finally {
            if ($wasDisabled) {
                self::disableTextGuard();
            } else {
                self::enableTextGuard();
            }
        }
    }
}
