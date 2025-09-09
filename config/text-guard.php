<?php

return [
    // Default preset: suitable for most "normal text input fields"
    'preset' => 'safe',

    // Preset pipelines (executed in order)
    'presets' => [
        // Strict mode: more restrictive filtering
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
                'punct' => true, // Convert all punctuation to halfwidth
            ],
            'html_decode' => true, // Decode HTML entities first
            'strip_html' => true,  // Remove HTML tags
            'character_whitelist' => [
                'enabled' => true,
                'allow_emoji' => false, // Strict mode disallows emoji
                'allow_chinese_punctuation' => true,
                'allow_english_punctuation' => true,
                'emoji_ranges' => [
                    'emoticons' => false,
                    'misc_symbols' => false,
                    'transport_map' => false,
                    'misc_symbols_2' => false,
                    'dingbats' => false,
                ],
            ],
            'visible_ratio_guard' => ['min_ratio' => 0.8],
            'truncate_length' => ['max' => 5000],
        ],

        // Plain text forms: nicknames, titles, comments, etc.
        'safe' => [
            'trim_whitespace' => true,
            'collapse_spaces' => true,
            'remove_control_chars' => true,
            'remove_zero_width' => true,
            'strip_html' => true,
            'visible_ratio_guard' => [
                'min_ratio' => 0.6, // Visible character ratio
            ],
            'truncate_length' => [
                'max' => 5000, // Safety fallback
            ],
        ],

        // Username: more strict
        'username' => [
            'trim_whitespace' => true,
            'collapse_spaces' => true,
            'remove_control_chars' => true,
            'remove_zero_width' => true,
            'unicode_normalization' => 'NFKC',
            'fullwidth_to_halfwidth' => ['ascii' => true, 'digits' => true, 'latin' => true, 'punct' => true],
            'normalize_punctuations' => 'en',
            'strip_html' => true,
            'collapse_repeated_marks' => ['max_repeat' => 1, 'charset' => '_-.'],
            'visible_ratio_guard' => ['min_ratio' => 0.9],
            'truncate_length' => ['max' => 50],
        ],

        // Nickname: example preset with emoji support
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

        // Rich text whitelist (example)
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
    'pipeline_map' => [
        // No parameter constructor
        'trim_whitespace' => \Overtrue\TextGuard\Pipeline\TrimWhitespace::class,
        'collapse_spaces' => \Overtrue\TextGuard\Pipeline\CollapseSpaces::class,
        'remove_control_chars' => \Overtrue\TextGuard\Pipeline\RemoveControlChars::class,
        'remove_zero_width' => \Overtrue\TextGuard\Pipeline\RemoveZeroWidth::class,
        'strip_html' => \Overtrue\TextGuard\Pipeline\StripHtml::class,
        'html_decode' => \Overtrue\TextGuard\Pipeline\HtmlDecode::class,

        // Single parameter constructor
        'unicode_normalization' => \Overtrue\TextGuard\Pipeline\NormalizeUnicode::class,
        'normalize_punctuations' => \Overtrue\TextGuard\Pipeline\NormalizePunctuations::class,

        // Array parameter constructor
        'fullwidth_to_halfwidth' => \Overtrue\TextGuard\Pipeline\FullwidthToHalfwidth::class,
        'whitelist_html' => \Overtrue\TextGuard\Pipeline\WhitelistHtml::class,
        'collapse_repeated_marks' => \Overtrue\TextGuard\Pipeline\CollapseRepeatedMarks::class,
        'visible_ratio_guard' => \Overtrue\TextGuard\Pipeline\VisibleRatioGuard::class,
        'truncate_length' => \Overtrue\TextGuard\Pipeline\TruncateLength::class,
        'character_whitelist' => \Overtrue\TextGuard\Pipeline\CharacterWhitelist::class,

        // You can add more custom pipeline step mappings here
        // 'custom_step' => \App\TextGuard\Pipeline\CustomStep::class,
    ],
];
