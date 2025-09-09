# Laravel TextGuard

TextGuard：可作 Rule 也可单用的字符串清洗/规范化工具

[English Documentation](README.md) | [中文文档](README.zh-CN.md)

## 安装

```bash
composer require overtrue/laravel-text-guard
```

## 发布配置文件

```bash
php artisan vendor:publish --tag=text-guard-config
```

## 基本用法

### 作为工具使用

```php
use Overtrue\TextGuard\TextGuard;

// 使用默认 'safe' 预设
$clean = TextGuard::filter($dirty);

// 使用指定预设
$clean = TextGuard::filter($dirty, 'username');

// 覆盖配置
$clean = TextGuard::filter($dirty, 'safe', [
    'truncate_length' => ['max' => 100]
]);
```

### 作为验证规则使用

```php
use Overtrue\TextGuard\Rules\Filtered;
use Overtrue\TextGuard\Rules\Sanitized;

// 先过滤再验证
$validator = validator($data, [
    'nickname' => [new Filtered('username')]
]);

// 仅验证可见度
$validator = validator($data, [
    'content' => [new Sanitized(0.8, 1)]
]);
```

### 模型自动过滤

使用 `TextGuardable` trait 为模型字段提供自动过滤功能：

```php
use Illuminate\Database\Eloquent\Model;
use Overtrue\TextGuard\TextGuardable;

class User extends Model
{
    use TextGuardable;

    protected $fillable = ['name', 'bio', 'description'];

    // 方式1：关联数组（指定不同预设）
    protected $textGuardFields = [
        'name' => 'username',        // 用户名使用更严格的过滤
        'bio' => 'safe',             // 简介使用安全过滤
        'description' => 'rich_text' // 描述允许富文本
    ];

    // 方式2：索引数组（使用默认预设）
    protected $textGuardFields = ['name', 'bio', 'description'];
    protected $textGuardDefaultPreset = 'safe';

    // 方式3：混合配置（部分字段使用默认预设，部分指定预设）
    protected $textGuardFields = [
        'name',  // 使用默认预设
        'bio' => 'safe',  // 指定预设
        'description' => 'rich_text'  // 指定预设
    ];
    protected $textGuardDefaultPreset = 'username';
}
```

当模型保存时，指定的字段会自动进行过滤：

```php
$user = new User();
$user->fill([
    'name' => 'ＵｓｅｒＮａｍｅ１２３！！！',  // 全角字符
    'bio' => '正常文本' . json_decode('"\u200B"') . '隐藏内容',  // 零宽字符
    'description' => '<script>alert("XSS")</script><p>正常内容</p>',  // HTML
]);
$user->save();

// 保存后数据已被自动过滤：
// $user->name = 'UserName123!!!'  // 全角转半角
// $user->bio = '正常文本隐藏内容'  // 零宽字符被移除
// $user->description = '<p>正常内容</p>'  // 危险标签被移除，保留安全标签
```

#### 动态管理过滤字段

```php
$user = new User();


// 手动过滤字段
$filtered = $user->filterField('bio', 'safe');

// 获取当前配置
$fields = $user->getTextGuardFields(); // 返回过滤字段配置
$fields = $user->getTextGuardFields(); // 返回字段列表
```

### 与 FormRequest 集成

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

## 预设配置

### 级别性预设

#### `safe` 预设
默认预设，适合大多数普通文本输入框。包含基本的文本清洗功能。

#### `strict` 预设
更严格的过滤模式：
- 不允许 emoji 字符
- 转换所有标点为半角
- 更高的可见字符比例要求 (0.8)
- 较短的文本长度限制 (5000)

### 功能特定预设（示例）

#### `username` 预设
适合用户名输入，更严格的规范化。

#### `nickname` 预设
适合用户昵称，支持 emoji 和中文标点符号。

#### `rich_text` 预设
适合富文本内容，保留安全的 HTML 标签。

## 扩展功能

### 注册自定义管道步骤

```php
use Overtrue\TextGuard\TextGuard;

// 注册自定义步骤
TextGuard::registerPipelineStep('custom_step', YourCustomPipeline::class);

// 在预设中使用
$clean = TextGuard::filter($dirty, 'custom', [
    'custom_step' => ['option' => 'value']
]);
```

### 获取可用步骤

```php
$availableSteps = TextGuard::getAvailableSteps();
// 返回: ['trim_whitespace', 'collapse_spaces', 'remove_control_chars', ...]
```

### 创建自定义管道步骤

```php
use Overtrue\TextGuard\Pipeline\PipelineStep;

class CustomStep implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        // 你的自定义逻辑
        return $text;
    }
}
```

### 构造函数配置

现在所有 Pipeline 步骤都使用构造函数进行配置，更符合传统的 OOP 设计：

```php
// config/text-guard.php
return [
    'pipeline_map' => [
        // 简化语法：直接使用类名
        'trim_whitespace' => \Overtrue\TextGuard\Pipeline\TrimWhitespace::class,
        'strip_html' => \Overtrue\TextGuard\Pipeline\StripHtml::class,
    ],

    'presets' => [
        'safe' => [
            // 布尔值配置：启用功能
            'trim_whitespace' => true,

            // 字符串配置：传递给构造函数
            'unicode_normalization' => 'NFKC',

            // 数组配置：传递给构造函数
            'truncate_length' => ['max' => 100],
        ],
    ],
];
```

### 配置传递机制

系统会根据配置类型自动传递给构造函数：

- `true` → 无参数构造函数 `new Class()`
- `'NFKC'` → 单参数构造函数 `new Class('NFKC')`
- `['max' => 100]` → 数组参数构造函数 `new Class(['max' => 100])`

## 实际使用场景

### 用户注册表单

```php
// 用户注册时清理昵称
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

// 处理结果：
// 输入: "  ＵｓｅｒＮａｍｅ１２３！！！  "
// 输出: "UserName123!!"
```

### 文章内容管理

```php
// 文章发布时清理内容
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

// 处理结果：
// 输入: "<p>Hello <script>alert('xss')</script> World</p>"
// 输出: "<p>Hello  World</p>"
```

### 评论系统

```php
// 评论提交时清理内容
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

// 处理结果：
// 输入: "  好文章！！！👍👍👍   "
// 输出: "好文章！！👍👍👍"
```

### 搜索关键词处理

```php
// 搜索时清理关键词
class SearchController extends Controller
{
    public function search(Request $request)
    {
        $keyword = TextGuard::filter($request->input('q', ''), 'safe');

        if (empty($keyword)) {
            return redirect()->back()->with('error', '请输入有效的搜索词');
        }

        $results = $this->searchService->search($keyword);

        return view('search.results', compact('results', 'keyword'));
    }
}

// 处理结果：
// 输入: "  Laravel 框架  "
// 输出: "Laravel 框架"
```

### 批量数据处理

```php
// 批量导入用户数据时清理
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

### 自定义管道步骤

```php
// 创建自定义的敏感词过滤步骤
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

// 注册并使用
TextGuard::registerPipelineStep('sensitive_filter', SensitiveWordFilter::class);

$clean = TextGuard::filter($dirty, 'custom', [
    'sensitive_filter' => ['sensitiveWords' => ['badword1', 'badword2']]
]);
```

## 功能特性

- 空白字符处理（trim、折叠空格）
- 控制字符移除
- 零宽字符移除
- Unicode 规范化
- 全角半角转换
- 标点符号规范化
- HTML 标签处理
- 重复标点折叠
- 可见度检查
- 长度截断
- 可扩展的管道架构
- 运行时注册自定义步骤
- Emoji 支持
- 中文标点符号支持
- 字符白名单过滤

## 测试

```bash
composer test
```

## 代码质量

本项目遵循严格的代码质量标准：

```bash
# 格式化代码
composer fix

# 运行测试
composer test
```

## 许可证

MIT License
