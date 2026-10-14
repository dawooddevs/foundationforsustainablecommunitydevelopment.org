<?php
/**
 * Services loaded on a fresh install (empty by default). Add the practice's services here
 * BEFORE the site is installed on the server; after that, add them in the dashboard or with
 * a migration in app/migrations/ (the seed only runs once, at install).
 *
 * One entry per service. HTML is allowed in what_is, how_it_works and what_to_expect;
 * conditions and benefits take one item per line; faqs is a list of [question, answer].
 *
 *   [
 *       'slug' => 'chiropractic-adjustments', 'title' => 'Chiropractic Adjustments', 'menu_label' => '',
 *       'category' => 'chiropractic',            // a key of Content::CATEGORIES
 *       'icon' => 'spine',                       // a key of app/lib/icons.php
 *       'featured' => 1, 'status' => 'published', // or 'draft'
 *       'excerpt' => 'One or two sentences for cards and meta descriptions.',
 *       'hero_text' => 'Intro line under the page title.',
 *       'what_is' => '<p>…</p>', 'how_it_works' => '<p>…</p>', 'what_to_expect' => '<p>…</p>',
 *       'conditions' => "Low back pain\nNeck pain",
 *       'benefits' => "Drug-free\nNon-surgical",
 *       'faqs' => [['Question?', 'Answer.']],
 *       'related' => 'other-slug,another-slug',   // related services
 *       'body_areas' => 'neck,back',              // keys of Content::BODY_AREAS
 *       'meta_title' => '', 'meta_description' => '',
 *   ],
 */
return [];
