<?php
/** SCAFFOLD standard content page (template "default"). @var array $page */
Seo::set(['title' => $page['meta_title'] ?: $page['title'], 'description' => $page['meta_description'] ?: $page['intro'], 'canonical' => abs_url($page['slug'] . '/'), 'image' => $page['image']]);
if (in_array($page['slug'], ['thank-you'], true)) Seo::$noindex = true;
partial('page-head', ['title' => $page['title'], 'intro' => $page['intro'], 'crumbs' => [[$page['title'], $page['slug'] . '/']]]);
?>
<section class="section">
  <div class="container narrow prose">
    <?php if ($page['image']): ?><?= img($page['image'], $page['title']) ?><?php endif; ?>
    <?= Html::clean((string)$page['content']) ?>
  </div>
</section>
