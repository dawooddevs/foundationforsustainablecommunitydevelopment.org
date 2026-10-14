<?php
/**
 * Helper for scripts/visual-check.js: prints, per page slug, the shared header (with that page's
 * active menu item) and footer exactly as the site stores them (media tokens unresolved).
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require APP . '/lib/Forms.php';
require APP . '/lib/HtmlPage.php';
$out = [];
foreach (DB::all("SELECT slug FROM pages WHERE template = 'html'") as $p) {
    $out[$p['slug']] = ['header' => HtmlPage::header($p['slug']), 'footer' => (string)setting('site_footer_html')];
}
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
