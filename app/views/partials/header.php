<?php
/** SCAFFOLD header: logo, service menu (by category), main links, phone. */
$phone = setting('phone');
$cur = fn($p) => rtrim($path ?? '', '/') === rtrim($p, '/') ? ' aria-current="page"' : '';
?>
<header class="site-header">
  <div class="container header__inner">
    <a class="brand" href="<?= e(url('')) ?>">
      <?php if (setting('logo')): ?><?= img(setting('logo'), setting('site_name'), ['class' => 'brand__img', 'loading' => 'eager']) ?><?php else: ?><?= e(setting('site_name')) ?><?php endif; ?>
    </a>
    <nav aria-label="Main navigation">
      <ul class="nav">
        <li><a href="<?= e(url(Content::SERVICE_BASE . '/')) ?>"<?= $cur('/' . Content::SERVICE_BASE . '/') ?>>Services</a>
          <ul>
            <?php foreach (Content::servicesByCategory() as $cat => $items): $items = array_filter($items, fn($s) => (int)$s['show_in_menu'] === 1); if (!$items) continue; ?>
            <li><strong><?= e(Content::categoryLabel($cat)) ?></strong>
              <ul><?php foreach ($items as $s): ?><li><a href="<?= e(Content::serviceUrl($s)) ?>"><?= e($s['menu_label'] ?: $s['title']) ?></a></li><?php endforeach; ?></ul>
            </li>
            <?php endforeach; ?>
          </ul>
        </li>
        <li><a href="<?= e(url('book-appointment/')) ?>"<?= $cur('/book-appointment/') ?>>Book Appointment</a></li>
        <li><a href="<?= e(url('contact-us/')) ?>"<?= $cur('/contact-us/') ?>>Contact</a></li>
      </ul>
    </nav>
    <?php if ($phone): ?><a class="header__phone" href="<?= e(tel_href($phone)) ?>"><?= icon('phone') ?><?= e($phone) ?></a><?php endif; ?>
  </div>
</header>
