<?php
/** SCAFFOLD service page. Every section is hidden when its field is empty. @var array $service */
$s = $service;
$url = Content::serviceUrl($s);
$conditions = lines($s['conditions']);
$benefits = lines($s['benefits']);
$faqs = json_list($s['faqs']);
$related = Content::related($s, 4);
$providers = array_values(array_filter(Content::providers(), fn($p) => in_array($s['slug'], csv_list($p['services']), true)));
$has = fn($html) => trim(strip_tags((string)$html)) !== '';

Seo::set(['title' => $s['meta_title'] ?: $s['title'], 'description' => $s['meta_description'] ?: $s['excerpt'], 'canonical' => abs_url(Content::SERVICE_BASE . '/' . $s['slug'] . '/'), 'image' => $s['image']]);
Seo::addSchema([
    '@context' => 'https://schema.org', '@type' => 'MedicalWebPage', 'name' => $s['title'],
    'description' => strip_tags((string)$s['excerpt']), 'about' => ['@type' => 'MedicalTherapy', 'name' => $s['title']],
    'publisher' => ['@id' => abs_url('') . '#organization'],
]);
$index = Content::page(Content::SERVICE_BASE);
partial('page-head', ['title' => $s['title'], 'intro' => $s['hero_text'] ?: $s['excerpt'], 'crumbs' => [[$index['title'] ?? 'Services', Content::SERVICE_BASE . '/'], [$s['title'], Content::SERVICE_BASE . '/' . $s['slug'] . '/']]]);
?>
<article class="section">
  <div class="container narrow prose">
    <?php if ($s['image']): ?><?= img($s['image'], $s['title'], ['loading' => 'eager']) ?><?php endif; ?>
    <?php if ($has($s['what_is'])): ?><h2>What Is <?= e($s['title']) ?>?</h2><?= Html::clean($s['what_is']) ?><?php endif; ?>
    <?php if ($conditions): ?><h2>What Can It Help With?</h2><ul><?php foreach ($conditions as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($has($s['how_it_works'])): ?><h2>How It Works</h2><?= Html::clean($s['how_it_works']) ?><?php endif; ?>
    <?php if ($benefits): ?><h2>Benefits</h2><ul><?php foreach ($benefits as $b): ?><li><?= e($b) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($has($s['what_to_expect'])): ?><h2>What to Expect</h2><?= Html::clean($s['what_to_expect']) ?><?php endif; ?>
    <?php if ($faqs): ?><h2>Frequently Asked Questions</h2><?php partial('faq', ['faqs' => $faqs, 'id' => 'svc-faq']); ?><?php endif; ?>
    <p><a class="btn" href="<?= e(url('book-appointment/')) ?>">Book an Appointment</a></p>
  </div>
</article>
<?php if ($providers): ?>
<section class="section"><div class="container">
  <h2>Providers Who Offer This Service</h2>
  <ul><?php foreach ($providers as $p): ?><li><a href="<?= e(Content::providerUrl($p)) ?>"><?= e($p['name']) ?></a> — <?= e($p['title']) ?></li><?php endforeach; ?></ul>
</div></section>
<?php endif; ?>
<?php if ($related): ?>
<section class="section"><div class="container">
  <h2>Related Services</h2>
  <div class="grid"><?php foreach ($related as $r) partial('service-card', ['s' => $r]); ?></div>
</div></section>
<?php endif; ?>
