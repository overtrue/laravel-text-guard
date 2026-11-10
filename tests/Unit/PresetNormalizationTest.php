<?php

namespace Tests\Unit;

use Overtrue\TextGuard\Rules\Filtered;
use Overtrue\TextGuard\TextGuard;
use Tests\TestCase;

class PresetNormalizationTest extends TestCase
{
    public function test_cleanup_does_not_leave_whitespace_that_fails_subsequent_validation(): void
    {
        foreach (['safe', 'strict', 'username', 'nickname'] as $preset) {
            $clean = TextGuard::filter("  Hello <b> </b>\u{200B}  ", $preset);
            $this->assertSame('Hello', $clean);
            $this->assertSame($clean, TextGuard::filter($clean, $preset));
            $this->assertSame(['name' => 'Hello'], validator(['name' => $clean], ['name' => [new Filtered($preset)]])->validated());
        }
    }

    public function test_whitespace_exposed_by_truncation_is_trimmed(): void
    {
        $this->assertSame('Hello', TextGuard::filter('Hello world', 'safe', ['truncate_length' => ['max' => 6]]));
    }
}
