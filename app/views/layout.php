<?php
/**
 * Page shell. SCAFFOLD: replace the markup and styling freely, but keep the marked hooks
 * (SEO head, colours, favicon, analytics, admin-managed scripts and chat widget).
 * @var string $content rendered page view  @var string $path request path
 */
$hex = fn($v, $d) => preg_match('/^#[0-9a-f]{6}$/i', (string)$v) ? $v : $d;
$primary = $hex(setting('color_primary'), '#0c2d5e');
$accent = $hex(setting('color_accent'), '#e23744');
$gaId = preg_replace('/[^A-Za-z0-9\-]/', '', (string)setting('ga_id'));
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <?= Seo::head() /* KEEP: title, meta description, canonical, Open Graph, robots, JSON-LD schema */ ?>
  <meta name="theme-color" content="<?= e($primary) ?>">
  <link rel="stylesheet" href="<?= e(asset(is_file(ROOT . '/assets/css/site.min.css') ? 'css/site.min.css' : 'css/site.css')) ?>">
  <style>:root{--primary:<?= $primary ?>;--accent:<?= $accent ?>}</style>
  <?php if (setting('favicon')): ?><link rel="icon" href="<?= e(media_url(setting('favicon'))) ?>"><?php endif; ?>
  <?php if ($gaId): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
  <?php endif; ?>
  <?= setting('head_scripts') /* KEEP: trusted, admin-only setting */ ?>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <?php partial('header', ['path' => $path]); ?>
  <main id="main" tabindex="-1"><?= $content ?></main>
  <?php partial('footer'); ?>
  <script src="<?= e(asset(is_file(ROOT . '/assets/js/site.min.js') ? 'js/site.min.js' : 'js/site.js')) ?>" defer></script>
  <?= setting('chat_widget') /* KEEP: trusted, admin-only setting */ ?>
  <?= setting('body_scripts') /* KEEP: trusted, admin-only setting */ ?>
</body>
</html>
