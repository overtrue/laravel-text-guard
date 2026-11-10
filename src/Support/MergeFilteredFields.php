<?php

namespace Overtrue\TextGuard\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Overtrue\TextGuard\TextGuard;

/**
 * Build request merge payloads for field => preset maps.
 *
 * Patterns use Laravel Str::is() against dotted keys from Arr::dot().
 * Wildcards such as items.*.name match nested array paths. Keys that do not
 * exist in the source are ignored.
 */
class MergeFilteredFields
{
    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, string>  $fieldPresets  attribute pattern => preset name
     * @return array<string, mixed> nested array suitable for Request::merge()
     */
    public static function buildMergePayload(array $source, array $fieldPresets): array
    {
        if ($fieldPresets === []) {
            return [];
        }

        $dotted = Arr::dot($source);
        $payload = [];

        foreach ($fieldPresets as $pattern => $preset) {
            foreach ($dotted as $key => $value) {
                if (! Str::is($pattern, $key)) {
                    continue;
                }

                if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8')) {
                    continue;
                }

                Arr::set($payload, $key, TextGuard::filter($value, $preset));
            }
        }

        return $payload;
    }
}
