<?php
/**
 * Renders the GoHighLevel embed when configured, otherwise the native form.
 * @var string $type appointment|contact|benefits
 */
$embedKey = ['appointment' => 'ghl_appointment_embed', 'contact' => 'ghl_contact_embed', 'benefits' => 'ghl_benefits_embed'][$type] ?? '';
$embed = trim((string)setting($embedKey, ''));
$sent = isset($_GET['sent']);
$submitLabel = $submitLabel ?? ['appointment' => 'Request Appointment', 'benefits' => 'Check My Benefits', 'contact' => 'Send Message'][$type];
if ($embed !== ''): ?>
<div class="form-embed"><?= $embed /* trusted embed from admin settings */ ?></div>
<?php return; endif; ?>
<form class="form" method="post" action="<?= e(url('api/form')) ?>" data-form novalidate>
  <input type="hidden" name="_form" value="<?= e($type) ?>">
  <input type="hidden" name="_token" value="<?= e(Forms::token()) ?>">
  <input type="hidden" name="_page" value="<?= e(strtok($_SERVER['REQUEST_URI'] ?? '/', '?')) ?>">
  <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
  <?php if ($sent): ?>
  <div class="form__success" role="status"><?= icon('check-circle') ?><div><strong>Thank you!</strong><p>We received your request and will be in touch shortly.</p></div></div>
  <?php endif; ?>
  <div class="form__grid">
    <?php foreach (Forms::fields($type) as $name => $f):
        $id = $type . '-' . $name;
        $req = !empty($f['required']); ?>
    <div class="field<?= !empty($f['half']) ? ' field--half' : '' ?>">
      <label for="<?= e($id) ?>"><?= e($f['label']) ?><?= $req ? ' <span class="req" aria-hidden="true">*</span>' : '' ?></label>
      <?php if ($f['type'] === 'select'): ?>
      <select id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $req ? ' required' : '' ?>>
        <option value="">Select…</option>
        <?php foreach ($f['options'] as $o): ?><option><?= e($o) ?></option><?php endforeach; ?>
      </select>
      <?php elseif ($f['type'] === 'textarea'): ?>
      <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="4"<?= $req ? ' required' : '' ?> placeholder="<?= e($f['placeholder'] ?? '') ?>"></textarea>
      <?php else: ?>
      <input id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= e($f['type']) ?>"<?= $req ? ' required' : '' ?><?= !empty($f['autocomplete']) ? ' autocomplete="' . e($f['autocomplete']) . '"' : '' ?><?= !empty($f['placeholder']) ? ' placeholder="' . e($f['placeholder']) . '"' : '' ?><?= $f['type'] === 'tel' ? ' inputmode="tel"' : '' ?>>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <div class="field field--check">
      <input type="checkbox" id="<?= e($type) ?>-consent" name="consent" value="1" required>
      <label for="<?= e($type) ?>-consent">I agree to be contacted by <?= e(setting('site_short_name')) ?> by phone, text or email about this request. Message and data rates may apply. Please don't include detailed medical information in this form.</label>
    </div>
  </div>
  <div class="form__actions">
    <button class="btn btn--accent btn--lg" type="submit"><span><?= e($submitLabel) ?></span><?= icon('arrow-right') ?></button>
    <p class="form__note"><?= icon('shield-check') ?>Prefer to talk? Call or text <a href="<?= e(tel_href(setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p>
  </div>
  <div class="form__status" role="status" aria-live="polite"></div>
</form>
