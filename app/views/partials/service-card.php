<?php /** SCAFFOLD service card (also returned by /api/treatments for "Show more"). @var array $s */ ?>
<article class="card">
  <a href="<?= e(Content::serviceUrl($s)) ?>">
    <?php if ($s['image']): ?><?= img($s['image'], $s['title']) ?><?php endif; ?>
    <h3><?= e($s['title']) ?></h3>
  </a>
  <?php if ($s['excerpt']): ?><p><?= e($s['excerpt']) ?></p><?php endif; ?>
</article>
