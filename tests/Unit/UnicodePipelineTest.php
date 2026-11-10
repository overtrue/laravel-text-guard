<?php

namespace Tests\Unit;

use InvalidArgumentException;
use Overtrue\TextGuard\Pipeline\CharacterWhitelist;
use Overtrue\TextGuard\Pipeline\RemoveBidiControls;
use Overtrue\TextGuard\Pipeline\RemoveZeroWidth;
use Overtrue\TextGuard\Pipeline\TruncateLength;
use Overtrue\TextGuard\TextGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UnicodePipelineTest extends TestCase
{
    public static function clusters(): array
    {
        return [
            'zwj' => ["\u{1F469}\u{1F3FD}\u{200D}\u{1F4BB}"],
            'family' => ["\u{1F468}\u{200D}\u{1F469}\u{200D}\u{1F467}"],
            'flag' => ["\u{1F1EF}\u{1F1F5}"],
            'keycap' => ["1\u{FE0F}\u{20E3}"],
            'variation' => ["\u{2764}\u{FE0F}"],
            'new pictograph' => ["\u{1FAE0}"],
            'tag sequence' => ["\u{1F3F4}\u{E0067}\u{E0062}\u{E0065}\u{E006E}\u{E0067}\u{E007F}"],
        ];
    }

    #[DataProvider('clusters')]
    public function test_emoji_is_kept_or_removed_as_a_sequence(string $cluster): void
    {
        $this->assertSame($cluster, (new CharacterWhitelist)($cluster));
        $this->assertSame('ab', (new CharacterWhitelist(['allow_emoji' => false]))('a'.$cluster.'b'));
        $this->assertSame($cluster, TextGuard::filter($cluster, 'nickname'));
        $this->assertSame($cluster, (new TruncateLength(['max' => 1, 'unit' => 'grapheme']))($cluster.'x'));
    }

    public function test_grapheme_truncation_preserves_combining_marks_and_empty_text(): void
    {
        $filter = new TruncateLength(['max' => 1, 'unit' => 'grapheme']);
        $this->assertSame("e\u{0301}", $filter("e\u{0301}x"));
        $this->assertSame('', $filter(''));
        $this->assertSame('e', (new TruncateLength(['max' => 1]))("e\u{0301}x"));
        $this->assertSame('', (new TruncateLength(['max' => 0, 'unit' => 'grapheme']))('abc'));
    }

    public function test_truncation_rejects_invalid_options(): void
    {
        foreach ([['max' => -1], ['max' => '2'], ['unit' => 'byte']] as $options) {
            try {
                new TruncateLength($options);
                $this->fail('Expected invalid truncation options to be rejected.');
            } catch (InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function test_joiner_policy_preserves_language_and_removes_other_zero_width_characters(): void
    {
        $text = "می\u{200C}روم a\u{200D}b";
        $filter = new RemoveZeroWidth(['preserve_joiners' => true]);
        $this->assertSame($text, $filter("\u{200B}".$text."\u{FEFF}\u{2060}"));
        $this->assertSame('میروم ab', (new RemoveZeroWidth)($text));
        $this->assertSame($text, $filter($filter($text)));
    }

    public function test_bidi_removal_is_independent_of_rtl_letters_and_joiners(): void
    {
        $controls = "\u{061C}\u{200E}\u{200F}\u{202A}\u{202B}\u{202C}\u{202D}\u{202E}\u{2066}\u{2067}\u{2068}\u{2069}";
        $text = "العربية עברית می\u{200C}روم";
        $filter = new RemoveBidiControls;
        $this->assertSame($text, $filter($controls.$text));
        $this->assertSame('', $filter(''));
        $this->assertSame($text, $filter($filter($text)));
        $this->assertSame($text, TextGuard::pipe($controls.$text, [
            'remove_bidi_controls' => true,
            'remove_zero_width' => ['preserve_joiners' => true],
            'visible_ratio_guard' => ['min_ratio' => 0.6],
        ]));
    }

    public function test_whitelist_does_not_allow_arbitrary_joiners_as_emoji(): void
    {
        $this->assertSame('ab', (new CharacterWhitelist)("a\u{200D}b"));
        $this->assertSame('ab', (new CharacterWhitelist)("a\u{202E}b"));
    }
}
