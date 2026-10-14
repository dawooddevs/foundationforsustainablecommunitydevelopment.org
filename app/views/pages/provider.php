<?php
/** SCAFFOLD provider profile. @var array $provider */
$p = $provider;
$url = Content::providerUrl($p);
$focus = lines($p['focus']);
$services = Content::servicesBySlugs(csv_list($p['services']));
$locs = array_values(array_filter(Content::locations(), fn($l) => in_array($l['slug'], csv_list($p['locations']), true)));
Seo::set(['title' => $p['meta_title'] ?: ($p['name'] . ' — ' . $p['title']), 'description' => $p['meta_description'] ?: str_limit(strip_tags((string)($p['short_bio'] ?: $p['bio'])), 160), 'image' => $p['photo'], 'type' => 'profile']);
$schema = ['@context' => 'https://schema.org', '@type' => ['Physician', 'Person'], 'name' => $p['name'], 'jobTitle' => $p['title'], 'worksFor' => ['@id' => abs_url('') . '#organization']];
if ($p['photo']) $schema['image'] = abs_url($p['photo']);
if ($p['credentials']) $schema['honorificSuffix'] = $p['credentials'];
Seo::addSchema($schema);
partial('page-head', ['title' => $p['name'] . ($p['credentials'] ? ', ' . $p['credentials'] : ''), 'intro' => $p['title'], 'crumbs' => [[$p['name'], Content::PROVIDER_BASE . '/' . $p['slug'] . '/']]]);
?>
<article class="section">
  <div class="container narrow prose">
    <?php if ($p['photo']): ?><?= img($p['photo'], $p['name'], ['style' => 'object-position:' . e($p['photo_position'] ?: '50% 20%')]) ?><?php endif; ?>
    <?php if ($p['short_bio']): ?><p class="lead"><?= e($p['short_bio']) ?></p><?php endif; ?>
    <?php foreach (['Biography' => $p['bio'], 'Education' => $p['education'], 'Experience' => $p['experience'], 'Treatment Philosophy' => $p['philosophy']] as $h => $html): if (trim(strip_tags((string)$html)) === '') continue; ?>
    <h2><?= e($h) ?></h2><?= Html::clean($html) ?>
    <?php endforeach; ?>
    <?php if ($focus): ?><h2>Areas of Focus</h2><ul><?php foreach ($focus as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($services): ?><h2>Services</h2><ul><?php foreach ($services as $s): ?><li><a href="<?= e(Content::serviceUrl($s)) ?>"><?= e($s['title']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($locs): ?><h2>Locations</h2><ul><?php foreach ($locs as $l): ?><li><?= e($l['name']) ?> — <?= e(Content::fullAddress($l)) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <p><a class="btn" href="<?= e(url('book-appointment/')) ?>">Book an Appointment</a></p>
  </div>
</article>
