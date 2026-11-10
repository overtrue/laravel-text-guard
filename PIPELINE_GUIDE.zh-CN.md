# Pipeline 参数参考

[English](PIPELINE_GUIDE.md) · [用法与升级说明](README.zh-CN.md)

## 组合契约

`TextGuard::pipe($text, $steps)` 按传入顺序执行步骤。`filter($text, $preset, $overrides)` 解析预设后调用同一执行器。`validate()` 判断该管道是否保持文本不变，自定义步骤自动参与。

配置为 `true`（默认构造）、`false`（禁用）、字符串（单个构造参数）或数组（原样传入，包括 `[]`）。未知名称和非法顶层配置类型抛异常。同一个类注册为多个名称即可重复执行；注册表只决定可用步骤，不决定顺序。

overrides 保留已有步骤位置，追加新步骤；关联配置递归合并，列表整体替换。缩短 HTML 白名单不能残留旧列表尾部的允许项。需要重新排序时，直接提供完整的 `pipe()` 配置。

## 内置步骤

| 步骤 | 参数 / 默认值 | 职责 |
| --- | --- | --- |
| `trim_whitespace` | 无 | 去除首尾 Unicode 空白 |
| `collapse_spaces` | 无 | 折叠连续空白 |
| `remove_control_chars` | 无 | 移除 ASCII 控制字符，保留 tab、LF、CR |
| `remove_zero_width` | `preserve_joiners: false` | 移除 U+200B/U+200C/U+200D/U+FEFF/U+2060，可选择保留 U+200C/U+200D |
| `remove_bidi_controls` | 无 | 移除 U+061C/U+200E/U+200F/U+202A–U+202E/U+2066–U+2069 |
| `unicode_normalization` | 字符串 `NFKC` | NFC/NFD/NFKC/NFKD 规范化 |
| `fullwidth_to_halfwidth` | `ascii: true, digits: true, latin: true, punct: false` | 现有 mb_convert_kana 的 a/n/r/s 标志；标志有重叠，s 实际转换全角空格 |
| `normalize_punctuations` | 字符串 `zh` | 按 `zh` 或 `en` 习惯转换标点 |
| `html_decode` | `enabled: true, flags: ENT_QUOTES \| ENT_HTML5, encoding: UTF-8` | 解码一轮 HTML 实体 |
| `strip_html` | 无 | 去 HTML 标签，保留文本内容 |
| `whitelist_html` | 见下文 | 基于 DOM 的标签、属性、协议白名单 |
| `character_whitelist` | 见下文 | 保留允许的文本，可选整体保留 emoji 序列 |
| `collapse_repeated_marks` | `max_repeat: 2, charset: '!?。，、…—'` | 限制指定标点的连续重复次数 |
| `visible_ratio_guard` | `min_ratio: 0.6` | 当前文本不满足码点可见度启发式比例时返回空串 |
| `truncate_length` | `max: 5000, unit: codepoint` | 按码点或字素限制长度 |

配置中用 `false` 禁用步骤。Unicode/标点规范化类的直接构造仍允许 null，但 `null` 不是合法的管道配置值。

## Unicode 操作

~~~php
TextGuard::pipe($text, [
    'unicode_normalization' => 'NFC',
    'remove_bidi_controls' => true,
    'remove_zero_width' => ['preserve_joiners' => true],
    'truncate_length' => ['max' => 40, 'unit' => 'grapheme'],
]);
~~~

这些选择彼此独立。双向控制符不等于全部从右向左书写的文字。连接符在波斯文、印度文字和 emoji 中有实际含义；保留连接符不代表验证其位置。后续字符白名单仍可能移除它们。

`truncate_length` 接受非负整数 max，以及 `codepoint` / `grapheme` 单位。零返回空串。字素使用已要求的 intl 扩展，不新增依赖；分段由 ICU 决定。输入必须是合法 UTF-8。两种模式都不理解 HTML 结构或显示列宽。

可见度比例按码点计算，连接字符可能被计为不可见。它不证明视觉真实性，也可能拒绝合法 emoji 或文字，不应默认加入所有管道。

## 字符白名单

参数：

- `enabled: true`
- `allow_emoji: true`
- `allow_chinese_punctuation: true`
- `allow_english_punctuation: true`

普通字符白名单保留 word 字符、汉字、空白和选定标点，不等于身份标识或文件名策略。emoji 识别将图形序列（包括 ZWJ、肤色）、区域指示符对、键帽、tag 序列作为整体处理，使用 Unicode 属性代替手写历史区间，但不是 RGI 校验器。属性支持随 PCRE 版本，字素分段随运行时 Unicode 数据。

`emoji_ranges` 已移除，传入会抛异常。要限制特定符号，注册独立的应用步骤；Unicode 区块并不等于语义类别。

~~~php
TextGuard::pipe($text, [
    'remove_zero_width' => ['preserve_joiners' => true],
    'character_whitelist' => ['allow_emoji' => true],
    'truncate_length' => ['max' => 30, 'unit' => 'grapheme'],
]);
~~~

## HTML 参数与执行顺序

`whitelist_html` 默认值：

~~~php
[
    'tags' => ['p', 'b', 'i', 'u', 'a', 'ul', 'ol', 'li', 'code', 'pre', 'br', 'blockquote', 'h1', 'h2', 'h3'],
    'attrs' => ['href', 'title', 'rel'],
    'protocols' => ['http', 'https', 'mailto'],
]
~~~

每个列表完整替换默认值，`[]` 清空对应列表。缺少 ext-dom 时抛异常，不降级为能力更弱的实现。白名单必须可信且尽量收窄；这不能代替覆盖更广威胁模型的专业富文本清洗器。

对于含编码标签的纯文本：

~~~php
TextGuard::pipe('&lt;b&gt;Hello&lt;/b&gt;', [
    'html_decode' => true,
    'strip_html' => true,
]); // Hello
~~~

反过来执行会得到 `<b>Hello</b>`，这是顺序的直接结果，说明顺序应由调用者而非注册表决定。实体解码只执行一轮，不承诺递归规范化。`strip_html` 保留 script 的文本内容，两者都不能代替上下文转义。

不要截断已经清洗的 HTML 字符串后，就认为结果仍有正确结构。应校验输入大小，或在最终清洗前使用理解 DOM 的步骤。Markdown、HTML 属性与 HTML 文本节点的渲染要求各不相同。

## 自定义步骤

实现 `PipelineStep::__invoke(string $text): string`，通过 `registerPipelineStep($name, $class)` 注册，再在预设或 `pipe()` 中使用。数组原样传入构造函数，`value` 键没有特殊含义。步骤不应有请求、存储或渲染副作用。

业务检测器和替换器可以独立组合，不需要把手机号、邮箱或域名策略加入工具核心。测试既覆盖应被转换的输入，也覆盖必须保持不变的普通文本；最终渲染测试与字符串转换测试分开。
