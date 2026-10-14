<?php
/**
 * Raw-HTML page (template "html"), rendered through layout-html.php. The stored fragment is
 * trusted (only Administrators can edit it) and printed with its tokens replaced; see HtmlPage.
 * @var array $page
 */
$GLOBALS['page_layout'] = 'layout-html';
$isHome = $page['slug'] === 'home';
Seo::set([
    'title' => $page['meta_title'] ?: $page['title'],
    'description' => (string)$page['meta_description'],
    'canonical' => abs_url($isHome ? '' : $page['slug'] . '/'),
    'image' => $page['image'],
    // The homepage title is used exactly as entered; other pages get the "– Site name" suffix
    'suffix' => !$isHome,
]);
echo HtmlPage::render((string)$page['content'], $page['slug']);
