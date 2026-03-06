<?php

namespace Overtrue\TextGuard\Pipeline;

use DOMDocument;
use DOMElement;
use DOMNode;

class WhitelistHtml implements PipelineStep
{
    public function __construct(protected array $options = []) {}

    public function __invoke(string $text): string
    {
        $options = array_replace_recursive([
            'tags' => ['p', 'b', 'i', 'u', 'a', 'ul', 'ol', 'li', 'code', 'pre', 'br', 'blockquote', 'h1', 'h2', 'h3'],
            'attrs' => ['href', 'title', 'rel'],
            'protocols' => ['http', 'https', 'mailto'],
        ], $this->options);

        if ($text === '') {
            return $text;
        }

        if (! class_exists(DOMDocument::class)) {
            $allowedTags = '<'.implode('><', $options['tags']).'>';

            return strip_tags($text, $allowedTags);
        }

        return $this->sanitizeHtml(
            $text,
            array_map('strtolower', $options['tags']),
            array_map('strtolower', $options['attrs']),
            array_map('strtolower', $options['protocols'])
        );
    }

    protected function sanitizeHtml(string $html, array $allowedTags, array $allowedAttrs, array $allowedProtocols): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $wrappedHtml = '<div data-textguard-root="1">'.$html.'</div>';

        $internalErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">'.$wrappedHtml,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (! $loaded) {
            return '';
        }

        $root = $dom->documentElement;
        if (! $root instanceof DOMElement) {
            return '';
        }

        $this->sanitizeNodeTree($root, $allowedTags, $allowedAttrs, $allowedProtocols);

        return $this->extractInnerHtml($root);
    }

    protected function sanitizeNodeTree(DOMNode $node, array $allowedTags, array $allowedAttrs, array $allowedProtocols): void
    {
        for ($child = $node->firstChild; $child !== null; $child = $nextChild) {
            $nextChild = $child->nextSibling;

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($child->tagName);
            if (! in_array($tagName, $allowedTags, true)) {
                if (in_array($tagName, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'], true)) {
                    $node->removeChild($child);

                    continue;
                }

                $this->unwrapNode($child);
                $this->sanitizeNodeTree($node, $allowedTags, $allowedAttrs, $allowedProtocols);

                return;
            }

            $this->sanitizeAttributes($child, $allowedAttrs, $allowedProtocols);
            $this->sanitizeNodeTree($child, $allowedTags, $allowedAttrs, $allowedProtocols);
        }
    }

    protected function sanitizeAttributes(DOMElement $element, array $allowedAttrs, array $allowedProtocols): void
    {
        for ($i = $element->attributes->length - 1; $i >= 0; $i--) {
            $attribute = $element->attributes->item($i);
            if ($attribute === null) {
                continue;
            }

            $name = strtolower($attribute->nodeName);
            $value = $attribute->nodeValue ?? '';

            if (! in_array($name, $allowedAttrs, true)) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if (! in_array($name, ['href', 'src', 'action', 'formaction'], true)) {
                continue;
            }

            if (! $this->isSafeUrl($value, $allowedProtocols)) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    protected function isSafeUrl(string $url, array $allowedProtocols): bool
    {
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $candidate = preg_replace('/[\x00-\x20]+/u', '', trim($decoded)) ?? trim($decoded);
        if ($candidate === '') {
            return true;
        }

        $scheme = parse_url($candidate, PHP_URL_SCHEME);
        if (! is_string($scheme) || $scheme === '') {
            return ! preg_match('/^\w+:/', $candidate);
        }

        return in_array(strtolower($scheme), $allowedProtocols, true);
    }

    protected function unwrapNode(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    protected function extractInnerHtml(DOMElement $root): string
    {
        $html = '';
        foreach ($root->childNodes as $child) {
            $fragment = $root->ownerDocument?->saveHTML($child);
            if (is_string($fragment)) {
                $html .= $fragment;
            }
        }

        return $html;
    }
}
