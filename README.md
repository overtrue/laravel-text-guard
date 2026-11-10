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

Nothing in this package rewrites the HTTP request unless you opt in. Pick one path:

| Goal | Approach |
|------|----------|
| Filter a string in application code | `TextGuard::filter()` / `pipe()` |
| Replace repeated `prepareForValidation()` boilerplate | `$request->mergeFiltered([...])` on `Request` / `FormRequest` |
| Filter during validation, keep `validated()` clean | `Sanitize` rule (validator data only) |
| Reject input that still needs filtering | `Filtered` rule (unchanged-input check) |
| Filter specific route inputs before the controller | Opt-in `text-guard.merge` middleware |

### `mergeFiltered()` on requests

After the service provider boots, every `Request` exposes `mergeFiltered(array $fieldPresets)`. Each key is an attribute pattern; each value is a preset name. Matching string fields with valid UTF-8 are filtered and merged back onto **that request instance** only.

~~~php
$request->mergeFiltered([
    'name' => 'safe',
    'bio' => 'nickname',
    'items.*.title' => 'safe', // wildcard patterns use Str::is() on dotted keys
]);
~~~

Non-string and invalid UTF-8 values are left unchanged (no surprise exceptions). Unknown presets and invalid configuration still throw, same as `filter()`.

Equivalent manual cleanup in a `FormRequest`:

~~~php
protected function prepareForValidation(): void
{
    $this->mergeFiltered(['name' => 'safe', 'bio' => 'nickname']);
}

public function rules(): array
{
    return ['name' => ['required', 'string', new Filtered('safe')]];
}
~~~

### `Sanitize` vs `Filtered`

`Sanitize` runs the configured pipeline and writes the cleaned string into the **validator data** so `validated()` returns filtered values. It never calls `request()->merge()`.

~~~php
use Overtrue\TextGuard\Rules\Sanitize;
use Overtrue\TextGuard\Rules\Filtered;

$validator = validator($data, [
    'nickname' => ['required', 'string', new Sanitize('username'), new Filtered('username')],
]);
$clean = $validator->validated(); // already filtered
~~~

Use `Filtered` alone when clients must submit already-clean text. Pair `Sanitize` with `Filtered` when you want automatic cleanup plus an unchanged-input guarantee after sanitization.

### Opt-in route middleware

The package registers the alias `text-guard.merge` only. **It is not global middleware.** Attach it to routes that should clean declared fields:

~~~php
Route::post('/profile', ...)
    ->middleware('text-guard.merge:name:safe,bio:nickname');
~~~

Parameters use `field:preset` syntax. Only listed fields are touched.

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
2. `Filtered` no longer cleans global request data: it rejects dirty input. Use `mergeFiltered()`, `Sanitize`, or explicit `TextGuard::filter()` / `prepareForValidation()` when cleanup is intended.
3. New opt-in helpers (`mergeFiltered()`, `Sanitize`, `text-guard.merge` middleware) never run unless you call or attach them.
4. `validate()` checks the complete pipeline, including custom steps, rather than a hard-coded subset. Empty configured presets are valid.
5. Unknown names, invalid step types and invalid UTF-8 fail explicitly. Use `false`, not `null`/`0`, to disable a step; `[]` enables defaults.
6. Numeric option lists replace rather than merge. Review published allowlists. Model preset typos no longer fall back to the default.
7. Remove `emoji_ranges` from published config. `allow_emoji` now handles Unicode sequences; obsolete range options throw. Use a custom step for application-specific symbol restrictions.
8. `nickname` now measures graphemes, retains joiners and omits the visibility heuristic. Other presets retain their character/length policies. Built-in presets now finish with trimming, so tag removal, zero-width removal or truncation cannot leave exposed leading/trailing whitespace.
9. HTML whitelist filtering requires ext-dom instead of silently degrading.

## For coding agents

Use this section when integrating or extending the package. Do not invent APIs from memory or from 1.x behavior.

### Scope

- **For:** normalizing and validating user-supplied plain/rich strings via composable pipelines and Laravel-friendly opt-in hooks.
- **Not for:** replacing output escaping (Blade `{{ }}`, `e()`, CSP, CSRF), authorization, rate limits, or storage encryption. Filtering here does not make HTML/JS safe to echo without boundary escaping.

### Pick one integration path

| Need | Do this |
|------|---------|
| Clean a string in code | `TextGuard::filter()` or `TextGuard::pipe()` |
| Clean specific request keys on this `Request` / `FormRequest` | `$request->mergeFiltered(['field' => 'preset', 'items.*.name' => 'safe'])` in the controller or `prepareForValidation()` |
| Clean during validation and return filtered values from `validated()` | Put `new Sanitize('preset')` **before** `new Filtered('preset')` on the same attribute |
| Reject input that still needs filtering (client must submit already-clean text) | `new Filtered('preset')` only |
| Clean declared fields on specific routes | Route middleware `text-guard.merge:field:preset,...` (alias registered by the package; **not** global) |
| Clean on Eloquent save | `TextGuardable` with `$textGuardFields` — only when the app owns that model |

`Sanitize` writes into **validator data only** (via `ValidatorAwareRule`). `Filtered` is a pure unchanged-input check and **never** mutates `request()`.

`Sanitized` is unrelated: it is a **visibility-ratio heuristic**, not pipeline filtering. Do not substitute it for `Sanitize` or `Filtered`.

Wildcards in `mergeFiltered()` use `Arr::dot` + `Str::is` on dotted keys (same idea as `items.*.name`). Middleware parameters are comma-separated `field:preset` pairs (only the first `:` splits); prefer `mergeFiltered()` when field patterns contain `:`.

Unknown presets and invalid pipeline configuration throw `InvalidArgumentException`. `mergeFiltered()` skips non-strings and invalid UTF-8 without throwing; `Filtered` fails those cases; `Sanitize` skips them without failing.

### Do not

- Register `text-guard.merge` as global middleware.
- Call `request()->merge()` from inside a validation rule.
- Assume `Filtered` still cleans the request like 1.x — it rejects dirty input instead.
- Confuse `Sanitize` (pipeline + `validated()` mutation) with `Sanitized` (invisible-character ratio).
- Put `Filtered` before `Sanitize` on the same attribute (order matters).
- Rely on `emoji_ranges` or other removed 1.x config keys.

### 1.x → 2.x facts agents must not miss

1. Pipeline steps run in **preset definition order**, not `pipeline_map` registration order.
2. `Filtered` no longer mutates global request data.
3. `mergeFiltered()`, `Sanitize`, and `text-guard.merge` run only when the application opts in.
4. List-style overrides (HTML allowlists, etc.) **replace** the whole list; `[]` clears a list.
5. Model preset typos throw instead of falling back to the default preset.
6. Remove `emoji_ranges` from config; use `character_whitelist` / `allow_emoji` and custom steps instead.

### Extending safely

1. Implement `PipelineStep` with `__invoke(string $text): string`.
2. Register with `TextGuard::registerPipelineStep('step_name', YourStep::class)`.
3. Reference the step in `config/text-guard.php` under `pipeline_map` and in preset arrays (`true`, `'NFKC'`, or `[...]` per step).
4. Use built-in preset names from config (`safe`, `strict`, `username`, `nickname`, `rich_text`) or define new presets — do not invent undocumented keys.

Human maintainers: project-wide agent maintenance rules live in [AGENTS.md](AGENTS.md); integration rules for agents are defined in this section.

## Development

~~~bash
composer install
composer fix
composer test
~~~

MIT License.
