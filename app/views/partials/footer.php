<?php
/** SCAFFOLD footer: brand, contact details, locations, social links, legal links. */
$phone = setting('phone');
$email = setting('email');
$social = array_filter(['Facebook' => setting('social_facebook'), 'X' => setting('social_x'), 'Instagram' => setting('social_instagram')]);
?>
<footer class="site-footer">
  <div class="container footer__grid">
    <div>
      <p><strong><?= e(setting('site_name')) ?></strong></p>
      <?php if (setting('brand_description')): ?><p><?= e(setting('brand_description')) ?></p><?php endif; ?>
      <?php if ($phone): ?><p><a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a></p><?php endif; ?>
      <?php if ($email): ?><p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
      <?php if ($social): ?><p><?php foreach ($social as $label => $href): ?><a href="<?= e($href) ?>" target="_blank" rel="noopener"><?= e($label) ?></a> <?php endforeach; ?></p><?php endif; ?>
    </div>
    <?php foreach (Content::locations() as $l): ?>
    <div>
      <p><strong><?= e($l['name']) ?></strong></p>
      <p><a href="<?= e(Content::mapsDirections($l)) ?>" target="_blank" rel="noopener"><?= e(Content::fullAddress($l)) ?></a></p>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="container footer__bar">
    <p>&copy; <?= date('Y') ?> <?= e(setting('site_name')) ?></p>
    <p>
      <a href="<?= e(url('privacy-policy/')) ?>">Privacy Policy</a> ·
      <a href="<?= e(url('terms-and-conditions/')) ?>">Terms</a> ·
      <a href="<?= e(url('medical-disclaimer/')) ?>">Medical Disclaimer</a> ·
      <a href="<?= e(url('sitemap/')) ?>">Sitemap</a>
    </p>
  </div>
</footer>
