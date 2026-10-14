<?php
/**
 * SCAFFOLD page heading with breadcrumbs (also emitted as BreadcrumbList schema by Seo::head()).
 * @var string $title  @var string $intro  @var array $crumbs [[label, path], …] after Home
 */
Seo::$breadcrumbs = array_merge([['Home', '']], $crumbs ?? []);
?>
<section class="page-head">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb">
      <?php foreach (Seo::$breadcrumbs as $i => [$label, $p]): ?><?= $i ? ' / ' : '' ?><a href="<?= e(url($p)) ?>"><?= e($label) ?></a><?php endforeach; ?>
    </nav>
    <h1><?= e($title) ?></h1>
    <?php if (!empty($intro)): ?><p class="lead"><?= e($intro) ?></p><?php endif; ?>
  </div>
</section>
