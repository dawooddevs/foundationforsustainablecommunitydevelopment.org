<?php
/** SCAFFOLD 404 page. */
Seo::set(['title' => 'Page Not Found']);
Seo::$noindex = true;
$phone = setting('phone');
?>
<section class="section">
  <div class="container narrow">
    <h1>Page Not Found</h1>
    <p>Sorry, we couldn't find that page.</p>
    <ul>
      <li><a href="<?= e(url('')) ?>">Home</a></li>
      <li><a href="<?= e(url(Content::SERVICE_BASE . '/')) ?>">Services</a></li>
      <li><a href="<?= e(url('book-appointment/')) ?>">Book an Appointment</a></li>
      <li><a href="<?= e(url('contact-us/')) ?>">Contact Us</a></li>
    </ul>
    <?php if ($phone): ?><p>Or call <a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a>.</p><?php endif; ?>
  </div>
</section>
