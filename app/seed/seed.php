<?php
/**
 * Seeds the database on a fresh install: services (app/seed/services.php), core pages and
 * redirects. Runs once, from the installer.
 *
 * Providers, locations, testimonials and homepage FAQs start empty. Add them in the
 * dashboard only from information the practice supplies; never invent them.
 */
function seed_database(): void
{
    $now = now();

    // ---------- Services ----------
    $services = require __DIR__ . '/services.php';
    foreach ($services as $i => $s) {
        $faqs = array_map(fn($f) => ['q' => $f[0], 'a' => $f[1]], $s['faqs'] ?? []);
        DB::insert('services', [
            'slug' => $s['slug'],
            'title' => $s['title'],
            'menu_label' => $s['menu_label'] ?? '',
            'category' => $s['category'],
            'excerpt' => $s['excerpt'],
            'hero_text' => $s['hero_text'],
            'image' => '',
            'icon' => $s['icon'] ?? '',
            'what_is' => Html::paragraphs($s['what_is']),
            'conditions' => $s['conditions'],
            'how_it_works' => Html::paragraphs($s['how_it_works']),
            'benefits' => $s['benefits'],
            'what_to_expect' => Html::paragraphs($s['what_to_expect']),
            'faqs' => json_encode($faqs),
            'related' => $s['related'],
            'body_areas' => $s['body_areas'],
            'featured' => (int)($s['featured'] ?? 0),
            'coming_soon' => (int)($s['coming_soon'] ?? 0),
            'show_in_menu' => (int)($s['show_in_menu'] ?? 1),
            'needs_review' => 1,
            'sort_order' => ($i + 1) * 10,
            'status' => $s['status'] ?? 'published',
            'meta_title' => $s['meta_title'] ?? $s['title'],
            'meta_description' => $s['meta_description'] ?? str_limit($s['excerpt'], 155),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // ---------- Pages ----------
    foreach (seed_pages() as $i => $p) {
        DB::insert('pages', array_merge([
            'eyebrow' => '', 'intro' => '', 'image' => '', 'content' => '', 'template' => 'default', 'show_cta' => 1, 'is_system' => 0,
            'needs_review' => 0, 'status' => 'published', 'meta_title' => '', 'meta_description' => '',
        ], $p, ['sort_order' => ($i + 1) * 10, 'created_at' => $now, 'updated_at' => $now]));
    }

    // ---------- FSCD pages: imported from app/seed/fscd/ (see BRIEF.md §6) ----------
}

function seed_pages(): array
{
    // Replaced by the FSCD page import (app/seed/fscd/manifest.json). See BRIEF.md §6.
    return [];
}
