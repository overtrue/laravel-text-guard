<?php

namespace Overtrue\TextGuard;

final class TextGuardState
{
    private static bool $disabled = false;

    public static function disable(): void
    {
        self::$disabled = true;
    }

    public static function disableTextGuard(): void
    {
        self::disable();
    }

    public static function enable(): void
    {
        self::$disabled = false;
    }

    public static function enableTextGuard(): void
    {
        self::enable();
    }

    public static function isDisabled(): bool
    {
        return self::$disabled;
    }

    public static function isTextGuardDisabled(): bool
    {
        return self::isDisabled();
    }

    public static function without(callable $callback): mixed
    {
        $wasDisabled = self::isDisabled();
        self::disable();

        try {
            return $callback();
        } finally {
            if ($wasDisabled) {
                self::disable();
            } else {
                self::enable();
            }
        }
    }

    public static function withoutTextGuard(callable $callback): mixed
    {
        return self::without($callback);
    }
}
