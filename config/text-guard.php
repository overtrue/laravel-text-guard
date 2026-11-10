<?php

use Overtrue\TextGuard\Pipeline\CharacterWhitelist;
use Overtrue\TextGuard\Pipeline\CollapseRepeatedMarks;
use Overtrue\TextGuard\Pipeline\CollapseSpaces;
use Overtrue\TextGuard\Pipeline\FullwidthToHalfwidth;
use Overtrue\TextGuard\Pipeline\HtmlDecode;
use Overtrue\TextGuard\Pipeline\NormalizePunctuations;
use Overtrue\TextGuard\Pipeline\NormalizeUnicode;
use Overtrue\TextGuard\Pipeline\RemoveBidiControls;
use Overtrue\TextGuard\Pipeline\RemoveControlChars;
use Overtrue\TextGuard\Pipeline\RemoveZeroWidth;
use Overtrue\TextGuard\Pipeline\StripHtml;
use Overtrue\TextGuard\Pipeline\TrimWhitespace;
use Overtrue\TextGuard\Pipeline\TruncateLength;
use Overtrue\TextGuard\Pipeline\VisibleRatioGuard;
use Overtrue\TextGuard\Pipeline\WhitelistHtml;

/**
 * Laravel TextGuard Configuration
 *
 * For detailed pipeline usage and parameters, see:
 * - Pipeline Guide: https://github.com/overtrue/laravel-text-guard/blob/master/PIPELINE_GUIDE.md
 * - Pipeline 指南: https://github.com/overtrue/laravel-text-guard/blob/master/PIPELINE_GUIDE.zh-CN.md
 */

return [
    // Default preset: suitable for most "normal text input fields"
    'preset' => 'safe',

    // Preset pipelines (executed in order)
    // For detailed pipeline usage and parameters, see:
    // - Pipeline Guide: https://github.com/overtrue/laravel-text-guard/blob/master/PIPELINE_GUIDE.md
    // - Pipeline 指南: https://github.com/overtrue/laravel-text-guard/blob/master/PIPELINE_GUIDE.zh-CN.md
    'presets' => [
        // Strict mode: more restrictive filtering
        'strict' => [
            'remove_control_chars' => true,
            'remove_zero_width' => true,
            'unicode_normalization' => 'NFKC',
            'fullwidth_to_halfwidth' => [
                'ascii' => true,
                'digits' => true,
                'latin' => true,
                'punct' => true, // Convert all punctuation to halfwidth
            ],
            'html_decode' => true, // Decode HTML entities first
            'strip_html' => true,  // Remove HTML tags
            'character_whitelist' => [
                'enabled' => true,
                'allow_emoji' => false, // Strict mode disallows emoji
                'allow_chinese_punctuation' => true,
                'allow_english_punctuation' => true,
            ],
            'visible_ratio_guard' => ['min_ratio' => 0.8],
            'collapse_spaces' => true,
            'truncate_length' => ['max' => 5000],
            'trim_whitespace' => true,
        ],

        // Plain text forms: nicknames, titles, comments, etc.
        'safe' => [
            'remove_control_chars' => true,
            'remove_zero_width' => true,
            'strip_html' => true,
            'visible_ratio_guard' => [
                'min_ratio' => 0.6, // Visible character ratio
            ],
            'collapse_spaces' => true,
            'truncate_length' => [
                'max' => 5000, // Safety fallback
            ],
            'trim_whitespace' => true,
        ],

        // Username: more strict
        'username' => [
            'remove_control_chars' => true,
            'remove_zero_width' => true,
            'unicode_normalization' => 'NFKC',
            'fullwidth_to_halfwidth' => ['ascii' => true, 'digits' => true, 'latin' => true, 'punct' => true],
            'normalize_punctuations' => 'en',
            'strip_html' => true,
            'collapse_repeated_marks' => ['max_repeat' => 1, 'charset' => '_-.'],
            'visible_ratio_guard' => ['min_ratio' => 0.9],
            'collapse_spaces' => true,
            'truncate_length' => ['max' => 50],
            'trim_whitespace' => true,
        ],

        // Nickname: example preset with emoji support
        'nickname' => [
            'remove_control_chars' => true,
            'remove_zero_width' => ['preserve_joiners' => true],
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
            ],
            'collapse_spaces' => true,
            'truncate_length' => ['max' => 30, 'unit' => 'grapheme'],
            'trim_whitespace' => true,
        ],

        // Rich text whitelist (example)
        'rich_text' => [
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
            'trim_whitespace' => true,
        ],

        // your custom preset
        // 'your_custom_preset' => [
        //     'trim_whitespace' => true,
        //     'collapse_spaces' => true,
        //     'remove_control_chars' => true,
        //     'remove_zero_width' => true,
        //     // ...
        // ],
    ],

    // Pipeline step mapping configuration
    // For detailed pipeline usage and parameters, see:
    // - Pipeline Guide: https://github.com/overtrue/laravel-text-guard/blob/master/PIPELINE_GUIDE.md
    // - Pipeline 指南: https://github.com/overtrue/laravel-text-guard/blob/master/PIPELINE_GUIDE.zh-CN.md
    'pipeline_map' => [
        // No parameter constructor
        'trim_whitespace' => TrimWhitespace::class,
        'collapse_spaces' => CollapseSpaces::class,
        'remove_control_chars' => RemoveControlChars::class,
        'remove_bidi_controls' => RemoveBidiControls::class,
        'remove_zero_width' => RemoveZeroWidth::class,
        'strip_html' => StripHtml::class,
        'html_decode' => HtmlDecode::class,

        // Single parameter constructor
        'unicode_normalization' => NormalizeUnicode::class,
        'normalize_punctuations' => NormalizePunctuations::class,

        // Array parameter constructor
        'fullwidth_to_halfwidth' => FullwidthToHalfwidth::class,
        'whitelist_html' => WhitelistHtml::class,
        'collapse_repeated_marks' => CollapseRepeatedMarks::class,
        'visible_ratio_guard' => VisibleRatioGuard::class,
        'truncate_length' => TruncateLength::class,
        'character_whitelist' => CharacterWhitelist::class,

        // You can add more custom pipeline step mappings here
        // 'custom_step' => \App\TextGuard\Pipeline\CustomStep::class,
    ],
];
