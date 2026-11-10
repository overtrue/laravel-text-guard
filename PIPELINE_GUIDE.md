# Pipeline reference

[中文](PIPELINE_GUIDE.zh-CN.md) · [Usage and migration](README.md)

## Composition contract

`TextGuard::pipe($text, $steps)` executes the supplied steps in insertion order. `filter($text, $preset, $overrides)` resolves a preset and delegates to the same executor. `validate()` checks whether that pipeline leaves the text unchanged. All custom steps participate automatically.

Configuration is either `true` (default constructor), `false` (disabled), a string (single constructor argument), or an array (options passed intact, including `[]`). Unknown names and invalid top-level configuration types throw. Register a class under multiple names to repeat it. Registrations describe availability, not execution order.

Overrides retain existing step positions and append new steps. Associative option dictionaries merge recursively; lists replace atomically. In particular, a shorter HTML allowlist must not retain trailing entries from the previous list. Use a complete `pipe()` configuration when order changes.

## Built-in steps

| Step | Options / defaults | Responsibility |
| --- | --- | --- |
| `trim_whitespace` | none | Remove leading/trailing Unicode whitespace |
| `collapse_spaces` | none | Collapse consecutive whitespace |
| `remove_control_chars` | none | Remove ASCII controls except tab, LF and CR |
| `remove_zero_width` | `preserve_joiners: false` | Remove U+200B/U+200C/U+200D/U+FEFF/U+2060; optionally preserve U+200C/U+200D |
| `remove_bidi_controls` | none | Remove U+061C/U+200E/U+200F/U+202A–U+202E/U+2066–U+2069 |
| `unicode_normalization` | string `NFKC` | NFC/NFD/NFKC/NFKD normalization |
| `fullwidth_to_halfwidth` | `ascii: true, digits: true, latin: true, punct: false` | Existing mb_convert_kana flags: a/n/r/s respectively; flags overlap, and s converts fullwidth spaces |
| `normalize_punctuations` | string `zh` | Map punctuation to `zh` or `en` conventions |
| `html_decode` | `enabled: true, flags: ENT_QUOTES \| ENT_HTML5, encoding: UTF-8` | Decode HTML entities once |
| `strip_html` | none | Strip HTML tags, leaving text content |
| `whitelist_html` | see below | DOM-based tag/attribute/protocol allowlists |
| `character_whitelist` | see below | Keep permitted text and optionally complete emoji sequences |
| `collapse_repeated_marks` | `max_repeat: 2, charset: '!?。，、…—'` | Limit runs of selected punctuation |
| `visible_ratio_guard` | `min_ratio: 0.6` | Return empty string if the current text fails a code-point visibility heuristic |
| `truncate_length` | `max: 5000, unit: codepoint` | Limit code points or grapheme clusters |

Use `false` to disable a step through configuration. Nullable direct constructors for Unicode/punctuation normalization remain available, but `null` is not a pipeline configuration value.

## Unicode operations

~~~php
TextGuard::pipe($text, [
    'unicode_normalization' => 'NFC',
    'remove_bidi_controls' => true,
    'remove_zero_width' => ['preserve_joiners' => true],
    'truncate_length' => ['max' => 40, 'unit' => 'grapheme'],
]);
~~~

These are separate decisions. Bidi controls are not all RTL text. Joiners carry meaning in Persian/Indic text and emoji; preserving them does not certify their placement. A whitelist applied later may still remove them.

`truncate_length` accepts a non-negative integer maximum and `codepoint` or `grapheme`. Zero returns empty text. Graphemes use the already-required intl extension; no new dependency is added. ICU determines segmentation. The input must be valid UTF-8. Neither mode understands HTML structure or display-column width.

Visibility ratios count code points, including non-visible joining characters. They are not a measure of visual authenticity and can reject legitimate emoji or writing systems. Do not add them automatically to every pipeline.

## Character whitelist

Options:

- `enabled: true`
- `allow_emoji: true`
- `allow_chinese_punctuation: true`
- `allow_english_punctuation: true`

The ordinary character whitelist retains word characters, Han and whitespace plus selected punctuation. It is not a configurable identity/filename policy. Emoji matching handles pictographic sequences (including ZWJ/modifiers), regional-indicator pairs, keycaps and tag sequences as units. It uses Unicode properties rather than historical hand-maintained blocks, but is not an RGI validator. Unicode property availability follows PCRE; grapheme segmentation follows runtime Unicode data.

`emoji_ranges` is removed and throws if supplied. To restrict particular symbols, register a separate application-owned step; do not assume Unicode blocks correspond to semantic categories.

~~~php
TextGuard::pipe($text, [
    'remove_zero_width' => ['preserve_joiners' => true],
    'character_whitelist' => ['allow_emoji' => true],
    'truncate_length' => ['max' => 30, 'unit' => 'grapheme'],
]);
~~~

## HTML options and ordering

`whitelist_html` defaults:

~~~php
[
    'tags' => ['p', 'b', 'i', 'u', 'a', 'ul', 'ol', 'li', 'code', 'pre', 'br', 'blockquote', 'h1', 'h2', 'h3'],
    'attrs' => ['href', 'title', 'rel'],
    'protocols' => ['http', 'https', 'mailto'],
]
~~~

Each list replaces its default in full; `[]` clears it. ext-dom is required; missing support throws instead of falling back to a weaker implementation. Keep allowlists trusted and narrow. This is not a replacement for a dedicated rich-text sanitizer with a broader threat model.

For plain text with encoded markup:

~~~php
TextGuard::pipe('&lt;b&gt;Hello&lt;/b&gt;', [
    'html_decode' => true,
    'strip_html' => true,
]); // Hello
~~~

Reversing these steps produces `<b>Hello</b>` by design. This illustrates why order belongs to the caller, not the registry. Entity decoding is one pass, not a promise of recursive canonicalization. `strip_html` leaves script text, and neither operation replaces contextual output escaping.

Do not run plain string truncation on sanitized HTML and assume the result remains structurally valid. Validate input size or use a DOM-aware step before final sanitization. Rendering to Markdown or an HTML attribute has different requirements from rendering plain text in an HTML text node.

## Custom steps

Implement `PipelineStep::__invoke(string $text): string`, register with `registerPipelineStep($name, $class)`, then reference its name in a preset or `pipe()`. Arrays are passed to the constructor unchanged: a key named `value` has no special meaning. Steps should have no request, storage or rendering side effects.

A custom detector can be composed with a custom replacer without adding phone/email/domain policy to the library. Test both transformed input and ordinary text that must remain unchanged, and test the final rendering separately from string transformations.
