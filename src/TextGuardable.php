<?php

namespace Overtrue\TextGuard;

use Illuminate\Database\Eloquent\Model;

/**
 * TextGuardable trait provides automatic text filtering for model attributes
 *
 * Usage:
 * 1. Add the trait to your model
 * 2. Define $textGuardFields property to specify which fields to filter and their presets
 *
 * Example:
 * ```php
 * class User extends Model
 * {
 *     use TextGuardable;
 *
 *     // Method 1: Associative array (specify different presets)
 *     protected $textGuardFields = [
 *         'name' => 'username',
 *         'bio' => 'safe',
 *         'description' => 'rich_text'
 *     ];
 *
 *     // Method 2: Indexed array (use default preset)
 *     protected $textGuardFields = ['name', 'bio', 'description'];
 *     protected $textGuardDefaultPreset = 'safe';
 *
 *     // Method 3: Mixed configuration (some fields use default preset, some specify preset)
 *     protected $textGuardFields = [
 *         'name',  // use default preset
 *         'bio' => 'safe',  // specify preset
 *         'description' => 'rich_text'  // specify preset
 *     ];
 *     protected $textGuardDefaultPreset = 'username';
 * }
 * ```
 */
trait TextGuardable
{
    /**
     * The fields that should be automatically filtered and their presets
     * Format: ['field_name' => 'preset_name', ...] or ['field_name', ...]
     *
     * @var array
     */
    protected $textGuardFields = [];

    /**
     * The default preset to use when no specific preset is defined for a field
     *
     * @var string
     */
    protected $textGuardDefaultPreset = 'safe';

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
    protected function filterTextGuardFields(): void
    {
        if (empty($this->textGuardFields) || self::isTextGuardDisabled()) {
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

        // If it's an indexed array format (list), each field uses the default preset
        if (array_is_list($this->textGuardFields)) {
            foreach ($this->textGuardFields as $field) {
                $fields[$field] = $this->textGuardDefaultPreset;
            }

            return $fields;
        }

        // If it's a mixed configuration, special handling is needed
        foreach ($this->textGuardFields as $key => $value) {
            if (is_numeric($key)) {
                // This is a value in the indexed array, used as field name with default preset
                $fields[$value] = $this->textGuardDefaultPreset;
            } else {
                // This is a key-value pair in the associative array
                if (is_string($value) && in_array($value, $this->getValidPresets())) {
                    // The value is a valid preset name
                    $fields[$key] = $value;
                } else {
                    // The value is not a preset name, use default preset
                    $fields[$key] = $this->textGuardDefaultPreset;
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
     * Get all text guard fields with their presets
     */
    public function getTextGuardFields(): array
    {
        return $this->normalizeTextGuardFields();
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
        return $this->textGuardFields;
    }

    /**
     * Add a field to be automatically filtered
     *
     * @return $this
     */
    public function addTextGuardField(string $field, ?string $preset = null): self
    {
        $this->textGuardFields[$field] = $preset ?? $this->textGuardDefaultPreset;

        return $this;
    }

    /**
     * Remove a field from automatic filtering
     *
     * @return $this
     */
    public function removeTextGuardField(string $field): self
    {
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
        $GLOBALS['text_guard_disabled'] = true;
    }

    /**
     * Enable text guard filtering for all models
     */
    public static function enableTextGuard(): void
    {
        $GLOBALS['text_guard_disabled'] = false;
    }

    /**
     * Check if text guard is disabled
     */
    public static function isTextGuardDisabled(): bool
    {
        return $GLOBALS['text_guard_disabled'] ?? false;
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
