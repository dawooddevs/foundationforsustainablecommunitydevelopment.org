<?php
/** SCAFFOLD HTML sitemap. @var array $page */
Seo::set(['title' => $page['meta_title'] ?: $page['title'], 'description' => $page['meta_description'], 'canonical' => abs_url('sitemap/')]);
partial('page-head', ['title' => $page['title'], 'intro' => $page['intro'], 'crumbs' => [[$page['title'], 'sitemap/']]]);
$pages = DB::all("SELECT slug, title FROM pages WHERE status = 'published' AND slug NOT IN ('thank-you') ORDER BY sort_order, title");
?>
<section class="section">
  <div class="container">
    <h2>Pages</h2>
    <ul><li><a href="<?= e(url('')) ?>">Home</a></li><?php foreach ($pages as $p): ?><li><a href="<?= e(url($p['slug'] . '/')) ?>"><?= e($p['title']) ?></a></li><?php endforeach; ?></ul>
    <?php foreach (Content::servicesByCategory() as $cat => $items): if (!$items) continue; ?>
    <h2><?= e(Content::categoryLabel($cat)) ?></h2>
    <ul><?php foreach ($items as $s): ?><li><a href="<?= e(Content::serviceUrl($s)) ?>"><?= e($s['title']) ?></a></li><?php endforeach; ?></ul>
    <?php endforeach; ?>
    <?php if ($providers = Content::providers()): ?>
    <h2>Our Team</h2>
    <ul><?php foreach ($providers as $p): ?><li><a href="<?= e(Content::providerUrl($p)) ?>"><?= e($p['name']) ?></a></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</section>
