# Laravel TextGuard Pipeline 使用指南

本文档详细介绍了 Laravel TextGuard 包中所有 Pipeline 步骤的用法、参数配置和默认值。

## 目录

- [基础 Pipeline](#基础-pipeline)
- [Unicode 处理 Pipeline](#unicode-处理-pipeline)
- [HTML 处理 Pipeline](#html-处理-pipeline)
- [字符过滤 Pipeline](#字符过滤-pipeline)
- [长度控制 Pipeline](#长度控制-pipeline)
- [配置示例](#配置示例)

## 基础 Pipeline

### 1. TrimWhitespace - 去除首尾空白

**功能**: 去除字符串开头和结尾的空白字符（包括全角空格）

**参数**: 无

**默认值**: 无

**使用示例**:
```php
// 配置
'trim_whitespace' => true

// 效果
"  Hello World  " → "Hello World"
"　中文内容　" → "中文内容"
```

### 2. CollapseSpaces - 合并连续空格

**功能**: 将多个连续的空白字符合并为单个空格

**参数**: 无

**默认值**: 无

**使用示例**:
```php
// 配置
'collapse_spaces' => true

// 效果
"Hello    World" → "Hello World"
"多    个    空格" → "多 个 空格"
```

### 3. RemoveControlChars - 移除控制字符

**功能**: 移除控制字符，但保留换行符和制表符

**参数**: 无

**默认值**: 无

**使用示例**:
```php
// 配置
'remove_control_chars' => true

// 效果
"Hello\x00World" → "HelloWorld"
"Text\x1F\x7F" → "Text"
```

### 4. RemoveZeroWidth - 移除零宽字符

**功能**: 移除零宽字符（U+200B..200D, U+FEFF）

**参数**: 无

**默认值**: 无

**使用示例**:
```php
// 配置
'remove_zero_width' => true

// 效果
"Hello\u{200B}World" → "HelloWorld"
"Text\u{FEFF}" → "Text"
```

## Unicode 处理 Pipeline

### 5. NormalizeUnicode - Unicode 规范化

**功能**: 对文本进行 Unicode 规范化处理

**参数**:
- `form` (string|null): 规范化形式，默认为 `'NFKC'`

**可选值**:
- `'NFC'`: Canonical Decomposition, followed by Canonical Composition
- `'NFD'`: Canonical Decomposition
- `'NFKC'`: Compatibility Decomposition, followed by Canonical Composition
- `'NFKD'`: Compatibility Decomposition
- `null`: 禁用规范化

**使用示例**:
```php
// 配置
'unicode_normalization' => 'NFKC'

// 效果
"café" → "café" (规范化)
"①" → "1" (兼容性分解)
```

### 6. FullwidthToHalfwidth - 全角转半角

**功能**: 将全角字符转换为半角字符

**参数**:
- `ascii` (bool): 转换全角 ASCII 字符，默认 `true`
- `digits` (bool): 转换全角数字，默认 `true`
- `latin` (bool): 转换全角拉丁字母，默认 `true`
- `punct` (bool): 转换全角标点符号，默认 `false`

**使用示例**:
```php
// 配置
'fullwidth_to_halfwidth' => [
    'ascii' => true,
    'digits' => true,
    'latin' => true,
    'punct' => false, // 保留中文标点
]

// 效果
"Ｈｅｌｌｏ　Ｗｏｒｌｄ！" → "Hello World！"
"１２３４５" → "12345"
```

### 7. NormalizePunctuations - 标点符号规范化

**功能**: 根据语言环境规范化标点符号

**参数**:
- `locale` (string|null): 语言环境，默认为 `'zh'`

**可选值**:
- `'zh'`: 将英文标点转换为中文标点
- `'en'`: 将中文标点转换为英文标点
- `null`: 禁用规范化

**使用示例**:
```php
// 配置
'normalize_punctuations' => 'zh'

// 效果 (zh 模式)
"Hello, World!" → "Hello，World！"
"Hello, World!" → "Hello，World！"

// 配置
'normalize_punctuations' => 'en'

// 效果 (en 模式)
"Hello，World！" → "Hello, World!"
```

## HTML 处理 Pipeline

### 8. StripHtml - 移除 HTML 标签

**功能**: 移除所有 HTML 标签

**参数**: 无

**默认值**: 无

**使用示例**:
```php
// 配置
'strip_html' => true

// 效果
"<p>Hello <b>World</b></p>" → "Hello World"
"<script>alert('xss')</script>" → "alert('xss')"
```

### 9. HtmlDecode - HTML 实体解码

**功能**: 解码 HTML 实体

**参数**:
- `enabled` (bool): 是否启用，默认 `true`
- `flags` (int): 解码标志，默认 `ENT_QUOTES | ENT_HTML5`
- `encoding` (string): 编码格式，默认 `'UTF-8'`

**使用示例**:
```php
// 配置
'html_decode' => true

// 效果
"&lt;script&gt;" → "<script>"
"&amp;&quot;" → "&""
```

### 10. WhitelistHtml - HTML 白名单过滤

**功能**: 只保留允许的 HTML 标签和属性

**参数**:
- `tags` (array): 允许的标签列表，默认 `['p', 'b', 'i', 'u', 'a', 'ul', 'ol', 'li', 'code', 'pre', 'br', 'blockquote', 'h1', 'h2', 'h3']`
- `attrs` (array): 允许的属性列表，默认 `['href', 'title', 'rel']`
- `protocols` (array): 允许的协议列表，默认 `['http', 'https', 'mailto']`

**使用示例**:
```php
// 配置
'whitelist_html' => [
    'tags' => ['p', 'b', 'i', 'a'],
    'attrs' => ['href'],
    'protocols' => ['http', 'https'],
]

// 效果
"<p><b>Hello</b> <a href='http://example.com'>World</a></p>" → 保留
"<script>alert('xss')</script>" → 移除
"<a href='javascript:alert(1)'>Link</a>" → 移除 href 属性
```

## 字符过滤 Pipeline

### 11. CharacterWhitelist - 字符白名单过滤

**功能**: 只保留允许的字符类型

**参数**:
- `enabled` (bool): 是否启用，默认 `true`
- `allow_emoji` (bool): 是否允许表情符号，默认 `true`
- `allow_chinese_punctuation` (bool): 是否允许中文标点，默认 `true`
- `allow_english_punctuation` (bool): 是否允许英文标点，默认 `true`
- `emoji_ranges` (array): 表情符号范围配置
  - `emoticons` (bool): 表情符号，默认 `true`
  - `misc_symbols` (bool): 杂项符号，默认 `true`
  - `transport_map` (bool): 交通符号，默认 `true`
  - `misc_symbols_2` (bool): 杂项符号2，默认 `true`
  - `dingbats` (bool): 装饰符号，默认 `true`

**使用示例**:
```php
// 配置
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

// 效果
"Hello World! 😊" → "Hello World! 😊" (保留)
"Hello World! 🚗" → "Hello World! " (移除交通符号)
```

### 12. CollapseRepeatedMarks - 合并重复标点

**功能**: 限制重复标点符号的最大重复次数

**参数**:
- `max_repeat` (int): 最大重复次数，默认 `2`
- `charset` (string): 要处理的字符集，默认 `'!?。，、…—'`

**使用示例**:
```php
// 配置
'collapse_repeated_marks' => [
    'max_repeat' => 1,
    'charset' => '_-.',
]

// 效果
"Hello!!!" → "Hello!!" (限制为2个)
"Hello___World" → "Hello_World" (限制为1个)
```

## 长度控制 Pipeline

### 13. VisibleRatioGuard - 可见字符比例检查

**功能**: 检查可见字符比例，低于阈值时返回空字符串

**参数**:
- `min_ratio` (float): 最小可见字符比例，默认 `0.6`

**使用示例**:
```php
// 配置
'visible_ratio_guard' => ['min_ratio' => 0.8]

// 效果
"Hello World" → "Hello World" (比例 > 0.8)
"Hello\u{200B}\u{200B}\u{200B}World" → "" (比例 < 0.8)
```

### 14. TruncateLength - 长度截断

**功能**: 截断超过最大长度的文本

**参数**:
- `max` (int): 最大长度，默认 `5000`

**使用示例**:
```php
// 配置
'truncate_length' => ['max' => 100]

// 效果
"Hello World" → "Hello World" (长度 < 100)
"Very long text..." → "Very long text..." (截断到100字符)
```

## 配置示例

### 基础文本处理
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

### 用户名处理
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

### 富文本处理
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

### 严格模式
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

## 使用建议

1. **基础文本**: 使用 `trim_whitespace`、`collapse_spaces`、`remove_control_chars`、`remove_zero_width` 组合
2. **用户名**: 添加 `unicode_normalization`、`fullwidth_to_halfwidth`、`normalize_punctuations`
3. **昵称**: 允许表情符号，使用 `character_whitelist` 控制
4. **富文本**: 使用 `whitelist_html` 而不是 `strip_html`
5. **严格模式**: 禁用表情符号，提高可见字符比例要求

## 注意事项

- Pipeline 步骤按配置顺序执行
- 某些步骤可能会影响后续步骤的效果
- 建议先进行基础清理，再进行特殊处理
- 长度控制类步骤通常放在最后
- 测试时注意 Unicode 字符的处理效果
