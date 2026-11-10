<?php

namespace Overtrue\TextGuard\Pipeline;

use InvalidArgumentException;

class CharacterWhitelist implements PipelineStep
{
    public function __construct(protected array $options = [])
    {
        if (array_key_exists('emoji_ranges', $options)) {
            throw new InvalidArgumentException('emoji_ranges is no longer supported; use allow_emoji or a custom pipeline step.');
        }
    }

    public function __invoke(string $text): string
    {
        $defaults = [
            'enabled' => true,
            'allow_emoji' => true,
            'allow_chinese_punctuation' => true,
            'allow_english_punctuation' => true,
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

        $blacklistPattern .= ']';

        $pictograph = '\p{Extended_Pictographic}\x{FE0F}?[\x{1F3FB}-\x{1F3FF}]?';
        $emoji = '/\A(?:'.$pictograph.'(?:\x{200D}'.$pictograph.')*'
            .'|[\x{1F1E6}-\x{1F1FF}]{2}|[0-9#*]\x{FE0F}?\x{20E3}'
            .'|\x{1F3F4}[\x{E0061}-\x{E007A}]+\x{E007F})\z/u';

        return preg_replace_callback('/\X/u', function (array $match) use ($options, $emoji, $blacklistPattern): string {
            if (preg_match($emoji, $match[0]) === 1) {
                return $options['allow_emoji'] ? $match[0] : '';
            }

            return preg_replace('/'.$blacklistPattern.'/ui', '', $match[0]) ?? $match[0];
        }, $text) ?? $text;
    }
}
