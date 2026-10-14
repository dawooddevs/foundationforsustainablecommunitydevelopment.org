<?php
/** @var array $faqs list of ['q'=>..,'a'=>..] or rows with question/answer */
if (!$faqs) return;
$schema = Seo::faqSchema($faqs);
if ($schema && empty($noSchema)) Seo::addSchema($schema);
$uid = $id ?? ('faq' . substr(md5(json_encode($faqs)), 0, 6));
?>
<div class="faq" data-accordion>
  <?php foreach ($faqs as $i => $f):
      $q = $f['question'] ?? $f['q'] ?? '';
      $a = $f['answer'] ?? $f['a'] ?? ''; ?>
  <div class="faq__item">
    <h3 class="faq__q">
      <button type="button" aria-expanded="false" aria-controls="<?= e($uid . '-' . $i) ?>" id="<?= e($uid . '-b' . $i) ?>">
        <span><?= e($q) ?></span><span class="faq__icon" aria-hidden="true"></span>
      </button>
    </h3>
    <div class="faq__a" id="<?= e($uid . '-' . $i) ?>" role="region" aria-labelledby="<?= e($uid . '-b' . $i) ?>" hidden>
      <div class="faq__a-inner"><?= str_starts_with(trim($a), '<') ? Html::clean($a) : '<p>' . nl2br(e($a)) . '</p>' ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
