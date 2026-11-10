<?php

namespace Tests\Unit;

use InvalidArgumentException;
use Overtrue\TextGuard\Pipeline\HtmlDecode;
use Overtrue\TextGuard\Pipeline\PipelineStep;
use Overtrue\TextGuard\Pipeline\StripHtml;
use Overtrue\TextGuard\Pipeline\TrimWhitespace;
use Overtrue\TextGuard\Pipeline\WhitelistHtml;
use Overtrue\TextGuard\PipelineFactory;
use Overtrue\TextGuard\TextGuardable;
use Overtrue\TextGuard\TextGuardManager;
use Tests\TestCase;
use Tests\User;

class CompositionTest extends TestCase
{
    public function test_steps_follow_preset_order_not_registry_order(): void
    {
        $manager = new TextGuardManager([
            'preset' => 'plain',
            'pipeline_map' => ['strip_html' => StripHtml::class, 'html_decode' => HtmlDecode::class],
            'presets' => ['plain' => ['html_decode' => true, 'strip_html' => true]],
        ]);
        $this->assertSame('Hello', $manager->filter('&lt;b&gt;Hello&lt;/b&gt;'));
    }

    public function test_empty_options_enable_defaults_and_false_disables(): void
    {
        $factory = new PipelineFactory(['trim' => TrimWhitespace::class]);
        $this->assertSame('text', $factory->build(['trim' => []])['trim'](' text '));
        $this->assertSame([], $factory->build(['trim' => false]));
    }

    public function test_unknown_enabled_step_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PipelineFactory([]))->build(['typo' => true]);
    }

    public function test_unknown_preset_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TextGuardManager(['preset' => 'missing', 'presets' => []]))->filter('text');
    }

    public function test_empty_preset_is_an_explicit_identity_pipeline(): void
    {
        $manager = new TextGuardManager(['preset' => 'empty', 'presets' => ['empty' => []]]);
        $this->assertSame(' text ', $manager->filter(' text '));
        $this->assertSame(['valid' => true, 'errors' => []], $manager->validate(' text '));
    }

    public function test_validation_uses_the_same_custom_pipeline_without_mutation(): void
    {
        $manager = new TextGuardManager([
            'preset' => 'plain', 'pipeline_map' => ['strip' => StripHtml::class],
            'presets' => ['plain' => ['strip' => true]],
        ]);
        $this->assertFalse($manager->validate('<b>Hello</b>')['valid']);
        $this->assertTrue($manager->validate('Hello')['valid']);
        $this->assertTrue($manager->validate('<b>Hello</b>', overrides: ['strip' => false])['valid']);
    }

    public function test_list_overrides_replace_instead_of_retaining_allowed_items(): void
    {
        $manager = new TextGuardManager([
            'preset' => 'html', 'pipeline_map' => ['html' => WhitelistHtml::class],
            'presets' => ['html' => ['html' => ['tags' => ['p', 'b', 'i'], 'attrs' => ['title']]]],
        ]);
        $this->assertSame('<p>x</p>yz', $manager->filter('<p>x</p><b>y</b><i>z</i>', overrides: ['html' => ['tags' => ['p']]]));
        $this->assertSame('xyz', $manager->filter('<p>x</p><b>y</b><i>z</i>', overrides: ['html' => ['tags' => []]]));
    }

    public function test_invalid_utf8_is_rejected_before_pipeline_execution(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TextGuardManager(['preset' => 'plain', 'presets' => ['plain' => []]]))->filter("\xFF");
    }

    public function test_array_options_with_value_key_are_not_unwrapped(): void
    {
        $manager = new TextGuardManager(['pipeline_map' => []]);
        $manager->registerPipelineStep('replace', LiteralReplacement::class);
        $this->assertSame('new other', $manager->pipe('value other', ['replace' => ['value' => 'new']]));
    }

    public function test_custom_aliases_can_repeat_a_step_in_order(): void
    {
        $manager = new TextGuardManager(['pipeline_map' => []]);
        $manager->registerPipelineStep('first', LiteralReplacement::class);
        $manager->registerPipelineStep('second', LiteralReplacement::class);
        $this->assertSame('c', $manager->pipe('a', ['first' => ['a' => 'b'], 'second' => ['b' => 'c']]));
        $this->assertSame('b', $manager->pipe('a', ['second' => ['b' => 'c'], 'first' => ['a' => 'b']]));
    }

    public function test_invalid_step_types_are_rejected(): void
    {
        foreach ([null, 0, 1] as $config) {
            try {
                (new PipelineFactory(['trim' => TrimWhitespace::class]))->build(['trim' => $config]);
                $this->fail('Expected invalid step configuration to be rejected.');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('configuration', $exception->getMessage());
            }
        }
    }

    public function test_override_preserves_existing_order_and_appends_new_steps(): void
    {
        $manager = new TextGuardManager([
            'preset' => 'custom',
            'pipeline_map' => ['first' => LiteralReplacement::class, 'second' => LiteralReplacement::class, 'third' => LiteralReplacement::class],
            'presets' => ['custom' => ['first' => ['a' => 'b'], 'second' => ['b' => 'c']]],
        ]);
        $this->assertSame('e', $manager->filter('a', overrides: ['second' => ['b' => 'd'], 'third' => ['d' => 'e']]));
        $this->assertSame('c', $manager->filter('a'));
    }

    public function test_unknown_model_preset_cannot_silently_persist_unfiltered_input(): void
    {
        $user = new class extends User
        {
            use TextGuardable;

            protected $table = 'users';

            protected $textGuardFields = ['name' => 'typo'];
        };
        $user->name = '<b>text</b>';
        try {
            $user->save();
            $this->fail('Expected an unknown model preset to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('typo', $exception->getMessage());
        }
        $this->assertSame('<b>text</b>', $user->name);
        $this->assertDatabaseCount('users', 0);
    }
}

class LiteralReplacement implements PipelineStep
{
    public function __construct(private array $options = []) {}

    public function __invoke(string $text): string
    {
        return strtr($text, $this->options);
    }
}
