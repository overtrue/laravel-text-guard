# Laravel TextGuard - AI Agent 开发规则

## 项目概述

Laravel TextGuard 是一个用于字符串清洗和规范化的 Laravel 包，提供可配置的文本过滤管道和验证规则。

## 核心架构

### 包结构
```
src/
├── TextGuardServiceProvider.php    # 服务提供者
├── TextGuardManager.php            # 核心服务管理器
├── PipelineFactory.php             # 管道工厂类
├── TextGuard.php                  # Facade
├── Pipeline/                      # 过滤步骤管道
│   ├── PipelineStep.php           # 管道步骤接口
│   ├── TrimWhitespace.php
│   ├── CollapseSpaces.php
│   ├── RemoveControlChars.php
│   ├── RemoveZeroWidth.php
│   ├── NormalizeUnicode.php
│   ├── FullwidthToHalfwidth.php
│   ├── NormalizePunctuations.php
│   ├── StripHtml.php
│   ├── HtmlDecode.php
│   ├── WhitelistHtml.php
│   ├── CollapseRepeatedMarks.php
│   ├── VisibleRatioGuard.php
│   ├── TruncateLength.php
│   └── CharacterWhitelist.php
├── Rules/                         # 验证规则
│   ├── Filtered.php
│   └── Sanitized.php
└── Support/                       # 支持类
    ├── ConfusablesMap.php
    └── Helpers.php
```

### 配置系统
- 配置文件：`config/text-guard.php`
- 级别性预设：`safe`、`strict`
- 功能特定预设：`username`、`nickname`、`rich_text`
- 可配置的过滤步骤和参数
- 管道映射配置：支持动态注册和扩展

## 开发规则

### 1. 代码风格
- 遵循 PSR-12 编码标准
- 使用 PHP 8.2+ 特性
- 所有类必须实现单一职责原则
- Pipeline 类必须实现 `__invoke(string $text): string` 方法

### 2. 命名约定
- 命名空间：`Overtrue\TextGuard`
- 包名：`overtrue/laravel-text-guard`
- 配置文件：`text-guard.php`
- 所有类名使用 PascalCase
- 方法名使用 camelCase

### 3. Pipeline 开发规则
- 每个 Pipeline 类必须实现 `PipelineStep` 接口
- 必须实现 `__invoke(string $text): string` 方法
- 构造函数接收配置数组
- 必须处理边界情况（空字符串、null 等）
- 使用 Unicode 正则表达式处理多语言文本
- 保持函数式编程风格，无副作用

### 4. 验证规则开发规则
- 实现 `Illuminate\Contracts\Validation\ValidationRule` 接口
- 提供清晰的错误消息
- 支持中文错误消息
- 考虑性能影响

### 5. 测试要求
- 每个 Pipeline 类必须有对应的单元测试
- 验证规则必须有测试覆盖
- 测试用例必须覆盖边界情况
- 使用 PHPUnit 进行测试

### 6. 代码质量要求
- **每次代码改动完成后必须执行以下命令**：
  - `composer fix` - 格式化代码，确保符合 PSR-12 标准
  - `composer test` - 运行所有测试，确保功能正常
- 代码提交前必须通过所有测试
- 不允许提交未格式化的代码
- 测试失败时必须修复问题，不能跳过测试

### 7. 文档管理要求
- **每次更新 README 时必须同步更新所有语言版本**：
  - `README.md` - 英文版（默认）
  - `README.zh-CN.md` - 中文版
- 所有语言版本的文档内容必须保持一致
- 新增功能时必须在所有语言版本中添加说明
- 修改示例代码时必须在所有语言版本中同步更新
- 文档结构变更时必须在所有语言版本中保持一致

### 8. 配置管理
- 所有配置项必须有默认值
- 支持预设配置
- 允许运行时覆盖配置
- 配置项必须文档化

## 使用示例

### 基本用法
```php
use Overtrue\TextGuard\Facades\TextGuard;

// 使用默认预设
$clean = TextGuard::filter($dirty);

// 使用指定预设
$clean = TextGuard::filter($dirty, 'username');

// 覆盖配置
$clean = TextGuard::filter($dirty, 'safe', [
    'truncate_length' => ['max' => 100]
]);
```

### 验证规则
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

### FormRequest 集成
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

## 扩展指南

### 添加新的 Pipeline 步骤
1. 在 `src/Pipeline/` 目录创建新类
2. 实现 `PipelineStep` 接口和 `__invoke(string $text): string` 方法
3. 在 `config/text-guard.php` 的 `pipeline_map` 中注册新步骤
4. 编写单元测试
5. 更新配置文件示例

### 构造函数配置语法
```php
// config/text-guard.php
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
```

### 配置传递机制
系统会根据配置类型自动传递给构造函数：
- `true` → 无参数构造函数 `new Class()`
- `'NFKC'` → 单参数构造函数 `new Class('NFKC')`
- `['max' => 100]` → 数组参数构造函数 `new Class(['max' => 100])`

### 运行时注册 Pipeline 步骤
```php
use Overtrue\TextGuard\Facades\TextGuard;

// 注册自定义步骤
TextGuard::registerPipelineStep('custom_step', YourCustomPipeline::class);
```

### 添加新的验证规则
1. 在 `src/Rules/` 目录创建新类
2. 实现 `ValidationRule` 接口
3. 提供清晰的错误消息
4. 编写测试用例

### 添加新的预设配置
1. 在 `config/text-guard.php` 中添加新预设
2. 配置相应的 Pipeline 步骤
3. 更新文档和示例

## 性能考虑

- Pipeline 步骤按顺序执行，考虑性能影响
- 避免在 Pipeline 中进行复杂的字符串操作
- 使用高效的 Unicode 处理函数
- 考虑缓存机制（如需要）

## 安全考虑

- 防止 XSS 攻击
- 处理零宽字符攻击
- 防止同形字符混淆
- 限制输入长度
- 验证可见字符比例

## 国际化支持

- 支持多语言文本处理
- 提供中文错误消息
- 支持不同语言的标点符号规范化
- 处理全角半角字符转换

## 维护指南

- 定期更新依赖包
- 监控性能指标
- 收集用户反馈
- 保持向后兼容性
- 及时修复安全漏洞

## 发布流程

1. 更新版本号
2. 运行 `composer fix` 格式化代码
3. 运行 `composer test` 确保所有测试通过
4. 检查代码风格
5. 同步更新所有语言版本的 README 文档
6. 更新 CHANGELOG
7. 创建 Git 标签
8. 发布到 Packagist

## 故障排除

### 常见问题
1. **Unicode 处理问题**：确保使用 `mb_*` 函数和 Unicode 正则表达式
2. **性能问题**：检查 Pipeline 步骤顺序，避免重复处理
3. **配置问题**：验证配置文件格式和预设定义
4. **测试失败**：检查测试环境配置和依赖

### 调试技巧
- 使用 `dd()` 或 `dump()` 调试 Pipeline 步骤
- 检查中间结果
- 验证配置参数
- 查看错误日志

## 贡献指南

1. Fork 项目
2. 创建功能分支
3. 编写测试
4. 运行 `composer fix` 格式化代码
5. 运行 `composer test` 确保所有测试通过
6. 如果修改了文档，确保同步更新所有语言版本的 README
7. 提交 Pull Request
8. 等待代码审查

## 许可证

MIT License - 详见 LICENSE 文件
