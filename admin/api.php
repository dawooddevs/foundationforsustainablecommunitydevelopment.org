<?php
/**
 * Dashboard JSON API. Every call is AJAX:  POST/GET admin/api.php?r=<route>
 * State-changing requests require the X-CSRF-Token header.
 */
require dirname(__DIR__) . '/app/bootstrap.php';
require APP . '/lib/Resources.php';
require APP . '/lib/Forms.php';
require APP . '/lib/HtmlPage.php';

if (!is_installed()) {
    json_out(['ok' => false, 'error' => 'Not installed'], 503);
}

Auth::start();
$route = (string)($_GET['r'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$raw = file_get_contents('php://input');
$in = [];
if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $in = json_decode($raw ?: '[]', true) ?: [];
} else {
    $in = $_POST;
}
$in = array_merge($_GET, $in);

function ok($data = []): void
{
    json_out(['ok' => true, 'data' => $data]);
}

function fail(string $msg, int $code = 400): void
{
    json_out(['ok' => false, 'error' => $msg], $code);
}

function need(string $cap): array
{
    $u = Auth::user();
    if (!$u) {
        fail('Your session has expired. Please sign in again.', 401);
    }
    if (!Auth::can($cap)) {
        fail('You do not have permission to do that.', 403);
    }
    return $u;
}

/** Raw HTML pages, the header/footer and code settings are for Administrators only (BRIEF §8). */
function is_admin(): bool
{
    $u = Auth::user();
    return $u && $u['role'] === 'admin';
}

function is_html_page(?array $row, array $data = []): bool
{
    return ($data['template'] ?? '') === 'html' || ($row['template'] ?? '') === 'html';
}

function need_admin_for_html(?array $row, array $data = []): void
{
    if (is_html_page($row, $data) && !is_admin()) {
        fail('Only administrators can change raw-HTML pages.', 403);
    }
}

function resource(string $type): array
{
    $def = Resources::get($type);
    if (!$def) {
        fail('Unknown content type.', 404);
    }
    return $def;
}

// CSRF for every state-changing request except login
if ($method === 'POST' && $route !== 'auth.login' && !Auth::checkCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['_csrf'] ?? null))) {
    fail('Security token expired. Please reload the page.', 419);
}
if ($method !== 'POST' && !in_array($route, ['auth.me', 'schema', 'dashboard', 'page.revisions', 'page.revision', 'res.list', 'res.get', 'res.options', 'media.list', 'submissions.list', 'submissions.get', 'submissions.export', 'users.list', 'settings.get', 'activity.list', 'search', 'system.info', 'backup.export'], true)) {
    fail('Method not allowed.', 405);
}

try {
    switch ($route) {

        // ------------------------------------------------------------ Auth
        case 'auth.me':
            $u = Auth::user();
            ok(['user' => $u ? Auth::publicUser($u) : null, 'csrf' => Auth::csrf()]);

        case 'auth.login':
            $res = Auth::attempt(trim((string)($in['login'] ?? '')), (string)($in['password'] ?? ''));
            if (!$res['ok']) {
                fail($res['error'], 422);
            }
            ok(['user' => $res['user'], 'csrf' => Auth::csrf()]);

        case 'auth.logout':
            if ($u = Auth::user()) {
                Auth::log('logout', 'user', (int)$u['id'], $u['name']);
            }
            Auth::logout();
            ok();

        // ------------------------------------------------------------ Schema
        case 'schema':
            need('content.view');
            $defs = [];
            foreach (Resources::all() as $k => $d) {
                unset($d['table']);
                $defs[$k] = $d;
            }
            $roles = [];
            foreach (Auth::ROLES as $k => $r) {
                $roles[] = ['value' => $k, 'label' => $r['label'], 'description' => $r['description']];
            }
            ok([
                'resources' => $defs,
                'settings' => Resources::settings(),
                'roles' => $roles,
                'site' => ['name' => setting('site_name'), 'short' => setting('site_short_name'), 'url' => url(''), 'abs' => abs_url(''), 'noindex' => setting('seo_noindex') === '1'],
                'upload_max' => min(Media::MAX_BYTES, self_ini_bytes('upload_max_filesize'), self_ini_bytes('post_max_size')),
                'extensions' => Media::allowedExtensions(),
            ]);

        // ------------------------------------------------------------ Dashboard
        case 'dashboard':
            need('content.view');
            $days = [];
            for ($i = 29; $i >= 0; $i--) {
                $days[date('Y-m-d', strtotime("-$i days"))] = 0;
            }
            $from = array_key_first($days);
            foreach (DB::all('SELECT day, SUM(views) AS v FROM pageviews WHERE day >= ? GROUP BY day', [$from]) as $r) {
                if (isset($days[$r['day']])) $days[$r['day']] = (int)$r['v'];
            }
            $prevFrom = date('Y-m-d', strtotime('-59 days'));
            $prev = (int)DB::val('SELECT COALESCE(SUM(views),0) FROM pageviews WHERE day >= ? AND day < ?', [$prevFrom, $from]);
            $top = DB::all('SELECT path, SUM(views) AS v FROM pageviews WHERE day >= ? GROUP BY path ORDER BY v DESC LIMIT 8', [$from]);
            $since7 = date('Y-m-d H:i:s', strtotime('-7 days'));
            $review = [];
            foreach (['services' => 'title', 'providers' => 'name', 'pages' => 'title'] as $t => $col) {
                if (!empty(Resources::get($t)['hidden'])) continue;
                foreach (DB::all("SELECT id, $col AS title, status FROM $t WHERE needs_review = 1 ORDER BY sort_order, id LIMIT 50") as $r) {
                    $review[] = ['type' => $t, 'id' => (int)$r['id'], 'title' => $r['title'], 'status' => $r['status']];
                }
            }
            $health = [];
            // Raw-HTML pages: tokens that would show as text, and files still loaded from the old WordPress site
            foreach (DB::all("SELECT id, title, content FROM pages WHERE template = 'html'") as $pg) {
                $bad = HtmlPage::unknownTokens((string)$pg['content']);
                if ($bad) $health[] = ['level' => 'warn', 'text' => '"' . $pg['title'] . '" has unknown or missing tokens: ' . implode(', ', array_slice($bad, 0, 5)), 'link' => '#/pages/' . $pg['id']];
                if (preg_match('~(?:src|href)="[^"]*/wp-content/uploads/fscd/fscd-eye\.(?:css|js)~', (string)$pg['content'])) {
                    $health[] = ['level' => 'warn', 'text' => '"' . $pg['title'] . '" still links fscd-eye.css/js from the old site: add the files to app/seed/fscd/eye-care/ and run php app/cli/import-fscd.php --force', 'link' => '#/pages/' . $pg['id']];
                }
            }
            foreach (['site_header_html' => 'header', 'site_footer_html' => 'footer'] as $k => $label) {
                if (trim((string)setting($k)) === '') $health[] = ['level' => 'warn', 'text' => "The shared $label is empty", 'link' => '#/settings/header_footer'];
                elseif ($bad = HtmlPage::unknownTokens((string)setting($k))) $health[] = ['level' => 'warn', 'text' => "The shared $label has unknown or missing tokens: " . implode(', ', $bad), 'link' => '#/settings/header_footer'];
            }
            foreach (json_list((string)setting('fscd_import_warnings')) as $w) {
                $health[] = ['level' => 'warn', 'text' => 'Import: ' . $w, 'link' => '#/pages'];
            }
            if (!trim((string)setting('notify_email'))) $health[] = ['level' => 'warn', 'text' => 'No form notification email is set', 'link' => '#/settings/general'];
            if (setting('seo_noindex') === '1') $health[] = ['level' => 'info', 'text' => 'Search engines are blocked (staging mode)', 'link' => '#/settings/seo'];

            $data = [
                'counts' => [
                    'services' => (int)DB::val("SELECT COUNT(*) FROM services WHERE status='published'"),
                    'providers' => (int)DB::val("SELECT COUNT(*) FROM providers WHERE status='published'"),
                    'pages' => (int)DB::val("SELECT COUNT(*) FROM pages WHERE status='published'"),
                    'media' => (int)DB::val('SELECT COUNT(*) FROM media'),
                    'drafts' => (int)DB::val("SELECT (SELECT COUNT(*) FROM services WHERE status='draft') + (SELECT COUNT(*) FROM pages WHERE status='draft') + (SELECT COUNT(*) FROM providers WHERE status='draft')"),
                    'review' => count($review),
                ],
                'views' => ['days' => array_map(fn($d, $v) => ['day' => $d, 'v' => $v], array_keys($days), array_values($days)), 'total' => array_sum($days), 'prev' => $prev],
                'top' => array_map(fn($r) => ['path' => $r['path'], 'v' => (int)$r['v']], $top),
                'review' => $review,
                'health' => $health,
            ];
            if (Auth::can('submissions.view')) {
                $data['counts']['new_submissions'] = (int)DB::val("SELECT COUNT(*) FROM submissions WHERE status = 'new'");
                $data['counts']['week_submissions'] = (int)DB::val('SELECT COUNT(*) FROM submissions WHERE created_at >= ?', [$since7]);
                $data['submissions'] = array_map('present_submission', DB::all('SELECT * FROM submissions ORDER BY id DESC LIMIT 6'));
            }
            $data['activity'] = DB::all('SELECT * FROM activity ORDER BY id DESC LIMIT 10');
            ok($data);

        // ------------------------------------------------------------ Generic content
        case 'res.list':
            need('content.view');
            $def = resource((string)($in['type'] ?? ''));
            if (!empty($def['cap'])) need($def['cap']);
            $t = $def['table'];
            $where = ['1=1'];
            $p = [];
            $q = trim((string)($in['q'] ?? ''));
            if ($q !== '') {
                $parts = [];
                foreach ($def['search'] as $col) {
                    $parts[] = DB::ident($col) . ' LIKE ?';
                    $p[] = '%' . $q . '%';
                }
                $where[] = '(' . implode(' OR ', $parts) . ')';
            }
            foreach ($def['filters'] as $col => $opts) {
                $v = (string)($in['f_' . $col] ?? '');
                if ($v !== '' && in_array($v, array_column($opts, 'value'), true)) {
                    $where[] = DB::ident($col) . ' = ?';
                    $p[] = $v;
                }
            }
            if (!empty($in['review']) && !empty($def['review'])) {
                $where[] = 'needs_review = 1';
            }
            $per = max(5, min(200, (int)($in['per'] ?? 50)));
            $page = max(1, (int)($in['page'] ?? 1));
            $total = (int)DB::val("SELECT COUNT(*) FROM $t WHERE " . implode(' AND ', $where), $p);
            $order = !empty($def['orderable']) ? 'sort_order, id' : ($t === 'redirects' ? 'id DESC' : 'id');
            if ($t === 'pages') $order = 'is_system DESC, sort_order, id';
            $rows = DB::all("SELECT * FROM $t WHERE " . implode(' AND ', $where) . " ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $p);
            $items = [];
            foreach ($rows as $r) {
                $item = ['id' => (int)$r['id']];
                foreach ($def['columns'] as $c) {
                    $item[$c['key']] = $r[$c['key']] ?? null;
                }
                if (isset($r['needs_review'])) $item['_review'] = (int)$r['needs_review'] === 1;
                if (isset($r['is_system'])) $item['_system'] = (int)$r['is_system'] === 1;
                if ($t === 'pages' && $r['slug'] === 'home') {
                    $item['_url'] = url('');
                } elseif (!empty($def['url']) && !empty($r['slug'])) {
                    $item['_url'] = url(str_replace('{slug}', $r['slug'], $def['url']));
                }
                if ($t === 'pages') $item['_html'] = ($r['template'] ?? '') === 'html';
                if (isset($item['photo'])) $item['photo'] = $item['photo'] ? media_url($item['photo']) : '';
                $items[] = $item;
            }
            ok(['items' => $items, 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $per), 'per' => $per]);

        case 'res.options':
            need('content.view');
            $def = resource((string)($in['type'] ?? ''));
            $title = $def['titleKey'];
            $slugCol = !empty($def['slug']) ? 'slug' : 'id';
            $rows = DB::all("SELECT $slugCol AS value, $title AS label" . ($def['table'] === 'services' ? ', category' : '') . " FROM {$def['table']} ORDER BY " . (!empty($def['orderable']) ? 'sort_order, ' : '') . "$title");
            ok($rows);

        case 'res.get':
            need('content.view');
            $def = resource((string)($in['type'] ?? ''));
            if (!empty($def['cap'])) need($def['cap']);
            $row = DB::one("SELECT * FROM {$def['table']} WHERE id = ?", [(int)($in['id'] ?? 0)]);
            if (!$row) fail('Not found.', 404);
            ok(Resources::present($def, $row));

        case 'res.save':
            $u = need('content.edit');
            $type = (string)($in['type'] ?? '');
            $def = resource($type);
            if (!empty($def['cap'])) $u = need($def['cap']);
            $t = $def['table'];
            $id = (int)($in['id'] ?? 0);
            $data = (array)($in['data'] ?? []);
            $existing = $id ? DB::one("SELECT * FROM $t WHERE id = ?", [$id]) : null;
            if ($id && !$existing) fail('This item no longer exists.', 404);
            $canPublish = Auth::can('content.publish');
            if ($type === 'pages') {
                need_admin_for_html($existing, $data);
            }
            if (!$canPublish && $existing && ($existing['status'] ?? '') === 'published') {
                fail('Only editors and administrators can change published content. Ask an editor to unpublish it first.', 403);
            }
            $row = [];
            $errors = [];
            foreach ($def['fields'] as $f) {
                if (!array_key_exists($f['key'], $data)) {
                    if (!$existing && isset($f['default'])) {
                        $row[$f['key']] = Resources::clean($f, $f['default']);
                    }
                    continue;
                }
                $v = Resources::clean($f, $data[$f['key']]);
                if (!empty($f['required']) && ($v === '' || $v === null)) {
                    $errors[$f['key']] = $f['label'] . ' is required.';
                }
                $row[$f['key']] = $v;
            }
            if ($errors) {
                json_out(['ok' => false, 'error' => reset($errors), 'fields' => $errors], 422);
            }
            // Raw-HTML pages store their HTML as typed (Administrators only, checked above)
            $notice = '';
            if ($type === 'pages' && is_html_page($existing, $row) && array_key_exists('content', $data)) {
                $row['content'] = str_replace("\r\n", "\n", (string)$data['content']);
                [$fixed, $replaced] = HtmlPage::replaceShells($row['content']);
                if ($replaced) {
                    $row['content'] = $fixed;
                    $notice = 'The pasted ' . implode(' and ', $replaced) . ' markup was replaced with ' . implode(' and ', array_map(fn($x) => '{{' . $x . '}}', $replaced)) . '. Edit the shared version in Settings → Header & Footer.';
                }
            }
            if ($type === 'pages' && $existing && isset($row['content']) && (string)$existing['content'] !== (string)$row['content']) {
                HtmlPage::saveRevision($id, (string)$existing['content'], (int)$u['id']);
            }
            if (isset($row['status']) && !$canPublish) {
                $row['status'] = 'draft';
            }
            // Slugs: auto-generate, keep unique, lock for system pages
            if (!empty($def['slug'])) {
                if ($existing && !empty($existing['is_system'])) {
                    $row['slug'] = $existing['slug'];
                } else {
                    $base = $row['slug'] ?? ($existing['slug'] ?? '');
                    if ($base === '') {
                        $base = slugify((string)($row[$def['titleKey']] ?? $existing[$def['titleKey']] ?? 'item'));
                    }
                    $slug = $base ?: 'item';
                    $n = 1;
                    while (DB::val("SELECT id FROM $t WHERE slug = ? AND id <> ?", [$slug, $id])) {
                        $slug = $base . '-' . (++$n);
                    }
                    $row['slug'] = $slug;
                }
            }
            if ($type === 'redirects') {
                $src = '/' . trim(parse_url((string)$row['source'], PHP_URL_PATH) ?: '', '/');
                $row['source'] = $src === '/' ? '/' : $src . '/';
                if ($row['source'] === '/') fail('The homepage cannot be redirected.', 422);
                if (DB::val('SELECT id FROM redirects WHERE source = ? AND id <> ?', [$row['source'], $id])) fail('A redirect for that path already exists.', 422);
                $row['code'] = (int)($row['code'] ?? 301);
            }
            $stamp = now();
            $row['updated_at'] = $stamp;
            if (in_array($t, ['services', 'providers', 'pages'], true)) {
                $row['updated_by'] = (int)$u['id'];
            }
            if ($existing) {
                DB::update($t, $row, 'id = ?', [$id]);
                Auth::log('updated', $type, $id, (string)($row[$def['titleKey']] ?? $existing[$def['titleKey']]));
            } else {
                $row['created_at'] = $stamp;
                if (!empty($def['orderable'])) {
                    $row['sort_order'] = (int)DB::val("SELECT COALESCE(MAX(sort_order),0) FROM $t") + 10;
                }
                $id = DB::insert($t, $row);
                Auth::log('created', $type, $id, (string)($row[$def['titleKey']] ?? ''));
            }
            ok(Resources::present($def, DB::one("SELECT * FROM $t WHERE id = ?", [$id])) + ($notice !== '' ? ['_notice' => $notice] : []));

        // ------------------------------------------------------------ Page revisions (raw HTML needs an undo)
        case 'page.revisions':
            need('content.view');
            $rows = DB::all('SELECT r.id, r.created_at, LENGTH(r.content) AS size, u.name AS user_name, u.username FROM page_revisions r LEFT JOIN users u ON u.id = r.user_id WHERE r.page_id = ? ORDER BY r.id DESC LIMIT 30', [(int)($in['id'] ?? 0)]);
            ok(array_map(fn($r) => ['id' => (int)$r['id'], 'created_at' => $r['created_at'], 'size' => (int)$r['size'], 'user' => $r['user_name'] ?: ($r['username'] ?: 'Import')], $rows));

        case 'page.revision':
            need('content.view');
            $rev = DB::one('SELECT * FROM page_revisions WHERE id = ?', [(int)($in['id'] ?? 0)]);
            if (!$rev) fail('Revision not found.', 404);
            ok(['id' => (int)$rev['id'], 'page_id' => (int)$rev['page_id'], 'content' => $rev['content'], 'created_at' => $rev['created_at']]);

        case 'page.restore':
            $u = need('content.edit');
            $rev = DB::one('SELECT * FROM page_revisions WHERE id = ?', [(int)($in['id'] ?? 0)]);
            if (!$rev) fail('Revision not found.', 404);
            $pg = DB::one('SELECT * FROM pages WHERE id = ?', [(int)$rev['page_id']]);
            if (!$pg) fail('Page not found.', 404);
            need_admin_for_html($pg);
            if (!Auth::can('content.publish') && $pg['status'] === 'published') fail('Only editors and administrators can change published content.', 403);
            HtmlPage::saveRevision((int)$pg['id'], (string)$pg['content'], (int)$u['id']);
            DB::update('pages', ['content' => $rev['content'], 'updated_at' => now(), 'updated_by' => (int)$u['id']], 'id = ?', [$pg['id']]);
            Auth::log('restored revision', 'pages', (int)$pg['id'], $pg['title']);
            ok(['restored' => (int)$rev['id']]);

        case 'res.delete':
            need('content.delete');
            $type = (string)($in['type'] ?? '');
            $def = resource($type);
            if (!empty($def['cap'])) need($def['cap']);
            $ids = array_map('intval', (array)($in['ids'] ?? []));
            $n = 0;
            foreach ($ids as $id) {
                $row = DB::one("SELECT * FROM {$def['table']} WHERE id = ?", [$id]);
                if (!$row) continue;
                if (!empty($row['is_system'])) fail('"' . $row[$def['titleKey']] . '" is a core page and cannot be deleted. Set it to Draft instead.', 422);
                if ($type === 'pages') need_admin_for_html($row);
                DB::delete($def['table'], 'id = ?', [$id]);
                Auth::log('deleted', $type, $id, (string)$row[$def['titleKey']]);
                $n++;
            }
            ok(['deleted' => $n]);

        case 'res.status':
            need('content.publish');
            $type = (string)($in['type'] ?? '');
            $def = resource($type);
            $status = ($in['status'] ?? '') === 'published' ? 'published' : 'draft';
            if ($type === 'pages') {
                foreach (array_map('intval', (array)($in['ids'] ?? [])) as $id) {
                    need_admin_for_html(DB::one('SELECT template FROM pages WHERE id = ?', [$id]));
                }
            }
            foreach (array_map('intval', (array)($in['ids'] ?? [])) as $id) {
                DB::update($def['table'], ['status' => $status, 'updated_at' => now()], 'id = ?', [$id]);
                Auth::log($status === 'published' ? 'published' : 'unpublished', $type, $id, (string)DB::val("SELECT {$def['titleKey']} FROM {$def['table']} WHERE id = ?", [$id]));
            }
            ok();

        case 'res.reorder':
            need('content.edit');
            $def = resource((string)($in['type'] ?? ''));
            if (empty($def['orderable'])) fail('This list cannot be reordered.');
            DB::tx(function () use ($def, $in) {
                foreach (array_values((array)($in['ids'] ?? [])) as $i => $id) {
                    DB::update($def['table'], ['sort_order' => ($i + 1) * 10], 'id = ?', [(int)$id]);
                }
            });
            ok();

        case 'res.duplicate':
            need('content.edit');
            $type = (string)($in['type'] ?? '');
            $def = resource($type);
            $row = DB::one("SELECT * FROM {$def['table']} WHERE id = ?", [(int)($in['id'] ?? 0)]);
            if (!$row) fail('Not found.', 404);
            if ($type === 'pages') need_admin_for_html($row);
            unset($row['id']);
            $row[$def['titleKey']] .= ' (copy)';
            if (!empty($def['slug'])) {
                $base = $row['slug'] . '-copy';
                $slug = $base;
                $n = 1;
                while (DB::val("SELECT id FROM {$def['table']} WHERE slug = ?", [$slug])) $slug = $base . '-' . (++$n);
                $row['slug'] = $slug;
            }
            if (isset($row['status'])) $row['status'] = 'draft';
            if (isset($row['is_system'])) $row['is_system'] = 0;
            if (isset($row['hits'])) $row['hits'] = 0;
            $row['created_at'] = $row['updated_at'] = now();
            $id = DB::insert($def['table'], $row);
            Auth::log('duplicated', $type, $id, $row[$def['titleKey']]);
            ok(['id' => $id]);

        // ------------------------------------------------------------ Media library
        case 'media.list':
            need('media.view');
            $where = ['1=1'];
            $p = [];
            if (($q = trim((string)($in['q'] ?? ''))) !== '') {
                $where[] = '(original_name LIKE ? OR title LIKE ? OR alt LIKE ?)';
                array_push($p, "%$q%", "%$q%", "%$q%");
            }
            if (in_array($in['kind'] ?? '', ['image', 'document', 'video'], true)) {
                $where[] = 'kind = ?';
                $p[] = $in['kind'];
            }
            $per = max(12, min(200, (int)($in['per'] ?? 60)));
            $page = max(1, (int)($in['page'] ?? 1));
            $total = (int)DB::val('SELECT COUNT(*) FROM media WHERE ' . implode(' AND ', $where), $p);
            $rows = DB::all('SELECT * FROM media WHERE ' . implode(' AND ', $where) . " ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
            $bytes = (int)DB::val('SELECT COALESCE(SUM(size),0) FROM media');
            ok(['items' => array_map([Media::class, 'present'], $rows), 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $per), 'bytes' => $bytes]);

        case 'media.upload':
            $u = need('media.upload');
            if (empty($_FILES['file'])) {
                fail('No file received. The file may exceed the server limit of ' . ini_get('upload_max_filesize') . '.', 422);
            }
            $row = Media::upload($_FILES['file'], (int)$u['id'], (string)($in['folder'] ?? ''));
            Auth::log('uploaded', 'media', (int)$row['id'], $row['original_name']);
            ok(Media::present($row));

        case 'media.update':
            need('media.upload');
            $id = (int)($in['id'] ?? 0);
            DB::update('media', [
                'alt' => mb_substr(trim(strip_tags((string)($in['alt'] ?? ''))), 0, 250),
                'title' => mb_substr(trim(strip_tags((string)($in['title'] ?? ''))), 0, 250),
            ], 'id = ?', [$id]);
            ok(Media::present(DB::one('SELECT * FROM media WHERE id = ?', [$id])));

        case 'media.delete':
            $u = need('media.view');
            $n = 0;
            $cleared = 0;
            foreach (array_map('intval', (array)($in['ids'] ?? [])) as $id) {
                $row = DB::one('SELECT * FROM media WHERE id = ?', [$id]);
                if (!$row) continue;
                if (!Auth::can('media.delete') && (int)$row['uploaded_by'] !== (int)$u['id']) {
                    fail('You can only delete files you uploaded.', 403);
                }
                Media::deleteFiles($row);
                DB::delete('media', 'id = ?', [$id]);
                $cleared += Media::repoint($row['path'], ''); // no page keeps pointing at a deleted file
                Auth::log('deleted', 'media', $id, $row['original_name']);
                $n++;
            }
            ok(['deleted' => $n, 'cleared_refs' => $cleared]);

        case 'media.autoassign':
            // Match media files to content by filename, e.g. "chiropractic-care.jpg" → the chiropractic-care service
            need('content.edit');
            $apply = !empty($in['apply']);
            $res = Media::autoAssign($apply, !empty($in['overwrite']), Auth::can('settings.manage'));
            if ($apply) {
                $n = count(array_filter($res['matches'], fn($x) => $x['status'] === 'assigned'));
                Auth::log('auto-assigned', 'media', null, plural($n, 'image', 'images'));
            }
            ok($res);

        case 'media.replace':
            // Swap the file behind a media item; every page/setting using it is re-pointed to the new file
            $u = need('media.upload');
            $id = (int)($in['id'] ?? 0);
            if (empty($_FILES['file'])) {
                fail('No file received. The file may exceed the server limit of ' . ini_get('upload_max_filesize') . '.', 422);
            }
            $old = DB::one('SELECT * FROM media WHERE id = ?', [$id]);
            if (!$old) fail('File not found.', 404);
            if (!Auth::can('media.delete') && (int)$old['uploaded_by'] !== (int)$u['id']) {
                fail('You can only replace files you uploaded.', 403);
            }
            [$row, $refs] = Media::replace($old, $_FILES['file'], (int)$u['id']);
            Auth::log('replaced', 'media', (int)$row['id'], $old['original_name'] . ' → ' . $row['original_name']);
            ok(Media::present($row) + ['updated_refs' => $refs]);

        // ------------------------------------------------------------ Submissions
        case 'submissions.list':
            need('submissions.view');
            $where = ['1=1'];
            $p = [];
            if (isset(Forms_types()[$in['form'] ?? ''])) {
                $where[] = 'form = ?';
                $p[] = $in['form'];
            }
            if (in_array($in['status'] ?? '', ['new', 'read', 'archived'], true)) {
                $where[] = 'status = ?';
                $p[] = $in['status'];
            } else {
                $where[] = "status <> 'archived'";
            }
            if (($q = trim((string)($in['q'] ?? ''))) !== '') {
                $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR data LIKE ?)';
                array_push($p, "%$q%", "%$q%", "%$q%", "%$q%");
            }
            $per = 30;
            $page = max(1, (int)($in['page'] ?? 1));
            $total = (int)DB::val('SELECT COUNT(*) FROM submissions WHERE ' . implode(' AND ', $where), $p);
            $rows = DB::all('SELECT * FROM submissions WHERE ' . implode(' AND ', $where) . " ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
            ok(['items' => array_map('present_submission', $rows), 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $per),
                'counts' => ['new' => (int)DB::val("SELECT COUNT(*) FROM submissions WHERE status='new'")], 'types' => used_form_types()]);

        case 'submissions.get':
            need('submissions.view');
            $row = DB::one('SELECT * FROM submissions WHERE id = ?', [(int)($in['id'] ?? 0)]);
            if (!$row) fail('Not found.', 404);
            if ($row['status'] === 'new' && Auth::can('submissions.manage')) {
                DB::update('submissions', ['status' => 'read'], 'id = ?', [$row['id']]);
                $row['status'] = 'read';
            }
            ok(present_submission($row));

        case 'submissions.update':
            need('submissions.manage');
            $status = in_array($in['status'] ?? '', ['new', 'read', 'archived'], true) ? $in['status'] : 'read';
            foreach (array_map('intval', (array)($in['ids'] ?? [])) as $id) {
                DB::update('submissions', ['status' => $status], 'id = ?', [$id]);
            }
            ok();

        case 'submissions.delete':
            need('submissions.manage');
            $n = 0;
            foreach (array_map('intval', (array)($in['ids'] ?? [])) as $id) {
                $n += DB::delete('submissions', 'id = ?', [$id]);
            }
            Auth::log('deleted', 'submissions', null, plural($n, 'submission', 'submissions'));
            ok(['deleted' => $n]);

        case 'submissions.export':
            need('submissions.view');
            $rows = DB::all('SELECT * FROM submissions ORDER BY id DESC');
            $keys = [];
            foreach ($rows as $r) {
                foreach (array_keys(json_list($r['data'])) as $k) $keys[$k] = true;
            }
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="submissions-' . date('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['id', 'form', 'status', 'submitted'], array_keys($keys), ['page']));
            foreach ($rows as $r) {
                $d = json_list($r['data']);
                $line = [$r['id'], $r['form'], $r['status'], $r['created_at']];
                foreach (array_keys($keys) as $k) {
                    $v = (string)($d[$k] ?? '');
                    $line[] = preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; // CSV injection guard
                }
                $line[] = $r['page'];
                fputcsv($out, $line);
            }
            fclose($out);
            exit;

        // ------------------------------------------------------------ Users
        case 'users.list':
            need('users.manage');
            $rows = DB::all('SELECT * FROM users ORDER BY id');
            ok(array_map(fn($u) => Auth::publicUser($u) + ['status' => $u['status'], 'created_at' => $u['created_at']], $rows));

        case 'users.save':
            $me = need('users.manage');
            $id = (int)($in['id'] ?? 0);
            $d = (array)($in['data'] ?? []);
            $row = [
                'name' => mb_substr(trim(strip_tags((string)($d['name'] ?? ''))), 0, 120),
                'username' => mb_substr(trim((string)($d['username'] ?? '')), 0, 60),
                'email' => strtolower(trim((string)($d['email'] ?? ''))),
                'role' => isset(Auth::ROLES[$d['role'] ?? '']) ? $d['role'] : 'editor',
                'status' => ($d['status'] ?? 'active') === 'disabled' ? 'disabled' : 'active',
                'updated_at' => now(),
            ];
            if (!preg_match('/^[A-Za-z0-9._\-]{3,60}$/', $row['username'])) fail('Username must be 3–60 characters: letters, numbers, dot, dash or underscore.', 422);
            if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.', 422);
            if (DB::val('SELECT id FROM users WHERE username = ? AND id <> ?', [$row['username'], $id])) fail('That username is already taken.', 422);
            if (DB::val('SELECT id FROM users WHERE email = ? AND id <> ?', [$row['email'], $id])) fail('That email is already in use.', 422);
            $pw = (string)($d['password'] ?? '');
            if ($pw !== '') {
                if (strlen($pw) < 8) fail('Password must be at least 8 characters.', 422);
                $row['password'] = password_hash($pw, PASSWORD_DEFAULT);
            } elseif (!$id) {
                fail('Please set a password for the new user.', 422);
            }
            if ($id) {
                $cur = DB::one('SELECT * FROM users WHERE id = ?', [$id]);
                if (!$cur) fail('User not found.', 404);
                $admins = (int)DB::val("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'");
                if ($cur['role'] === 'admin' && ($row['role'] !== 'admin' || $row['status'] !== 'active') && $admins <= 1) {
                    fail('There must be at least one active administrator.', 422);
                }
                if ($id === (int)$me['id'] && $row['status'] !== 'active') fail('You cannot disable your own account.', 422);
                DB::update('users', $row, 'id = ?', [$id]);
                Auth::log('updated', 'user', $id, $row['name'] ?: $row['username']);
                if ($id === (int)$me['id'] && isset($row['password'])) {
                    Auth::login(DB::one('SELECT * FROM users WHERE id = ?', [$id]));
                }
            } else {
                $row['created_at'] = now();
                $id = DB::insert('users', $row);
                Auth::log('created', 'user', $id, $row['name'] ?: $row['username']);
            }
            $u = DB::one('SELECT * FROM users WHERE id = ?', [$id]);
            ok(Auth::publicUser($u) + ['status' => $u['status'], 'created_at' => $u['created_at'], 'csrf' => Auth::csrf()]);

        case 'users.delete':
            $me = need('users.manage');
            $id = (int)($in['id'] ?? 0);
            if ($id === (int)$me['id']) fail('You cannot delete your own account.', 422);
            $u = DB::one('SELECT * FROM users WHERE id = ?', [$id]);
            if (!$u) fail('User not found.', 404);
            if ($u['role'] === 'admin' && (int)DB::val("SELECT COUNT(*) FROM users WHERE role='admin'") <= 1) fail('You cannot delete the last administrator.', 422);
            DB::delete('users', 'id = ?', [$id]);
            Auth::log('deleted', 'user', $id, $u['name'] ?: $u['username']);
            ok();

        case 'profile.save':
            $me = need('content.view');
            $d = (array)($in['data'] ?? []);
            $row = [
                'name' => mb_substr(trim(strip_tags((string)($d['name'] ?? $me['name']))), 0, 120),
                'email' => strtolower(trim((string)($d['email'] ?? $me['email']))),
                'avatar' => Resources::clean(['type' => 'image'], $d['avatar'] ?? $me['avatar']),
                'updated_at' => now(),
            ];
            if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.', 422);
            if (DB::val('SELECT id FROM users WHERE email = ? AND id <> ?', [$row['email'], $me['id']])) fail('That email is already in use.', 422);
            $new = (string)($d['new_password'] ?? '');
            if ($new !== '') {
                if (!password_verify((string)($d['current_password'] ?? ''), $me['password'])) fail('Your current password is incorrect.', 422);
                if (strlen($new) < 8) fail('New password must be at least 8 characters.', 422);
                $row['password'] = password_hash($new, PASSWORD_DEFAULT);
            }
            DB::update('users', $row, 'id = ?', [$me['id']]);
            $u = DB::one('SELECT * FROM users WHERE id = ?', [$me['id']]);
            if ($new !== '') {
                Auth::login($u);
                Auth::log('changed password', 'user', (int)$u['id'], $u['name']);
            }
            ok(['user' => Auth::publicUser($u), 'csrf' => Auth::csrf()]);

        // ------------------------------------------------------------ Settings
        case 'settings.get':
            need('settings.manage');
            $vals = [];
            foreach (Resources::settings() as $tab) {
                foreach ($tab['fields'] as $f) {
                    $v = Settings::get($f['key'], '');
                    if ($f['type'] === 'repeater') $v = json_list((string)$v);
                    elseif ($f['type'] === 'relation') $v = csv_list((string)$v);
                    elseif ($f['type'] === 'toggle') $v = $v === '1';
                    $vals[$f['key']] = $v;
                }
            }
            ok($vals);

        case 'settings.save':
            need('settings.manage');
            $d = (array)($in['data'] ?? []);
            $changed = [];
            foreach (Resources::settings() as $tab) {
                foreach ($tab['fields'] as $f) {
                    if (array_key_exists($f['key'], $d)) {
                        if ($f['type'] === 'code' && !is_admin()) fail('Only administrators can change code settings.', 403);
                        $v = Resources::clean($f, $d[$f['key']]);
                        if ($f['type'] === 'color' && $v === '') continue;
                        Settings::set($f['key'], (string)$v);
                        $changed[] = $f['key'];
                    }
                }
            }
            Auth::log('updated', 'settings', null, implode(', ', array_slice($changed, 0, 6)));
            ok(['saved' => count($changed)]);

        // ------------------------------------------------------------ Activity, search, system
        case 'activity.list':
            need('activity.view');
            $page = max(1, (int)($in['page'] ?? 1));
            $per = 50;
            $total = (int)DB::val('SELECT COUNT(*) FROM activity');
            ok(['items' => DB::all("SELECT * FROM activity ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per)), 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $per)]);

        case 'search':
            need('content.view');
            $q = trim((string)($in['q'] ?? ''));
            $out = [];
            if (mb_strlen($q) >= 2) {
                foreach (['services', 'providers', 'pages', 'testimonials', 'faqs', 'locations'] as $type) {
                    $def = Resources::get($type);
                    if (!empty($def['hidden'])) continue;
                    $tk = $def['titleKey'];
                    foreach (DB::all("SELECT id, $tk AS title FROM {$def['table']} WHERE $tk LIKE ? ORDER BY $tk LIMIT 5", ["%$q%"]) as $r) {
                        $out[] = ['type' => $type, 'label' => $def['singular'], 'id' => (int)$r['id'], 'title' => $r['title'], 'icon' => $def['icon']];
                    }
                }
                if (Auth::can('media.view')) {
                    foreach (DB::all('SELECT id, original_name AS title FROM media WHERE original_name LIKE ? OR title LIKE ? LIMIT 5', ["%$q%", "%$q%"]) as $r) {
                        $out[] = ['type' => 'media', 'label' => 'Media', 'id' => (int)$r['id'], 'title' => $r['title'], 'icon' => 'image'];
                    }
                }
            }
            ok($out);

        case 'system.info':
            need('settings.manage');
            $dbSize = DB::driver() === 'sqlite' ? @filesize((string)(cfg('db.sqlite_path') ? ROOT . '/' . cfg('db.sqlite_path') : '')) : null;
            ok([
                'version' => VERSION,
                'php' => PHP_VERSION,
                'database' => DB::driver() === 'sqlite' ? 'SQLite' : 'MySQL ' . DB::val('SELECT VERSION()'),
                'db_size' => $dbSize ?: null,
                'gd' => extension_loaded('gd'),
                'webp' => function_exists('imagewebp'),
                'upload_max' => ini_get('upload_max_filesize'),
                'post_max' => ini_get('post_max_size'),
                'memory' => ini_get('memory_limit'),
                'uploads_writable' => is_writable(ROOT . '/uploads'),
                'media_bytes' => (int)DB::val('SELECT COALESCE(SUM(size),0) FROM media'),
                'installed_at' => setting('installed_at'),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            ]);

        case 'backup.export':
            need('settings.manage');
            $dump = ['exported_at' => date('c'), 'version' => VERSION];
            foreach (['settings', 'services', 'providers', 'pages', 'locations', 'testimonials', 'faqs', 'redirects', 'media'] as $t) {
                $dump[$t] = DB::all("SELECT * FROM $t");
            }
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="site-content-' . date('Y-m-d') . '.json"');
            echo json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;

        default:
            fail('Unknown endpoint.', 404);
    }
} catch (RuntimeException $e) {
    fail($e->getMessage(), 422);
} catch (Throwable $e) {
    log_error('API ' . $route . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    fail(cfg('debug') ? $e->getMessage() : 'Something went wrong. Please try again.', 500);
}

function Forms_types(): array
{
    return Forms::TYPES;
}

/** Form types for the inbox filter: the site's own forms plus any type that has submissions. */
function used_form_types(): array
{
    $keep = array_merge(array_keys(Forms::OPTIONS), array_column(DB::all('SELECT DISTINCT form FROM submissions'), 'form'));
    return array_intersect_key(Forms::TYPES, array_flip($keep));
}

function present_submission(array $r): array
{
    return [
        'id' => (int)$r['id'],
        'form' => $r['form'],
        'form_label' => Forms_types()[$r['form']] ?? $r['form'],
        'name' => $r['name'],
        'email' => $r['email'],
        'phone' => $r['phone'],
        'data' => json_list($r['data']),
        'page' => $r['page'],
        'status' => $r['status'],
        'forwarded' => (int)$r['forwarded'] === 1,
        'created_at' => $r['created_at'],
    ];
}

function self_ini_bytes(string $key): int
{
    $v = trim((string)ini_get($key));
    if ($v === '') return PHP_INT_MAX;
    $n = (int)$v;
    $u = strtolower(substr($v, -1));
    return match ($u) { 'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n } ?: PHP_INT_MAX;
}
