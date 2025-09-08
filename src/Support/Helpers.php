<?php

namespace Overtrue\TextGuard\Support;

class Helpers
{
    /**
     * 检查字符串是否包含过多的不可见字符
     */
    public static function hasTooManyInvisibleChars(string $text, float $threshold = 0.5): bool
    {
        $totalLength = mb_strlen($text);
        if ($totalLength === 0) {
            return false;
        }

        $invisibleChars = preg_replace('/[\p{L}\p{N}\p{P}\p{S}\p{Z}]/u', '', $text);
        $invisibleLength = mb_strlen($invisibleChars);

        return ($invisibleLength / $totalLength) > $threshold;
    }

    /**
     * 计算文本的可见字符比例
     */
    public static function getVisibleRatio(string $text): float
    {
        $totalLength = mb_strlen($text);
        if ($totalLength === 0) {
            return 1.0;
        }

        $visibleText = preg_replace('/[\p{C}\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
        $visibleLength = mb_strlen($visibleText);

        return $visibleLength / $totalLength;
    }

    /**
     * 检查文本是否为空或只包含空白字符
     */
    public static function isEmptyOrWhitespace(string $text): bool
    {
        return mb_strlen(trim($text)) === 0;
    }

    /**
     * 获取文本的主要语言（简化版）
     */
    public static function detectLanguage(string $text): string
    {
        $chineseCount = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text);
        $latinCount = preg_match_all('/[a-zA-Z]/u', $text);
        $cyrillicCount = preg_match_all('/[\x{0400}-\x{04ff}]/u', $text);
        $arabicCount = preg_match_all('/[\x{0600}-\x{06ff}]/u', $text);

        $max = max($chineseCount, $latinCount, $cyrillicCount, $arabicCount);

        return match ($max) {
            $chineseCount => 'zh',
            $latinCount => 'en',
            $cyrillicCount => 'ru',
            $arabicCount => 'ar',
            default => 'en'
        };
    }

    /**
     * 安全地截断文本
     */
    public static function safeTruncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - mb_strlen($suffix)).$suffix;
    }

    /**
     * 移除文本中的零宽字符
     */
    public static function removeZeroWidthChars(string $text): string
    {
        return preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $text);
    }

    /**
     * 规范化空白字符
     */
    public static function normalizeWhitespace(string $text): string
    {
        // 移除首尾空白
        $text = trim($text);
        // 将多个连续空白字符替换为单个空格
        $text = preg_replace('/\s+/', ' ', $text);

        return $text;
    }
}
