# Laravel TextGuard

A powerful string sanitization and normalization tool for Laravel that can be used as validation rules or standalone utility.

[中文文档](README.zh-CN.md) | [English Documentation](README.md)

## Documentation

- [Pipeline Guide](PIPELINE_GUIDE.md) - Complete guide to all pipeline steps
- [Pipeline 指南](PIPELINE_GUIDE.zh-CN.md) - 所有 pipeline 步骤的完整指南

## Installation

```bash
composer require overtrue/laravel-text-guard
```

## Publish Configuration

```bash
php artisan vendor:publish --tag=text-guard-config
```

## Basic Usage

### As a Utility

```php
use Overtrue\TextGuard\TextGuard;

// Use default 'safe' preset
$clean = TextGuard::filter($dirty);

// Use specified preset
$clean = TextGuard::filter($dirty, 'username');

// Override configuration
$clean = TextGuard::filter($dirty, 'safe', [
    'truncate_length' => ['max' => 100]
]);
```

### As Validation Rules

```php
use Overtrue\TextGuard\Rules\Filtered;
use Overtrue\TextGuard\Rules\Sanitized;

// Filter then validate
$validator = validator($data, [
    'nickname' => [new Filtered('username')]
]);

// Validate visibility only
$validator = validator($data, [
    'content' => [new Sanitized(0.8, 1)]
]);
```

### Model Auto-Filtering

Use the `TextGuardable` trait to provide automatic filtering for model fields:

```php
use Illuminate\Database\Eloquent\Model;
use Overtrue\TextGuard\TextGuardable;

class User extends Model
{
    use TextGuardable;

    protected $fillable = ['name', 'bio', 'description'];

    // Method 1: Associative array (specify different presets)
    protected $textGuardFields = [
        'name' => 'username',        // Username uses stricter filtering
        'bio' => 'safe',             // Bio uses safe filtering
        'description' => 'rich_text' // Description allows rich text
    ];

    // Method 2: Indexed array (use default preset)
    protected $textGuardFields = ['name', 'bio', 'description'];
    protected $textGuardDefaultPreset = 'safe';

    // Method 3: Mixed configuration (some fields use default, some specify preset)
    protected $textGuardFields = [
        'name',  // Use default preset
        'bio' => 'safe',  // Specify preset
        'description' => 'rich_text'  // Specify preset
    ];
    protected $textGuardDefaultPreset = 'username';
}
```

When the model is saved, specified fields are automatically filtered:

```php
$user = new User();
$user->fill([
    'name' => 'ＵｓｅｒＮａｍｅ１２３！！！',  // Full-width characters
    'bio' => 'Normal text' . json_decode('"\u200B"') . 'hidden content',  // Zero-width characters
    'description' => '<script>alert("XSS")</script><p>Normal content</p>',  // HTML
]);
$user->save();

// After saving, data has been automatically filtered:
// $user->name = 'UserName123!!!'  // Full-width to half-width
// $user->bio = 'Normal texthidden content'  // Zero-width characters removed
// $user->description = '<p>Normal content</p>'  // Dangerous tags removed, safe tags preserved
```

#### Dynamic Field Management

```php
$user = new User();


// Manually filter fields
$filtered = $user->filterField('bio', 'safe');

// Get current configuration
$fields = $user->getTextGuardFields(); // Returns filtering field configuration
$fields = $user->getTextGuardFields(); // Returns field list
```

### FormRequest Integration

```php
class UpdateProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('nickname')) {
            $this->merge([
                'nickname' => TextGuard::filter(
                    (string)$this->input('nickname'),
                    'username'
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', new Sanitized(0.9, 1)],
            'bio' => ['nullable', new Filtered('safe', false)],
        ];
    }
}
```

## Pipeline Usage

Laravel TextGuard provides 14 built-in pipeline steps for text processing. Each pipeline step can be configured with specific parameters to meet different requirements.

### Available Pipeline Steps

#### Basic Text Processing
- **`trim_whitespace`** - Remove leading/trailing whitespace (including full-width spaces)
- **`collapse_spaces`** - Collapse multiple consecutive spaces into single space
- **`remove_control_chars`** - Remove control characters while preserving newlines and tabs
- **`remove_zero_width`** - Remove zero-width characters (U+200B..200D, U+FEFF)

#### Unicode Processing
- **`unicode_normalization`** - Unicode normalization (NFC, NFD, NFKC, NFKD)
- **`fullwidth_to_halfwidth`** - Convert fullwidth characters to halfwidth
- **`normalize_punctuations`** - Normalize punctuation based on locale (zh/en)

#### HTML Processing
- **`strip_html`** - Remove all HTML tags
- **`html_decode`** - Decode HTML entities
- **`whitelist_html`** - Keep only allowed HTML tags and attributes

#### Character Filtering
- **`character_whitelist`** - Keep only allowed character types (with emoji support)
- **`collapse_repeated_marks`** - Limit repeated punctuation marks

#### Length Control
- **`visible_ratio_guard`** - Check visible character ratio
- **`truncate_length`** - Truncate text to maximum length

### Pipeline Configuration Examples

#### Basic Text Cleaning
```php
'basic_clean' => [
    'trim_whitespace' => true,
    'collapse_spaces' => true,
    'remove_control_chars' => true,
    'remove_zero_width' => true,
    'strip_html' => true,
    'visible_ratio_guard' => ['min_ratio' => 0.6],
    'truncate_length' => ['max' => 1000],
],
```

#### Username Processing
```php
'username' => [
    'trim_whitespace' => true,
    'collapse_spaces' => true,
    'remove_control_chars' => true,
    'remove_zero_width' => true,
    'unicode_normalization' => 'NFKC',
    'fullwidth_to_halfwidth' => [
        'ascii' => true,
        'digits' => true,
        'latin' => true,
        'punct' => true,
    ],
    'normalize_punctuations' => 'en',
    'strip_html' => true,
    'collapse_repeated_marks' => [
        'max_repeat' => 1,
        'charset' => '_-.',
    ],
    'visible_ratio_guard' => ['min_ratio' => 0.9],
    'truncate_length' => ['max' => 50],
],
```

#### Rich Text Processing
```php
'rich_text' => [
    'trim_whitespace' => true,
    'remove_control_chars' => true,
    'remove_zero_width' => true,
    'unicode_normalization' => 'NFC',
    'whitelist_html' => [
        'tags' => ['p', 'b', 'i', 'u', 'a', 'ul', 'ol', 'li', 'code', 'pre', 'br', 'blockquote', 'h1', 'h2', 'h3'],
        'attrs' => ['href', 'title', 'rel'],
        'protocols' => ['http', 'https', 'mailto'],
    ],
    'visible_ratio_guard' => ['min_ratio' => 0.5],
    'truncate_length' => ['max' => 20000],
],
```

#### Nickname with Emoji Support
```php
'nickname' => [
    'trim_whitespace' => true,
    'collapse_spaces' => true,
    'remove_control_chars' => true,
    'remove_zero_width' => true,
    'unicode_normalization' => 'NFKC',
    'fullwidth_to_halfwidth' => [
        'ascii' => true,
        'digits' => true,
        'latin' => true,
        'punct' => false, // Preserve Chinese punctuation
    ],
    'html_decode' => true,
    'strip_html' => true,
    'character_whitelist' => [
        'enabled' => true,
        'allow_emoji' => true,
        'allow_chinese_punctuation' => true,
        'allow_english_punctuation' => true,
        'emoji_ranges' => [
            'emoticons' => true,
            'misc_symbols' => true,
            'transport_map' => true,
            'misc_symbols_2' => true,
            'dingbats' => true,
        ],
    ],
    'visible_ratio_guard' => ['min_ratio' => 0.7],
    'truncate_length' => ['max' => 30],
],
```

### Pipeline Parameter Types

The system automatically passes configuration to constructors based on type:

- **Boolean values** (`true`/`false`) → No parameter constructor: `new Class()`
- **String values** (`'NFKC'`) → Single parameter constructor: `new Class('NFKC')`
- **Array values** (`['max' => 100]`) → Array parameter constructor: `new Class(['max' => 100])`

### Usage Tips

1. **Order matters**: Pipeline steps are executed in configuration order
2. **Basic cleanup first**: Start with `trim_whitespace`, `collapse_spaces`, `remove_control_chars`, `remove_zero_width`
3. **Unicode processing**: Use `unicode_normalization` and `fullwidth_to_halfwidth` for international text
4. **HTML handling**: Use `whitelist_html` for rich text, `strip_html` for plain text
5. **Length control last**: Place `visible_ratio_guard` and `truncate_length` at the end

For detailed parameter information and more examples, see the [Pipeline Guide](PIPELINE_GUIDE.md).

## Preset Configurations

### Level-based Presets

#### `safe` Preset
Default preset suitable for most normal text input fields. Includes basic text sanitization features.

#### `strict` Preset
More restrictive filtering mode with stricter rules:
- No emoji characters allowed
- Converts all punctuation to half-width
- Higher visible character ratio requirement (0.8)
- Shorter text length limit (5000)

### Function-specific Presets (Examples)

#### `username` Preset
Suitable for username input with stricter normalization.

#### `nickname` Preset
Suitable for user nicknames with emoji and Chinese punctuation support.

#### `rich_text` Preset
Suitable for rich text content, preserving safe HTML tags.

## Extended Features

### Register Custom Pipeline Steps

```php
use Overtrue\TextGuard\TextGuard;

// Register custom step
TextGuard::registerPipelineStep('custom_step', YourCustomPipeline::class);

// Use in preset
$clean = TextGuard::filter($dirty, 'custom', [
    'custom_step' => ['option' => 'value']
]);
```

### Get Available Steps

```php
$availableSteps = TextGuard::getAvailableSteps();
// Returns: ['trim_whitespace', 'collapse_spaces', 'remove_control_chars', ...]
```

### Create Custom Pipeline Steps

```php
use Overtrue\TextGuard\Pipeline\PipelineStep;

class CustomStep implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        // Your custom logic
        return $text;
    }
}
```

### Constructor Configuration

All Pipeline steps now use constructor configuration, following traditional OOP design:

```php
// config/text-guard.php
return [
    'pipeline_map' => [
        // Simplified syntax: use class names directly
        'trim_whitespace' => \Overtrue\TextGuard\Pipeline\TrimWhitespace::class,
        'strip_html' => \Overtrue\TextGuard\Pipeline\StripHtml::class,
    ],

    'presets' => [
        'safe' => [
            // Boolean configuration: enable feature
            'trim_whitespace' => true,

            // String configuration: pass to constructor
            'unicode_normalization' => 'NFKC',

            // Array configuration: pass to constructor
            'truncate_length' => ['max' => 100],
        ],
    ],
];
```

### Configuration Passing Mechanism

The system automatically passes configuration to constructors based on type:

- `true` → No parameter constructor `new Class()`
- `'NFKC'` → Single parameter constructor `new Class('NFKC')`
- `['max' => 100]` → Array parameter constructor `new Class(['max' => 100])`

## Real-world Usage Scenarios

### User Registration Form

```php
// Clean nickname during user registration
class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('nickname')) {
            $this->merge([
                'nickname' => TextGuard::filter(
                    (string)$this->input('nickname'),
                    'username'
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'max:20', new Sanitized(0.9, 1)],
            'email' => ['required', 'email'],
        ];
    }
}

// Processing result:
// Input: "  ＵｓｅｒＮａｍｅ１２３！！！  "
// Output: "UserName123!!"
```

### Article Content Management

```php
// Clean content when publishing articles
class ArticleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge([
                'content' => TextGuard::filter(
                    (string)$this->input('content'),
                    'rich_text'
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', new Filtered('safe')],
            'content' => ['required', 'string', new Sanitized(0.8, 10)],
        ];
    }
}

// Processing result:
// Input: "<p>Hello <script>alert('xss')</script> World</p>"
// Output: "<p>Hello  World</p>"
```

### Comment System

```php
// Clean content when submitting comments
class CommentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge([
                'content' => TextGuard::filter(
                    (string)$this->input('content'),
                    'safe'
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:500', new Sanitized(0.7, 1)],
        ];
    }
}

// Processing result:
// Input: "  Great article!!!👍👍👍   "
// Output: "Great article!!👍👍👍"
```

### Search Keyword Processing

```php
// Clean keywords during search
class SearchController extends Controller
{
    public function search(Request $request)
    {
        $keyword = TextGuard::filter($request->input('q', ''), 'safe');

        if (empty($keyword)) {
            return redirect()->back()->with('error', 'Please enter a valid search term');
        }

        $results = $this->searchService->search($keyword);

        return view('search.results', compact('results', 'keyword'));
    }
}

// Processing result:
// Input: "  Laravel Framework  "
// Output: "Laravel Framework"
```

### Batch Data Processing

```php
// Clean user data during batch import
class UserImportService
{
    public function importUsers(array $users): void
    {
        foreach ($users as $user) {
            $cleanUser = [
                'name' => TextGuard::filter($user['name'], 'safe'),
                'email' => TextGuard::filter($user['email'], 'safe'),
                'bio' => TextGuard::filter($user['bio'] ?? '', 'rich_text'),
            ];

            User::create($cleanUser);
        }
    }
}
```

### Custom Pipeline Steps

```php
// Create custom sensitive word filtering step
class SensitiveWordFilter implements PipelineStep
{
    public function __construct(protected array $sensitiveWords = []) {}

    public function __invoke(string $text): string
    {
        foreach ($this->sensitiveWords as $word) {
            $text = str_ireplace($word, str_repeat('*', mb_strlen($word)), $text);
        }

        return $text;
    }
}

// Register and use
TextGuard::registerPipelineStep('sensitive_filter', SensitiveWordFilter::class);

$clean = TextGuard::filter($dirty, 'custom', [
    'sensitive_filter' => ['sensitiveWords' => ['badword1', 'badword2']]
]);
```

## Features

- Whitespace handling (trim, collapse spaces)
- Control character removal
- Zero-width character removal
- Unicode normalization
- Full-width to half-width conversion
- Punctuation normalization
- HTML tag processing
- Repeated punctuation collapsing
- Visibility checking
- Length truncation
- Extensible pipeline architecture
- Runtime custom step registration
- Emoji support
- Chinese punctuation support
- Character whitelist filtering

## Testing

```bash
composer test
```

## Code Quality

This project follows strict code quality standards:

```bash
# Format code
composer fix

# Run tests
composer test
```

## License

MIT License
