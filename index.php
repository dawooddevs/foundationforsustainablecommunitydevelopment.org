<?php
/**
 * Public front controller.
 * Every request that is not a real file is routed through here (see .htaccess).
 */

// Built-in PHP dev server: serve real files and folder index files directly.
if (PHP_SAPI === 'cli-server') {
    $p = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    $f = __DIR__ . $p;
    if (!str_contains($p, '..') && !preg_match('~^/(app|scripts)(/|$)~', $p)) {
        if (is_file($f)) {
            return false;
        }
        if (is_dir($f) && is_file(rtrim($f, '/') . '/index.php') && $p !== '/') {
            if (!str_ends_with($p, '/')) {
                header('Location: ' . $p . '/', true, 301);
                exit;
            }
            $_SERVER['SCRIPT_NAME'] = rtrim($p, '/') . '/index.php';
            $_SERVER['SCRIPT_FILENAME'] = rtrim($f, '/') . '/index.php';
            chdir(dirname($_SERVER['SCRIPT_FILENAME']));
            require $_SERVER['SCRIPT_FILENAME'];
            exit;
        }
    }
}

require __DIR__ . '/app/bootstrap.php';
require APP . '/lib/Forms.php';

if (!is_installed()) {
    redirect(url('install/'));
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$path = '/' . ltrim(BASE_PATH !== '' && str_starts_with($uri, BASE_PATH) ? substr($uri, strlen(BASE_PATH)) : $uri, '/');
$query = $_SERVER['QUERY_STRING'] ?? '';

// 1. Managed redirects (Dashboard → Redirects)
$variants = array_unique([$path, rtrim($path, '/'), rtrim($path, '/') . '/']);
$ph = implode(',', array_fill(0, count($variants), '?'));
if ($r = DB::one("SELECT * FROM redirects WHERE source IN ($ph) LIMIT 1", array_values($variants))) {
    DB::q('UPDATE redirects SET hits = hits + 1 WHERE id = ?', [$r['id']]);
    $target = preg_match('~^https?://~', $r['target']) ? $r['target'] : url($r['target']);
    redirect($target . ($query !== '' ? (str_contains($target, '?') ? '&' : '?') . $query : ''), (int)$r['code'] ?: 301);
}

// 2. Machine routes
if ($path === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    if (setting('seo_noindex') === '1') {
        echo "User-agent: *\nDisallow: /\n";
    } else {
        echo "User-agent: *\nDisallow: /admin/\nDisallow: /install/\nDisallow: /api/\n\nSitemap: " . abs_url('sitemap.xml') . "\n";
    }
    exit;
}
if ($path === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    echo render('sitemap-xml');
    exit;
}
if ($path === '/api/form') {
    Forms::handle();
}
if ($path === '/api/treatments') {
    // Homepage treatment explorer: rendered cards for one tab, a page at a time ("Show More")
    $cat = (string)($_GET['category'] ?? 'featured');
    header('Content-Type: application/json; charset=utf-8');
    if ($cat !== 'featured' && !isset(Content::CATEGORIES[$cat])) {
        http_response_code(404);
        echo json_encode(['error' => 'Unknown category']);
        exit;
    }
    $items = Content::explorerServices($cat);
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $limit = (int)($_GET['limit'] ?? 3);
    $slice = array_slice($items, $offset, $limit > 0 ? $limit : null);
    $html = '';
    foreach ($slice as $s) {
        $html .= render('partials/service-card', ['s' => $s]);
    }
    header('Cache-Control: public, max-age=300');
    echo json_encode(['html' => $html, 'total' => count($items), 'next' => $offset + count($slice)]);
    exit;
}

// 3. Enforce trailing slash on page URLs (consistent URL format)
if ($path !== '/' && !str_ends_with($path, '/') && !preg_match('~\.[a-z0-9]{2,5}$~i', $path)) {
    redirect(url($path . '/') . ($query !== '' ? '?' . $query : ''), 301);
}

// 4. Content routes
$segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
$view = null;
$vars = [];
$status = 200;

if (!$segments) {
    $view = 'pages/home';
} elseif ($segments[0] === Content::SERVICE_BASE && count($segments) === 2) {
    if ($s = Content::service($segments[1])) {
        $view = 'pages/service';
        $vars['service'] = $s;
    }
} elseif ($segments[0] === Content::PROVIDER_BASE && count($segments) === 2) {
    if ($p = Content::provider($segments[1])) {
        $view = 'pages/provider';
        $vars['provider'] = $p;
    }
} elseif (count($segments) === 1 && preg_match('/^[a-z0-9\-]+$/', $segments[0])) {
    if ($page = Content::page($segments[0])) {
        $templates = ['treatments', 'appointment', 'contact', 'sitemap', 'legal'];
        $view = 'pages/' . (in_array($page['template'], $templates, true) ? $page['template'] : 'page');
        $vars['page'] = $page;
    }
}

// Preview drafts for logged-in dashboard users
if (!$view && isset($_GET['preview']) && $segments) {
    Auth::start();
    if (Auth::user() && Auth::can('content.view')) {
        if ($segments[0] === Content::SERVICE_BASE && isset($segments[1]) && ($s = DB::one('SELECT * FROM services WHERE slug = ?', [$segments[1]]))) {
            $view = 'pages/service';
            $vars['service'] = $s;
        } elseif ($segments[0] === Content::PROVIDER_BASE && isset($segments[1]) && ($p = DB::one('SELECT * FROM providers WHERE slug = ?', [$segments[1]]))) {
            $view = 'pages/provider';
            $vars['provider'] = $p;
        } elseif ($page = DB::one('SELECT * FROM pages WHERE slug = ?', [$segments[0]])) {
            $view = 'pages/page';
            $vars['page'] = $page;
        }
        Seo::$noindex = true;
    }
}

if (!$view) {
    $status = 404;
    $view = 'pages/404';
}

http_response_code($status);
$vars['path'] = $path;
$content = render($view, $vars);
header('Content-Type: text/html; charset=utf-8');
echo render('layout', ['content' => $content, 'path' => $path]);

// Lightweight, cookie-free page view counter for the dashboard
if ($status === 200 && !is_bot() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_GET['preview'])) {
    try {
        $day = date('Y-m-d');
        $n = DB::q('UPDATE pageviews SET views = views + 1 WHERE day = ? AND path = ?', [$day, $path])->rowCount();
        if ($n === 0) {
            DB::insert('pageviews', ['day' => $day, 'path' => mb_substr($path, 0, 250), 'views' => 1]);
        }
    } catch (Throwable $e) {
        // Never break a page for analytics
    }
}
