<?php

namespace Overtrue\TextGuard\Pipeline;

class WhitelistHtml implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        $options = array_merge([
            'tags' => ['p', 'b', 'i', 'u', 'a', 'ul', 'ol', 'li', 'code', 'pre', 'br', 'blockquote', 'h1', 'h2', 'h3'],
            'attrs' => ['href', 'title', 'rel'],
            'protocols' => ['http', 'https', 'mailto'],
        ], $this->options);

        // Keep only allowed tags
        $allowedTags = '<'.implode('><', $options['tags']).'>';
        $text = strip_tags($text, $allowedTags);

        // Clean attributes (simplified handling, may need more complex HTML parsing in real projects)
        foreach ($options['attrs'] as $attr) {
            $text = preg_replace_callback(
                '/<'.implode('|', $options['tags']).'[^>]*'.$attr.'="([^"]*)"[^>]*>/i',
                function ($matches) use ($attr, $options) {
                    if (count($matches) < 2) {
                        return $matches[0];
                    }
                    $url = $matches[1];
                    $protocol = parse_url($url, PHP_URL_SCHEME);
                    if ($protocol && in_array($protocol, $options['protocols'])) {
                        return $matches[0];
                    }

                    return str_replace($attr.'="'.$url.'"', '', $matches[0]);
                },
                $text
            );
        }

        return $text;
    }
}
