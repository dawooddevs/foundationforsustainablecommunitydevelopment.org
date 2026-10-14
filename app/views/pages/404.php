<?php
/**
 * 404 page. While the site runs on raw-HTML pages it reuses the Privacy Policy page's own styles
 * and scripts (everything before {{header}} and after {{footer}}) with the shared header and footer,
 * and a "Page not found" hero in that page's existing hero style. Otherwise the scaffold version.
 */
Seo::set(['title' => 'Page Not Found']);
Seo::$noindex = true;

$base = DB::one("SELECT * FROM pages WHERE slug = 'privacy-policy-2' AND template = 'html' AND status = 'published'");
$tpl = (string)($base['content'] ?? '');
$h = strpos($tpl, '{{header}}');
$f = strpos($tpl, '{{footer}}');
if ($base && $h !== false && $f !== false && $f > $h) {
    $GLOBALS['page_layout'] = 'layout-html';
    $body = '<div id="fscd-privacy-page">
<section class="privacy-hero">
  <div class="fscd-container">
    <div class="privacy-breadcrumb"><a href="' . e(url('')) . '">Home</a><i class="fa-solid fa-chevron-right"></i><span>Page Not Found</span></div>
    <span class="eyebrow"><i class="fa-solid fa-compass"></i> Error 404</span>
    <h1>Page Not Found</h1>
    <p>Sorry, we couldn\'t find that page. Return to the <a href="' . e(url('')) . '" style="color:inherit;text-decoration:underline">home page</a> or <a href="' . e(url('contact/')) . '" style="color:inherit;text-decoration:underline">contact FSCD</a>.</p>
  </div>
</section>
</div>
';
    echo HtmlPage::render(substr($tpl, 0, $h) . '{{header}}' . "\n" . $body . '{{footer}}' . substr($tpl, $f + 10), '');
    return;
}
$phone = setting('phone');
?>
<section class="section">
  <div class="container narrow">
    <h1>Page Not Found</h1>
    <p>Sorry, we couldn't find that page.</p>
    <ul>
      <li><a href="<?= e(url('')) ?>">Home</a></li>
      <li><a href="<?= e(url('contact/')) ?>">Contact</a></li>
    </ul>
    <?php if ($phone): ?><p>Or call <a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a>.</p><?php endif; ?>
  </div>
</section>
