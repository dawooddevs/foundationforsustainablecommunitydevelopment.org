<?php
/**
 * Minimal page shell for raw-HTML pages (template "html"). It deliberately loads none of the
 * scaffold CSS/JS: each page carries its own styles and scripts, exactly as the original
 * Elementor Canvas pages did (a plain <body> with margin 0).
 * @var string $content rendered page HTML  @var string $path request path
 */
$gaId = preg_replace('/[^A-Za-z0-9\-]/', '', (string)setting('ga_id'));
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= Seo::head() /* KEEP: title, meta description, canonical, Open Graph, robots, JSON-LD schema */ ?>

<?php if (setting('favicon')): ?><link rel="icon" href="<?= e(media_url(setting('favicon'))) ?>">
<?php endif; ?>
<?php if ($gaId): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
<?php endif; ?>
<?= setting('head_scripts') /* KEEP: trusted, admin-only setting */ ?>
<style>body{margin:0}</style>
</head>
<body>
<?= $content ?>
<?= setting('chat_widget') /* KEEP: trusted, admin-only setting */ ?>
<?= setting('body_scripts') /* KEEP: trusted, admin-only setting */ ?>
</body>
</html>
