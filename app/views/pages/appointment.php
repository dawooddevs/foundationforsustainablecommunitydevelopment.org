<?php
/** SCAFFOLD appointment page: GoHighLevel embed or built-in form, plus downloadable patient forms. @var array $page */
Seo::set(['title' => $page['meta_title'] ?: $page['title'], 'description' => $page['meta_description'] ?: $page['intro'], 'canonical' => abs_url($page['slug'] . '/')]);
partial('page-head', ['title' => $page['title'], 'intro' => $page['intro'], 'crumbs' => [[$page['title'], $page['slug'] . '/']]]);
$phone = setting('phone');
?>
<section class="section">
  <div class="container narrow">
    <?php partial('form', ['type' => 'appointment']); ?>
    <?php if ($phone): ?><p>Prefer to talk? Call <a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a> or <a href="<?= e(sms_href(setting('sms_phone') ?: $phone)) ?>">send a text</a>.</p><?php endif; ?>
  </div>
</section>
<?php if ($groups = Content::formGroups()): ?>
<section class="section" id="patient-forms">
  <div class="container narrow">
    <h2>Patient Forms</h2>
    <?php foreach ($groups as $g): ?>
    <h3><?= e($g['title']) ?></h3>
    <p><?= e($g['text']) ?></p>
    <ul><?php foreach ($g['items'] as $f): ?><li><a href="<?= e($f['url']) ?>" target="_blank" rel="noopener"><?= e($f['label']) ?> (PDF)</a></li><?php endforeach; ?></ul>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php if (trim(strip_tags((string)$page['content'])) !== ''): ?><section class="section"><div class="container narrow prose"><?= Html::clean($page['content']) ?></div></section><?php endif; ?>
