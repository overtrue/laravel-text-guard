# Laravel TextGuard Pipeline Usage Guide

This document provides detailed information about all Pipeline steps in the Laravel TextGuard package, including usage, parameter configuration, and default values.

## Table of Contents

- [Basic Pipelines](#basic-pipelines)
- [Unicode Processing Pipelines](#unicode-processing-pipelines)
- [HTML Processing Pipelines](#html-processing-pipelines)
- [Character Filtering Pipelines](#character-filtering-pipelines)
- [Length Control Pipelines](#length-control-pipelines)
- [Configuration Examples](#configuration-examples)

## Basic Pipelines

### 1. TrimWhitespace - Remove Leading/Trailing Whitespace

**Function**: Removes leading and trailing whitespace characters (including full-width spaces)

**Parameters**: None

**Default Values**: None

**Usage Example**:
```php
// Configuration
'trim_whitespace' => true

// Effect
"  Hello World  " → "Hello World"
"　Chinese Content　" → "Chinese Content"
```

### 2. CollapseSpaces - Collapse Multiple Spaces

**Function**: Collapses multiple consecutive whitespace characters into a single space

**Parameters**: None

**Default Values**: None

**Usage Example**:
```php
// Configuration
'collapse_spaces' => true

// Effect
"Hello    World" → "Hello World"
"Multiple    Spaces" → "Multiple Spaces"
```

### 3. RemoveControlChars - Remove Control Characters

**Function**: Removes control characters while preserving newlines and tabs

**Parameters**: None

**Default Values**: None

**Usage Example**:
```php
// Configuration
'remove_control_chars' => true

// Effect
"Hello\x00World" → "HelloWorld"
"Text\x1F\x7F" → "Text"
```

### 4. RemoveZeroWidth - Remove Zero-Width Characters

**Function**: Removes zero-width characters (U+200B..200D, U+FEFF)

**Parameters**: None

**Default Values**: None

**Usage Example**:
```php
// Configuration
'remove_zero_width' => true

// Effect
"Hello\u{200B}World" → "HelloWorld"
"Text\u{FEFF}" → "Text"
```

## Unicode Processing Pipelines

### 5. NormalizeUnicode - Unicode Normalization

**Function**: Performs Unicode normalization on text

**Parameters**:
- `form` (string|null): Normalization form, defaults to `'NFKC'`

**Available Values**:
- `'NFC'`: Canonical Decomposition, followed by Canonical Composition
- `'NFD'`: Canonical Decomposition
- `'NFKC'`: Compatibility Decomposition, followed by Canonical Composition
- `'NFKD'`: Compatibility Decomposition
- `null`: Disable normalization

**Usage Example**:
```php
// Configuration
'unicode_normalization' => 'NFKC'

// Effect
"café" → "café" (normalized)
"①" → "1" (compatibility decomposition)
```

### 6. FullwidthToHalfwidth - Convert Fullwidth to Halfwidth

**Function**: Converts fullwidth characters to halfwidth characters

**Parameters**:
- `ascii` (bool): Convert fullwidth ASCII characters, default `true`
- `digits` (bool): Convert fullwidth digits, default `true`
- `latin` (bool): Convert fullwidth Latin letters, default `true`
- `punct` (bool): Convert fullwidth punctuation, default `false`

**Usage Example**:
```php
// Configuration
'fullwidth_to_halfwidth' => [
    'ascii' => true,
    'digits' => true,
    'latin' => true,
    'punct' => false, // Preserve Chinese punctuation
]

// Effect
"Ｈｅｌｌｏ　Ｗｏｒｌｄ！" → "Hello World！"
"１２３４５" → "12345"
```

### 7. NormalizePunctuations - Normalize Punctuation

**Function**: Normalizes punctuation based on locale

**Parameters**:
- `locale` (string|null): Locale, defaults to `'zh'`

**Available Values**:
- `'zh'`: Convert English punctuation to Chinese punctuation
- `'en'`: Convert Chinese punctuation to English punctuation
- `null`: Disable normalization

**Usage Example**:
```php
// Configuration
'normalize_punctuations' => 'zh'

// Effect (zh mode)
"Hello, World!" → "Hello，World！"

// Configuration
'normalize_punctuations' => 'en'

// Effect (en mode)
"Hello，World！" → "Hello, World!"
```

## HTML Processing Pipelines

### 8. StripHtml - Remove HTML Tags

**Function**: Removes all HTML tags

**Parameters**: None

**Default Values**: None

**Usage Example**:
```php
// Configuration
'strip_html' => true

// Effect
"<p>Hello <b>World</b></p>" → "Hello World"
"<script>alert('xss')</script>" → "alert('xss')"
```

### 9. HtmlDecode - Decode HTML Entities

**Function**: Decodes HTML entities

**Parameters**:
- `enabled` (bool): Whether to enable, default `true`
- `flags` (int): Decode flags, default `ENT_QUOTES | ENT_HTML5`
- `encoding` (string): Encoding format, default `'UTF-8'`

**Usage Example**:
```php
// Configuration
'html_decode' => true

// Effect
"&lt;script&gt;" → "<script>"
"&amp;&quot;" → "&""
```

### 10. WhitelistHtml - HTML Whitelist Filtering

**Function**: Keeps only allowed HTML tags and attributes

**Parameters**:
- `tags` (array): Allowed tags list, default `['p', 'b', 'i', 'u', 'a', 'ul', 'ol', 'li', 'code', 'pre', 'br', 'blockquote', 'h1', 'h2', 'h3']`
- `attrs` (array): Allowed attributes list, default `['href', 'title', 'rel']`
- `protocols` (array): Allowed protocols list, default `['http', 'https', 'mailto']`

**Usage Example**:
```php
// Configuration
'whitelist_html' => [
    'tags' => ['p', 'b', 'i', 'a'],
    'attrs' => ['href'],
    'protocols' => ['http', 'https'],
]

// Effect
"<p><b>Hello</b> <a href='http://example.com'>World</a></p>" → Preserved
"<script>alert('xss')</script>" → Removed
"<a href='javascript:alert(1)'>Link</a>" → href attribute removed
```

## Character Filtering Pipelines

### 11. CharacterWhitelist - Character Whitelist Filtering

**Function**: Keeps only allowed character types

**Parameters**:
- `enabled` (bool): Whether to enable, default `true`
- `allow_emoji` (bool): Whether to allow emoji, default `true`
- `allow_chinese_punctuation` (bool): Whether to allow Chinese punctuation, default `true`
- `allow_english_punctuation` (bool): Whether to allow English punctuation, default `true`
- `emoji_ranges` (array): Emoji range configuration
  - `emoticons` (bool): Emoticons, default `true`
  - `misc_symbols` (bool): Miscellaneous symbols, default `true`
  - `transport_map` (bool): Transport symbols, default `true`
  - `misc_symbols_2` (bool): Miscellaneous symbols 2, default `true`
  - `dingbats` (bool): Decorative symbols, default `true`

**Usage Example**:
```php
// Configuration
'character_whitelist' => [
    'enabled' => true,
    'allow_emoji' => true,
    'allow_chinese_punctuation' => true,
    'allow_english_punctuation' => true,
    'emoji_ranges' => [
        'emoticons' => true,
        'misc_symbols' => false,
        'transport_map' => false,
        'misc_symbols_2' => false,
        'dingbats' => false,
    ],
]

// Effect
"Hello World! 😊" → "Hello World! 😊" (preserved)
"Hello World! 🚗" → "Hello World! " (transport symbol removed)
```

### 12. CollapseRepeatedMarks - Collapse Repeated Punctuation

**Function**: Limits the maximum repetition count of repeated punctuation marks

**Parameters**:
- `max_repeat` (int): Maximum repetition count, default `2`
- `charset` (string): Character set to process, default `'!?。，、…—'`

**Usage Example**:
```php
// Configuration
'collapse_repeated_marks' => [
    'max_repeat' => 1,
    'charset' => '_-.',
]

// Effect
"Hello!!!" → "Hello!!" (limited to 2)
"Hello___World" → "Hello_World" (limited to 1)
```

## Length Control Pipelines

### 13. VisibleRatioGuard - Visible Character Ratio Check

**Function**: Checks visible character ratio, returns empty string if below threshold

**Parameters**:
- `min_ratio` (float): Minimum visible character ratio, default `0.6`

**Usage Example**:
```php
// Configuration
'visible_ratio_guard' => ['min_ratio' => 0.8]

// Effect
"Hello World" → "Hello World" (ratio > 0.8)
"Hello\u{200B}\u{200B}\u{200B}World" → "" (ratio < 0.8)
```

### 14. TruncateLength - Length Truncation

**Function**: Truncates text that exceeds maximum length

**Parameters**:
- `max` (int): Maximum length, default `5000`

**Usage Example**:
```php
// Configuration
'truncate_length' => ['max' => 100]

// Effect
"Hello World" → "Hello World" (length < 100)
"Very long text..." → "Very long text..." (truncated to 100 characters)
```

## Configuration Examples

### Basic Text Processing
```php
'basic_text' => [
    'trim_whitespace' => true,
    'collapse_spaces' => true,
    'remove_control_chars' => true,
    'remove_zero_width' => true,
    'strip_html' => true,
    'visible_ratio_guard' => ['min_ratio' => 0.6],
    'truncate_length' => ['max' => 1000],
],
```

### Username Processing
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

### Rich Text Processing
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

### Strict Mode
```php
'strict' => [
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
    'html_decode' => true,
    'strip_html' => true,
    'character_whitelist' => [
        'enabled' => true,
        'allow_emoji' => false,
        'allow_chinese_punctuation' => true,
        'allow_english_punctuation' => true,
    ],
    'visible_ratio_guard' => ['min_ratio' => 0.8],
    'truncate_length' => ['max' => 5000],
],
```

## Usage Recommendations

1. **Basic Text**: Use `trim_whitespace`, `collapse_spaces`, `remove_control_chars`, `remove_zero_width` combination
2. **Username**: Add `unicode_normalization`, `fullwidth_to_halfwidth`, `normalize_punctuations`
3. **Nickname**: Allow emoji, use `character_whitelist` for control
4. **Rich Text**: Use `whitelist_html` instead of `strip_html`
5. **Strict Mode**: Disable emoji, increase visible character ratio requirements

## Important Notes

- Pipeline steps are executed in configuration order
- Some steps may affect the results of subsequent steps
- Recommend basic cleanup first, then special processing
- Length control steps are usually placed last
- Pay attention to Unicode character handling effects during testing
