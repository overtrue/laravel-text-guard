# Laravel TextGuard

可组合的 Laravel 字符串过滤工具，过滤与校验职责分离。

[English](README.md) · [Pipeline 参数参考](PIPELINE_GUIDE.zh-CN.md)

## 安装

需要 PHP 8.3+、Laravel 12/13、mbstring 和 intl。HTML 白名单过滤另需 ext-dom。

~~~bash
composer require overtrue/laravel-text-guard
php artisan vendor:publish --tag=text-guard-config
~~~

## 组合操作，而不是内置业务策略

每个步骤只负责字符串到字符串的转换。重复使用的组合可命名为预设，也可以直接声明执行顺序：

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

步骤按传入顺序执行，不按注册表顺序执行。预设只是有名字的有序配置。工具不内置手机号阈值、域名后缀表、邮件专用替换或文件名策略。

- `true` 使用默认构造参数；`false` 禁用步骤；字符串和数组（包括 `[]`）原样传入构造函数。
- overrides 递归合并关联配置；列表整体替换，`[]` 可清空白名单。
- 覆盖已有步骤不移动位置，新步骤追加到末尾。要改变顺序，使用 `pipe()` 或新预设。同一类注册成不同名称即可多次执行。
- 未知预设/步骤、非法配置类型抛出 `InvalidArgumentException`。显式定义的空预设和 `pipe($input, [])` 原样返回文本。
- `filter()` 和 `pipe()` 拒绝非法 UTF-8。配置必须来自可信应用代码，不接收用户提供的管道配置。

不使用 Facade 时，可将配置数组传给 `TextGuardManager`。其字符串操作不读取 HTTP 请求。

## 明确选择清洗还是拒绝

过滤返回新字符串，不修改输入或 HTTP 请求。校验判断同一管道是否保持输入不变：

~~~php
$result = TextGuard::validate($input, 'safe'); // ['valid' => bool, 'errors' => array]

use Overtrue\TextGuard\Rules\Filtered;

$validator = validator($data, [
    'items.*.name' => ['required', 'string', new Filtered('safe')],
]);
$accepted = $validator->validated();
~~~

`Filtered` 拒绝需要清洗的值，不写入 `request()`，支持独立 Validator 和嵌套/通配字段。`mustNotBeEmpty: false` 允许原样的空字符串，不会允许需要被删除的空白。可选 `overrides` 参数与 `filter()` 一致。

需要清洗后接受时，在校验前显式执行：

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

必填、可空、业务长度等条件由 Laravel 的 `required`、`nullable`、长度规则承担。非法类型/UTF-8 在 `Filtered` 中返回校验失败；配置错误仍抛异常。`Sanitized(minVisibleRatio: 0.6, minLength: 1)` 仅是**可见度启发式检查**，不保证文本已经清洗或可安全渲染。

## Unicode 策略彼此独立

- `remove_zero_width` 默认移除 U+200B/U+200C/U+200D/U+FEFF/U+2060。`preserve_joiners: true` 保留文字和 emoji 中的 ZWNJ/ZWJ，但不验证连接符的位置。
- `remove_bidi_controls` 移除 Unicode Bidi_Control 字符，保留普通从右向左书写的文字和连接符。因为方向控制可能有合理用途，该步骤需显式启用。
- `truncate_length` 默认按码点计数。`unit: grapheme` 按用户感知字符截断，保留组合附加符和 emoji 序列。`max: 0` 返回空字符串；非法最大值/单位抛异常。
- `character_whitelist` 将图形 ZWJ 序列、肤色、区域指示符对、键帽、emoji tag 序列作为整体保留或删除。它不是 RGI emoji 校验器。识别依赖运行时 Unicode 数据；字素截断使用 ICU。

`nickname` 示例预设保留连接符、按字素限长，不再套用码点可见度启发式检查。后续步骤仍可能改变前面的结果：保留连接符不代表绕过字符白名单。请按文本语言和用途组合操作。

## 复用预设与扩展

内置 `safe`、`strict`、`username`、`nickname`、`rich_text`。精确定义见 `config/text-guard.php`；名字只是便捷标签，不代表安全保证。

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

示例仅做字面替换，不是 HTML/Markdown 转义。业务特征识别和打码放在独立的自定义步骤；替换策略不应强制绑定规范化、存储或渲染方式。

查询配置：`getAvailableSteps()`、`getAvailablePresets()`、`getPresetConfig($name)`。

## Eloquent 集成必须显式启用

~~~php
use Illuminate\Database\Eloquent\Model;
use Overtrue\TextGuard\TextGuardable;

class User extends Model
{
    use TextGuardable;

    protected $textGuardFields = ['name' => 'nickname', 'bio' => 'safe'];
}
~~~

保存时只处理已配置、dirty 且非 null 的字段。字段名列表使用 `getTextGuardDefaultPreset()`；关联项指定预设，支持混合配置。未知预设抛异常，不再静默回退。

可以覆盖 `getTextGuardFields()` / `getTextGuardDefaultPreset()` 代替属性。`filterField($field, $preset)` 返回过滤值，不赋值；`getTextGuardFieldNames()` 返回规范化字段名。`disableTextGuard()` / `enableTextGuard()` 影响进程内全部模型；`withoutTextGuard($callback)` 无论成功或异常都恢复原状态。不要跨并发请求使用进程级开关。

仅用于展示的打码不应持久化覆盖原值，除非这就是明确意图。

## 渲染与组合边界

`strip_html` 是去标签，不是输出上下文转义。`html_decode` 只解码一轮；如果输入契约需要解码后去标签，就显式按此顺序执行，不要隐式反复解码到稳定。任意管道不保证幂等。

HTML/属性/JavaScript/Markdown 分别在输出边界转义。HTML 白名单过滤缺少 ext-dom 时明确失败，不降级到保留属性的去标签函数。白名单整体替换默认列表。清洗后再解码或规范化 HTML，需要重新评估最终输出。普通字符串截断不理解 DOM；富文本应拒绝超长输入，或在最终清洗前使用理解 DOM 的截断步骤。

## 升级：破坏性变化

本次设计调整需要破坏性版本发布：

1. 按预设顺序执行。检查依赖 `pipeline_map` 顺序的配置；先解码再去标签现已符合文档。
2. `Filtered` 不再清洗全局请求，而是拒绝脏输入；有意清洗的入口改为先过滤、后校验。
3. `validate()` 校验完整管道，包括自定义步骤，不再维护硬编码子集。显式空预设有效。
4. 未知名称、非法步骤类型、非法 UTF-8 明确失败。使用 `false` 而非 `null`/`0` 禁用步骤；`[]` 表示启用默认参数。
5. 数字索引列表整体替换，不递归合并；检查已发布的白名单。模型预设拼写错误不再回退。
6. 从已发布配置移除 `emoji_ranges`。`allow_emoji` 改为识别 Unicode 序列；旧区间参数抛异常。特定符号限制使用自定义步骤。
7. `nickname` 改为按字素计数、保留连接符、去掉可见度启发式检查。其他预设保留原有字符和长度策略。内置预设统一在末尾去除首尾空白，避免去标签、去零宽字符或截断后残留空白。
8. HTML 白名单过滤必须有 ext-dom，不再静默降级。

## 开发

~~~bash
composer install
composer fix
composer test
~~~

MIT License。
