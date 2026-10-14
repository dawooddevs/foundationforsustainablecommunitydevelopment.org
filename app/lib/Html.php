<?php
/**
 * Allowlist-based HTML sanitizer for rich text written in the dashboard.
 * Keeps formatting, links, images, tables and trusted video/map embeds.
 */
final class Html
{
    private const TAGS = [
        'p' => ['class'], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'a' => ['href', 'title', 'target', 'rel'], 'ul' => [], 'ol' => ['start'], 'li' => [],
        'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'], 'blockquote' => [], 'hr' => [],
        'img' => ['src', 'alt', 'width', 'height', 'title'], 'figure' => ['class'], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'span' => ['class'], 'div' => ['class'], 'sup' => [], 'sub' => [], 'small' => [], 'mark' => [],
        'iframe' => ['src', 'width', 'height', 'title', 'allow', 'allowfullscreen', 'loading'],
    ];

    private const IFRAME_HOSTS = '~^https://(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com|google\.com/maps|maps\.google\.com)/~i';

    public static function clean(?string $html): string
    {
        $html = trim((string)$html);
        if ($html === '') {
            return '';
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $doc->getElementById('__root') ?: $doc->documentElement;
        if (!$root) {
            return '';
        }
        self::walk($root);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }
            if (!($child instanceof DOMElement)) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'link', 'meta', 'base', 'svg', 'math'], true)) {
                $node->removeChild($child);
                continue;
            }
            if ($tag === 'h1') {
                // Only one H1 per page (the hero) – demote headings in body copy.
                $child = self::rename($child, 'h2');
                $tag = 'h2';
            }
            if (!isset(self::TAGS[$tag])) {
                // Unwrap unknown tags but keep their content
                self::walk($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $allowed = self::TAGS[$tag];
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, $allowed, true)) {
                    $child->removeAttribute($attr->name);
                    continue;
                }
                if (in_array($name, ['href', 'src'], true) && !self::safeUrl($attr->value, $name === 'src')) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($tag === 'iframe') {
                $src = $child->getAttribute('src');
                if (!preg_match(self::IFRAME_HOSTS, $src)) {
                    $node->removeChild($child);
                    continue;
                }
                $child->setAttribute('loading', 'lazy');
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'img') {
                $child->setAttribute('loading', 'lazy');
                $child->setAttribute('decoding', 'async');
            }
            self::walk($child);
        }
    }

    private static function rename(DOMElement $el, string $tag): DOMElement
    {
        $new = $el->ownerDocument->createElement($tag);
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);
        return $new;
    }

    public static function safeUrl(string $url, bool $isSrc = false): bool
    {
        $url = trim(html_entity_decode($url));
        if ($url === '') {
            return false;
        }
        if (preg_match('~^(https?:|mailto:|tel:|sms:|/|#|\.\./|\./|[a-z0-9_\-]+(/|\.|$))~i', $url) && !preg_match('~^\s*(javascript|data|vbscript):~i', $url)) {
            if ($isSrc && preg_match('~^(mailto|tel|sms):~i', $url)) {
                return false;
            }
            return true;
        }
        return false;
    }

    /** Converts plain text (e.g. seed copy) with blank-line paragraphs into HTML. */
    public static function paragraphs(string $text): string
    {
        $text = trim($text);
        if ($text === '' || preg_match('/^\s*</', $text)) {
            return $text;
        }
        $parts = preg_split('/\n\s*\n/', $text);
        return implode("\n", array_map(fn($p) => '<p>' . e(trim($p)) . '</p>', $parts));
    }
}
