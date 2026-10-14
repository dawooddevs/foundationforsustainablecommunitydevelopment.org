<?php
/**
 * SCAFFOLD homepage. Shows every homepage data source the dashboard manages; each block is
 * hidden when it has no data. Replace the layout and design freely.
 */
Seo::set([
    'title' => setting('site_name') . (setting('tagline') ? ' | ' . setting('tagline') : ''),
    'description' => setting('seo_default_description'),
    'canonical' => abs_url(''),
]);
$phone = setting('phone');
$featured = Content::explorerServices('featured') ?: Content::services();
$stats = json_list(setting('stats'));
$providers = Content::providers();
$testimonials = Content::testimonials(true);
$locations = Content::locations();
$faqs = Content::faqs('home');
?>
<section class="hero">
  <div class="container">
    <h1><?= e(setting('hero_line1') ?: setting('site_name')) ?><?php if (setting('hero_line2')): ?> <span><?= e(setting('hero_line2')) ?></span><?php endif; ?></h1>
    <?php if (setting('hero_copy')): ?><p class="lead"><?= e(setting('hero_copy')) ?></p><?php endif; ?>
    <p>
      <a class="btn" href="<?= e(url('book-appointment/')) ?>">Book an Appointment</a>
      <?php if ($phone): ?><a class="btn btn--ghost" href="<?= e(tel_href($phone)) ?>">Call <?= e($phone) ?></a><?php endif; ?>
    </p>
    <?php if ($badges = lines(setting('hero_badges'))): ?><ul class="badges"><?php foreach ($badges as $b): ?><li><?= e($b) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if (setting('hero_image')): ?><?= img(setting('hero_image'), setting('site_name'), ['loading' => 'eager', 'fetchpriority' => 'high']) ?><?php endif; ?>
  </div>
</section>

<?php if ($stats): ?>
<section class="section"><div class="container stats">
  <?php foreach ($stats as $st): ?><div><strong><?= e($st['value'] . ($st['suffix'] ?? '')) ?></strong> <?= e($st['label']) ?></div><?php endforeach; ?>
</div></section>
<?php endif; ?>

<?php if ($featured): ?>
<section class="section">
  <div class="container">
    <h2>Services</h2>
    <div class="grid"><?php foreach ($featured as $s) partial('service-card', ['s' => $s]); ?></div>
    <p><a href="<?= e(url(Content::SERVICE_BASE . '/')) ?>">All services</a></p>
  </div>
</section>
<?php endif; ?>

<?php if ($providers): ?>
<section class="section">
  <div class="container">
    <h2>Our Team</h2>
    <div class="grid">
      <?php foreach ($providers as $p): ?>
      <article class="card">
        <a href="<?= e(Content::providerUrl($p)) ?>">
          <?php if ($p['photo']): ?><?= img($p['photo'], $p['name'], ['style' => 'object-position:' . e($p['photo_position'] ?: '50% 20%')]) ?><?php endif; ?>
          <h3><?= e($p['name']) ?><?= $p['credentials'] ? ', ' . e($p['credentials']) : '' ?></h3>
        </a>
        <p><?= e($p['title']) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($testimonials): ?>
<section class="section">
  <div class="container">
    <h2>What Our Patients Say</h2>
    <?php foreach ($testimonials as $t): ?>
    <blockquote><p><?= e($t['content']) ?></p><footer><?= e($t['name']) ?><?= $t['label'] ? ', ' . e($t['label']) : '' ?></footer></blockquote>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($locations): ?>
<section class="section">
  <div class="container grid">
    <?php foreach ($locations as $l): ?>
    <article class="card">
      <h3><?= e($l['name']) ?></h3>
      <p><a href="<?= e(Content::mapsDirections($l)) ?>" target="_blank" rel="noopener"><?= e(Content::fullAddress($l)) ?></a></p>
      <?php if ($l['phone']): ?><p><a href="<?= e(tel_href($l['phone'])) ?>"><?= e($l['phone']) ?></a></p><?php endif; ?>
      <?php if ($hours = Content::hours($l)): ?><dl class="hours"><?php foreach ($hours as $h): ?><dt><?= e($h['days']) ?></dt><dd><?= e($h['time']) ?></dd><?php endforeach; ?></dl><?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section" id="book">
  <div class="container narrow">
    <h2>Book an Appointment</h2>
    <?php partial('form', ['type' => 'appointment']); ?>
  </div>
</section>

<?php if ($faqs): ?>
<section class="section">
  <div class="container narrow">
    <h2>Frequently Asked Questions</h2>
    <?php partial('faq', ['faqs' => $faqs, 'id' => 'home-faq']); ?>
  </div>
</section>
<?php endif; ?>
