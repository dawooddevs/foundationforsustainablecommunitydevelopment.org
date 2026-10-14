<?php
/** SCAFFOLD services index (page template "treatments"). Supports ?category=<key>. @var array $page */
Seo::set(['title' => $page['meta_title'] ?: $page['title'], 'description' => $page['meta_description'] ?: $page['intro'], 'canonical' => abs_url($page['slug'] . '/')]);
$active = isset(Content::CATEGORIES[$_GET['category'] ?? '']) ? $_GET['category'] : '';
partial('page-head', ['title' => $page['title'], 'intro' => $page['intro'], 'crumbs' => [[$page['title'], $page['slug'] . '/']]]);
?>
<section class="section">
  <div class="container">
    <p class="filters">
      <a href="<?= e(url($page['slug'] . '/')) ?>"<?= $active === '' ? ' aria-current="true"' : '' ?>>All</a>
      <?php foreach (Content::CATEGORIES as $k => $c): ?><a href="<?= e(url($page['slug'] . '/?category=' . $k)) ?>"<?= $active === $k ? ' aria-current="true"' : '' ?>><?= e($c['label']) ?></a><?php endforeach; ?>
    </p>
    <?php foreach (Content::servicesByCategory() as $cat => $items): if (!$items || ($active && $active !== $cat)) continue; ?>
    <h2><?= e(Content::categoryLabel($cat)) ?></h2>
    <div class="grid"><?php foreach ($items as $s) partial('service-card', ['s' => $s]); ?></div>
    <?php endforeach; ?>
    <?php if (!Content::services()): ?><p>No services have been published yet.</p><?php endif; ?>
    <?php if (trim(strip_tags((string)$page['content'])) !== ''): ?><div class="prose"><?= Html::clean($page['content']) ?></div><?php endif; ?>
  </div>
</section>
