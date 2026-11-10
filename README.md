# Laravel TextGuard

Composable string filtering and explicit validation for Laravel.

[中文文档](README.zh-CN.md) · [Pipeline reference](PIPELINE_GUIDE.md)

## Install

Requires PHP 8.3+, Laravel 12/13, mbstring and intl. HTML whitelist filtering additionally requires ext-dom.

~~~bash
composer require overtrue/laravel-text-guard
php artisan vendor:publish --tag=text-guard-config
~~~

## Compose operations, not business policies

Every step is a string-to-string operation. Use a preset for reuse, or compose an ordered pipeline directly:

~~~php
use Overtrue\TextGuard\TextGuard;

$clean = TextGuard::pipe($input, [
    'html_decode' => true,
    'strip_html' => true,
    'unicode_normalization' => 'NFC',
    'remove_bidi_controls' => true,
    'remove_zero_width' => ['preserve_joiners' => true],
    'collapse_spaces' => true,
    'trim_whitespace' => true,
    'truncate_length' => ['max' => 80, 'unit' => 'grapheme'],
]);

$clean = TextGuard::filter($input, 'safe');
$clean = TextGuard::filter($input, 'safe', ['truncate_length' => ['max' => 100]]);
$cleanByKey = TextGuard::batchFilter(['title' => $input], 'safe');
~~~

Steps execute in the supplied order, not registry order. A preset is just a named ordered configuration. There are no built-in phone thresholds, domain suffix lists, email-specific replacements or filename policies.

- `true` enables defaults; `false` disables a step. A string or array (including `[]`) is passed directly to the constructor.
- Overrides recursively merge associative options. Lists replace whole lists, including `[]` to clear an allowlist.
- Existing step positions are retained by overrides; new steps append. Use `pipe()` or a new preset to change order. Register the same class under different names to run it more than once.
- Unknown presets/steps and invalid configuration types throw `InvalidArgumentException`. An explicitly configured empty preset or `pipe($input, [])` is an identity operation.
- `filter()` and `pipe()` reject invalid UTF-8. Configuration is trusted application code, not user input.

For non-Facade usage, instantiate `TextGuardManager` with the configuration array. Its string operations do not read the HTTP request.

## Choose filtering or rejection explicitly

Filtering returns a new string without mutating input or the HTTP request. Validation checks whether the configured pipeline leaves the string unchanged:

~~~php
$result = TextGuard::validate($input, 'safe'); // ['valid' => bool, 'errors' => array]

use Overtrue\TextGuard\Rules\Filtered;

$validator = validator($data, [
    'items.*.name' => ['required', 'string', new Filtered('safe')],
]);
$accepted = $validator->validated();
~~~

`Filtered` rejects values that need filtering and never writes to `request()`. It supports standalone validators and nested/wildcard fields. `mustNotBeEmpty: false` permits an unchanged empty string, not whitespace that would be removed. The optional `overrides` argument has the same semantics as `filter()`.

When cleanup is intended, do it explicitly before validation:

~~~php
protected function prepareForValidation(): void
{
    $name = $this->input('name');
    if (is_string($name) && mb_check_encoding($name, 'UTF-8')) {
        $this->merge(['name' => TextGuard::filter($name, 'safe')]);
    }
}

public function rules(): array
{
    return ['name' => ['required', 'string', new Filtered('safe')]];
}
~~~

Use Laravel's `required`, `nullable` and length rules for business requirements. Invalid types/UTF-8 fail the `Filtered` rule; configuration errors still throw. `Sanitized(minVisibleRatio: 0.6, minLength: 1)` remains a **visibility heuristic**, not a guarantee that text is filtered or safe to render.

## Independent Unicode policies

- `remove_zero_width` removes U+200B/U+200C/U+200D/U+FEFF/U+2060 by default. `preserve_joiners: true` keeps ZWNJ/ZWJ for writing systems and emoji; it does not validate their placement.
- `remove_bidi_controls` removes Unicode Bidi_Control characters while preserving ordinary RTL letters and joiners. It is opt-in because directional formatting can be intentional.
- `truncate_length` defaults to code points. Choose `unit: grapheme` for user-perceived characters, combining marks and emoji sequences. `max: 0` returns an empty string; invalid maxima/units throw.
- `character_whitelist` recognizes pictographic ZWJ sequences, modifiers, regional-indicator pairs, keycaps and emoji tag sequences as units. It is not an RGI emoji validator. Recognition depends on runtime Unicode tables; grapheme truncation uses ICU.

The `nickname` example preset preserves joiners, uses grapheme limits and omits the code-point visibility heuristic. A later step can still undo an earlier decision: preserving joiners does not override a character whitelist. Choose operations appropriate to the text's language and purpose.

## Reusable presets and extensions

Built-ins: `safe`, `strict`, `username`, `nickname`, `rich_text`. Exact definitions live in `config/text-guard.php`; names are convenience labels, not security guarantees.

~~~php
use Overtrue\TextGuard\Pipeline\PipelineStep;

class ReplaceText implements PipelineStep
{
    public function __construct(private array $replacements = []) {}

    public function __invoke(string $text): string
    {
        return strtr($text, $this->replacements);
    }
}

TextGuard::registerPipelineStep('replace_text', ReplaceText::class);
$display = TextGuard::pipe($input, [
    'strip_html' => true,
    'replace_text' => ['[' => '(', ']' => ')'],
]);
~~~

This example is literal replacement, not HTML/Markdown escaping. Domain-specific detection and masking belong in separately composed custom steps. Replacement choices should not force normalization, storage or rendering policies.

Introspection: `getAvailableSteps()`, `getAvailablePresets()`, `getPresetConfig($name)`.

## Eloquent integration is opt-in

~~~php
use Illuminate\Database\Eloquent\Model;
use Overtrue\TextGuard\TextGuardable;

class User extends Model
{
    use TextGuardable;

    protected $textGuardFields = ['name' => 'nickname', 'bio' => 'safe'];
}
~~~

Only dirty, non-null configured attributes are filtered on saving. Lists of field names use `getTextGuardDefaultPreset()`; associative entries specify presets. Mixed lists are supported. Unknown preset names throw instead of falling back silently.

Override `getTextGuardFields()` / `getTextGuardDefaultPreset()` instead of properties if needed. `filterField($field, $preset)` returns a value without assigning it; `getTextGuardFieldNames()` returns normalized names. `disableTextGuard()` / `enableTextGuard()` affect all models in the process. `withoutTextGuard($callback)` restores the previous state on success or exception. Do not use process-wide toggles across concurrent requests.

Do not persist display-only masking unless overwriting the original value is intended.

## Rendering and composition boundaries

`strip_html` is tag removal, not contextual output encoding. `html_decode` performs one pass. Put decoding before tag removal when that is the desired contract; do not silently decode repeatedly until stable. Arbitrary pipelines are not guaranteed to be idempotent.

Escape at the HTML/attribute/JavaScript/Markdown output boundary. HTML whitelist filtering requires ext-dom rather than degrading to an attribute-preserving tag stripper. Allowlists replace defaults. Do not decode or normalize HTML after sanitization without reconsidering the final output. String truncation is not DOM-aware; for rich text, reject oversized input or use DOM-aware truncation before final sanitization.

## Upgrading: breaking changes

This redesign requires a breaking release:

1. Execution follows preset order. Audit configurations relying on `pipeline_map` order; decoding before tag removal now works as documented.
2. `Filtered` no longer cleans global request data: it rejects dirty input. Move intended cleanup before validation.
3. `validate()` checks the complete pipeline, including custom steps, rather than a hard-coded subset. Empty configured presets are valid.
4. Unknown names, invalid step types and invalid UTF-8 fail explicitly. Use `false`, not `null`/`0`, to disable a step; `[]` enables defaults.
5. Numeric option lists replace rather than merge. Review published allowlists. Model preset typos no longer fall back to the default.
6. Remove `emoji_ranges` from published config. `allow_emoji` now handles Unicode sequences; obsolete range options throw. Use a custom step for application-specific symbol restrictions.
7. `nickname` now measures graphemes, retains joiners and omits the visibility heuristic. Other presets retain their character/length policies. Built-in presets now finish with trimming, so tag removal, zero-width removal or truncation cannot leave exposed leading/trailing whitespace.
8. HTML whitelist filtering requires ext-dom instead of silently degrading.

## Development

~~~bash
composer install
composer fix
composer test
~~~

MIT License.
