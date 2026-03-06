<?php

namespace Overtrue\TextGuard\Pipeline;

class CharacterWhitelist implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        $defaults = [
            'enabled' => true,
            'allow_emoji' => true,
            'allow_chinese_punctuation' => true,
            'allow_english_punctuation' => true,
            'emoji_ranges' => [
                'emoticons' => true,        // [\x{1F600}-\x{1F64F}]
                'misc_symbols' => true,     // [\x{1F300}-\x{1F5FF}]
                'transport_map' => true,    // [\x{1F680}-\x{1F6FF}]
                'misc_symbols_2' => true,   // [\x{2600}-\x{26FF}]
                'dingbats' => true,         // [\x{2700}-\x{27BF}]
            ],
        ];
        $options = array_replace_recursive($defaults, $this->options);

        if (! (bool) $options['enabled']) {
            return $text;
        }

        // Build whitelist pattern - match characters that should be REMOVED
        $blacklistPattern = '[^';

        // Always allow word characters, Chinese characters, and spaces
        $blacklistPattern .= '\w\p{Han}\s';

        // Add Chinese punctuation if enabled
        if ((bool) $options['allow_chinese_punctuation']) {
            $chinesePunctuation = preg_quote('。、！？：；﹑•＂…\'\'""〝〞¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）', '/');
            $blacklistPattern .= $chinesePunctuation;
        }

        // Add English punctuation if enabled
        if ((bool) $options['allow_english_punctuation']) {
            $blacklistPattern .= '\`\~\!\@\#\$\%\^\&\*\(\)\_\+\-\=\[\]\{\}\\\|\;\'\'\:\"\"\,\.\/\>\?';
        }

        // Add emoji ranges if enabled
        if ((bool) $options['allow_emoji']) {
            if ((bool) $options['emoji_ranges']['emoticons']) {
                $blacklistPattern .= '\x{1F600}-\x{1F64F}';
            }

            if ((bool) $options['emoji_ranges']['misc_symbols']) {
                $blacklistPattern .= '\x{1F300}-\x{1F5FF}';
            }

            if ((bool) $options['emoji_ranges']['transport_map']) {
                $blacklistPattern .= '\x{1F680}-\x{1F6FF}';
            }

            if ((bool) $options['emoji_ranges']['misc_symbols_2']) {
                $blacklistPattern .= '\x{2600}-\x{26FF}';
            }

            if ((bool) $options['emoji_ranges']['dingbats']) {
                $blacklistPattern .= '\x{2700}-\x{27BF}';
            }
        }

        $blacklistPattern .= ']';

        // Remove characters not in whitelist
        return preg_replace('/'.$blacklistPattern.'/ui', '', $text) ?? $text;
    }
}
