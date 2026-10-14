<?php
/** SCAFFOLD contact page: contact details, locations with maps, contact form (or GHL embed). @var array $page */
Seo::set(['title' => $page['meta_title'] ?: $page['title'], 'description' => $page['meta_description'] ?: $page['intro'], 'canonical' => abs_url($page['slug'] . '/')]);
partial('page-head', ['title' => $page['title'], 'intro' => $page['intro'], 'crumbs' => [[$page['title'], $page['slug'] . '/']]]);
$phone = setting('phone');
$sms = setting('sms_phone') ?: $phone;
$email = setting('email');
?>
<section class="section">
  <div class="container narrow">
    <ul class="contact-list">
      <?php if ($phone): ?><li>Call: <a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a></li><?php endif; ?>
      <?php if ($sms): ?><li>Text: <a href="<?= e(sms_href($sms)) ?>"><?= e($sms) ?></a></li><?php endif; ?>
      <?php if ($email): ?><li>Email: <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li><?php endif; ?>
    </ul>
    <?php foreach (Content::locations() as $l): ?>
    <h2><?= e($l['name']) ?></h2>
    <p><a href="<?= e(Content::mapsDirections($l)) ?>" target="_blank" rel="noopener"><?= e(Content::fullAddress($l)) ?></a></p>
    <?php if ($hours = Content::hours($l)): ?><dl class="hours"><?php foreach ($hours as $h): ?><dt><?= e($h['days']) ?></dt><dd><?= e($h['time']) ?></dd><?php endforeach; ?></dl><?php endif; ?>
    <iframe class="map" src="<?= e(Content::mapEmbed($l)) ?>" title="Map to <?= e($l['name']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    <?php endforeach; ?>
    <h2>Send Us a Message</h2>
    <?php partial('form', ['type' => 'contact']); ?>
  </div>
</section>
