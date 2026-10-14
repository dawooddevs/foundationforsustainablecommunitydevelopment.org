<?php
/**
 * Pages built from raw HTML (template "html"): the FSCD pages migrated from Elementor HTML widgets.
 *
 * A page's `content` is an HTML fragment rendered as-is (trusted: only Administrators can edit it),
 * with these tokens replaced at render time:
 *   {{header}}            the shared header (Settings → Header & Footer), with the page's menu item marked active
 *   {{footer}}            the shared footer
 *   {{media:file.jpg}}    URL of the Media Library item with that original file name
 *   {{form_token}}        a fresh spam-protection token for the built-in forms (Forms::token())
 * Unknown tokens are left visible and reported on the dashboard.
 */
final class HtmlPage
{
    public const SHELL_TOP = 'fscd-master-shell-top';
    public const SHELL_BOTTOM = 'fscd-master-shell-bottom';

    /** Which top-level menu item is marked active (class fscd-active) on each page. Override in Settings → Header & Footer. */
    public const NAV_ACTIVE = [
        'home' => 'Home',
        'about-us' => 'About', 'leadership' => 'About', 'dilip-limaye' => 'About',
        'our-programs' => 'Programs', 'eye-care' => 'Programs', 'clean-energy' => 'Programs',
        'global-benefits' => 'Impact', 'changing-lives' => 'Impact',
        'strategic-partners' => 'Partners',
        'contact' => 'Contact',
    ];

    private const TOKEN_RE = '/\{\{\s*([a-z_]+)(?::([^{}\r\n]+?))?\s*\}\}/i';

    private static ?array $media = null;

    /** Render a stored fragment for the page with slug $slug. */
    public static function render(string $content, string $slug = ''): string
    {
        // Header and footer first: they contain media tokens of their own
        $content = str_replace(['{{header}}', '{{footer}}'], [self::header($slug), (string)setting('site_footer_html')], $content);
        return (string)preg_replace_callback(self::TOKEN_RE, function ($m) {
            $name = strtolower($m[1]);
            $arg = isset($m[2]) ? trim($m[2]) : null;
            if ($name === 'media' && $arg !== null && ($path = self::mediaPath($arg)) !== null) {
                return media_url($path);
            }
            if ($name === 'form_token' && $arg === null) {
                return Forms::token();
            }
            return $m[0];
        }, $content);
    }

    /** The shared header with the page's menu item marked active. */
    public static function header(string $slug): string
    {
        $html = (string)setting('site_header_html');
        $label = self::navMap()[$slug] ?? '';
        return $label === '' ? $html : self::markActive($html, $label);
    }

    /**
     * Slug → menu label. Defaults from NAV_ACTIVE, overridden by the "nav_active_map" setting
     * (one "slug = Label" per line; "slug = none" for no active item).
     */
    public static function navMap(): array
    {
        $map = self::NAV_ACTIVE;
        foreach (lines((string)setting('nav_active_map')) as $line) {
            if (!str_contains($line, '=')) continue;
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            if ($k === '') continue;
            $map[slugify($k) ?: $k] = strtolower($v) === 'none' ? '' : $v;
        }
        return $map;
    }

    /** Add fscd-active to the first top-level menu link/button whose text is $label (as each original page did). */
    public static function markActive(string $html, string $label): string
    {
        $done = false;
        return (string)preg_replace_callback('~<(a|button)\b([^>]*?)\bclass="(fscd-nav-link|fscd-nav-trigger)((?:\s[^"]*)?)"([^>]*)>(\s*)([^<]*)~i', function ($m) use ($label, &$done) {
            if ($done || strcasecmp(trim(html_entity_decode($m[7], ENT_QUOTES, 'UTF-8')), $label) !== 0) {
                return $m[0];
            }
            $done = true;
            $classes = $m[3] . $m[4];
            if (!preg_match('/(^|\s)fscd-active(\s|$)/', $classes)) {
                $classes .= ' fscd-active';
            }
            return '<' . $m[1] . $m[2] . 'class="' . $classes . '"' . $m[5] . '>' . $m[6] . $m[7];
        }, $html);
    }

    /** Remove fscd-active markers from menu items (the stored header is neutral; the renderer adds the active one). */
    public static function clearActive(string $html): string
    {
        return (string)preg_replace('~(class="(?:fscd-nav-link|fscd-nav-trigger)[^"]*?)\s+fscd-active\b~', '$1', $html);
    }

    /** Media Library path for an original file name (newest upload wins), or null. */
    public static function mediaPath(string $name): ?string
    {
        if (self::$media === null) {
            self::$media = [];
            try {
                foreach (DB::all('SELECT path, original_name FROM media ORDER BY id') as $r) {
                    self::$media[strtolower((string)$r['original_name'])] = $r['path'];
                    self::$media[strtolower(basename((string)$r['path']))] ??= $r['path'];
                }
            } catch (Throwable $e) {
            }
        }
        $name = strtolower(trim(rawurldecode($name)));
        return self::$media[$name] ?? self::$media[strtolower(basename($name))] ?? null;
    }

    public static function flushMedia(): void
    {
        self::$media = null;
    }

    /** Tokens in $content that would not be replaced (unknown names or missing media). */
    public static function unknownTokens(string $content): array
    {
        $bad = [];
        if (!preg_match_all(self::TOKEN_RE, $content, $ms, PREG_SET_ORDER)) {
            return [];
        }
        foreach ($ms as $m) {
            $name = strtolower($m[1]);
            $arg = isset($m[2]) ? trim($m[2]) : null;
            $ok = ($arg === null && in_array($name, ['header', 'footer', 'form_token'], true))
                || ($name === 'media' && $arg !== null && self::mediaPath($arg) !== null);
            if (!$ok) {
                $bad[$m[0]] = true;
            }
        }
        return array_keys($bad);
    }

    /** Keep the latest 30 revisions of a page's content. */
    public static function saveRevision(int $pageId, string $content, int $userId): void
    {
        DB::insert('page_revisions', ['page_id' => $pageId, 'content' => $content, 'user_id' => $userId ?: null, 'created_at' => now()]);
        $keep = DB::all('SELECT id FROM page_revisions WHERE page_id = ? ORDER BY id DESC LIMIT 30', [$pageId]);
        if (count($keep) === 30) {
            DB::q('DELETE FROM page_revisions WHERE page_id = ? AND id < ?', [$pageId, (int)end($keep)['id']]);
        }
    }

    /**
     * Locate a master-shell block (<div … id="$id">…</div>) by matching nested divs, skipping
     * the contents of scripts, styles and comments. Returns [offset, length] or null.
     */
    public static function findBlock(string $html, string $id): ?array
    {
        if (!preg_match('~<div\b[^>]*\bid=["\']' . preg_quote($id, '~') . '["\'][^>]*>~i', $html, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $start = $m[0][1];
        $depth = 0;
        $re = '~(?<skip><!--.*?-->|<(?<t>script|style)\b[^>]*>.*?</\k<t>\s*>)|<(?<close>/?)div\b[^>]*>~is';
        if (!preg_match_all($re, $html, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL, $start)) {
            return null;
        }
        foreach ($tags as $t) {
            if ($t['skip'][0] !== null) {
                continue; // comment, script or style
            }
            $depth += $t['close'][0] === '/' ? -1 : 1;
            if ($depth === 0) {
                return [$start, $t[0][1] + strlen($t[0][0]) - $start];
            }
        }
        return null;
    }

    /**
     * Replace full header/footer master-shell blocks with {{header}} / {{footer}}. Any <style> or
     * <script> inside a block is page-specific, so it stays in the page just before the token.
     * Returns [html, list of replaced parts, removed block markup keyed by part].
     */
    public static function replaceShells(string $html): array
    {
        $done = [];
        $blocks = [];
        foreach (['header' => self::SHELL_TOP, 'footer' => self::SHELL_BOTTOM] as $token => $id) {
            while (($pos = self::findBlock($html, $id)) !== null) {
                [$off, $len] = $pos;
                $block = substr($html, $off, $len);
                $keep = '';
                if (preg_match_all('~<(style|script)\b[^>]*>.*?</\1\s*>~is', $block, $ms)) {
                    $keep = implode("\n", $ms[0]) . "\n";
                    $block = (string)preg_replace('~[ \t]*\n?[ \t]*<(style|script)\b[^>]*>.*?</\1\s*>~is', '', $block);
                }
                $blocks[$token] ??= $block;
                $html = substr($html, 0, $off) . $keep . '{{' . $token . '}}' . substr($html, $off + $len);
                $done[] = $token;
            }
        }
        return [$html, array_values(array_unique($done)), $blocks];
    }
}
