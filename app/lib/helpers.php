<?php
/**
 * Global helper functions shared by the public site, dashboard and installer.
 */

function cfg(string $key, $default = null)
{
    $c = $GLOBALS['config'] ?? null;
    if (!is_array($c)) {
        return $default;
    }
    foreach (explode('.', $key) as $part) {
        if (!is_array($c) || !array_key_exists($part, $c)) {
            return $default;
        }
        $c = $c[$part];
    }
    return $c;
}

function e($s): string
{
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    return BASE_PATH;
}

/** Site-relative URL, e.g. url('services/x/') => /services/x/ (or /sub/services/x/ in a subfolder install) */
function url(string $path = ''): string
{
    if (preg_match('~^(https?:|mailto:|tel:|sms:|#|//)~i', $path)) {
        return $path;
    }
    return BASE_PATH . '/' . ltrim($path, '/');
}

function site_origin(): string
{
    $configured = rtrim((string)cfg('site_url', ''), '/');
    if ($configured !== '') {
        $p = parse_url($configured);
        return ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : '');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host;
}

function abs_url(string $path = ''): string
{
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return site_origin() . url($path);
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string)filemtime($file), -6) : VERSION;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

/** URL for an uploaded/media path or an absolute URL. */
function media_url(?string $path): string
{
    $path = (string)$path;
    if ($path === '') {
        return '';
    }
    if (preg_match('~^(https?:)?//~i', $path)) {
        return $path;
    }
    // Uploaded files are cached for a year, so version the URL by modification time: a file
    // replaced under the same name (e.g. deleted and re-uploaded) gets a fresh URL everywhere.
    if (str_starts_with($path, 'uploads/') && !str_contains($path, '?')) {
        $mtime = @filemtime(ROOT . '/' . $path);
        if ($mtime) {
            return url($path) . '?v=' . base_convert((string)$mtime, 10, 36);
        }
    }
    return url($path);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function digits(string $s): string
{
    return preg_replace('/\D+/', '', $s);
}

function tel_href(string $phone): string
{
    $d = digits($phone);
    if (strlen($d) === 10) {
        $d = '1' . $d;
    }
    return 'tel:+' . $d;
}

function sms_href(string $phone): string
{
    $d = digits($phone);
    if (strlen($d) === 10) {
        $d = '1' . $d;
    }
    return 'sms:+' . $d;
}

/** Split a newline (or pipe) separated list into trimmed non-empty lines. */
function lines(?string $text): array
{
    $parts = preg_split('/\r\n|\r|\n/', (string)$text);
    return array_values(array_filter(array_map('trim', $parts), fn($l) => $l !== ''));
}

function csv_list(?string $text): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string)$text)), fn($l) => $l !== ''));
}

function json_list(?string $json): array
{
    $d = json_decode((string)$json, true);
    return is_array($d) ? $d : [];
}

function str_limit(string $s, int $n): string
{
    $s = trim(preg_replace('/\s+/', ' ', strip_tags($s)));
    if (mb_strlen($s) <= $n) {
        return $s;
    }
    return rtrim(mb_substr($s, 0, $n - 1), " ,.;:-") . '…';
}

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function redirect(string $to, int $code = 302): void
{
    header('Location: ' . $to, true, $code);
    exit;
}

function setting(string $key, $default = null)
{
    return Settings::get($key, $default);
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 64);
}

function is_bot(): bool
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return $ua === '' || (bool)preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless|lighthouse/i', $ua);
}

function app_key(): string
{
    $k = (string)cfg('app_key', '');
    return $k !== '' ? $k : 'site-fallback-key';
}

function sign(string $data): string
{
    return hash_hmac('sha256', $data, app_key());
}

function render(string $view, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    include APP . '/views/' . $view . '.php';
    return (string)ob_get_clean();
}

function partial(string $name, array $vars = []): void
{
    echo render('partials/' . $name, $vars);
}

function log_error(string $msg): void
{
    @file_put_contents(STORAGE . '/error.log', '[' . date('c') . '] ' . $msg . "\n", FILE_APPEND);
}

/** Inline SVG icon set (stroke icons, 24x24). */
function icon(string $name, string $class = 'icon'): string
{
    static $icons = null;
    if ($icons === null) {
        $icons = require APP . '/lib/icons.php';
    }
    $body = $icons[$name] ?? $icons['circle'];
    $fill = str_starts_with($body, 'FILL:');
    if ($fill) {
        $body = substr($body, 5);
        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/**
 * Responsive image tag. Uses the WebP sibling and stored dimensions from the
 * media library when available to avoid layout shift.
 */
function img(?string $src, string $alt = '', array $attrs = []): string
{
    $src = (string)$src;
    if ($src === '') {
        return '';
    }
    $meta = Media::lookup($src);
    $a = array_merge(['loading' => 'lazy', 'decoding' => 'async'], $attrs);
    if ($meta && empty($a['width']) && $meta['width']) {
        $a['width'] = $meta['width'];
        $a['height'] = $meta['height'];
    }
    if ($alt === '' && $meta) {
        $alt = $meta['alt'];
    }
    $html = '';
    foreach ($a as $k => $v) {
        if ($v === null || $v === false) {
            continue;
        }
        $html .= ' ' . e($k) . '="' . e($v) . '"';
    }
    $tag = '<img src="' . e(media_url($src)) . '" alt="' . e($alt) . '"' . $html . '>';
    if ($meta && $meta['webp'] && $meta['webp'] !== $src) {
        return '<picture><source type="image/webp" srcset="' . e(media_url($meta['webp'])) . '">' . $tag . '</picture>';
    }
    return $tag;
}

function plural(int $n, string $one, string $many): string
{
    return $n . ' ' . ($n === 1 ? $one : $many);
}

function time_ago(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $t = strtotime($dt);
    $d = time() - $t;
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('M j, Y', $t);
}
