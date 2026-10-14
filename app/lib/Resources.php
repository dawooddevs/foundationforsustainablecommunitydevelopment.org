<?php
/**
 * Content type definitions. One definition drives the dashboard list views,
 * the edit forms (rendered client-side from this schema) and server-side
 * validation/sanitisation.
 */
final class Resources
{
    public static function all(): array
    {
        $cats = [];
        foreach (Content::CATEGORIES as $k => $c) {
            $cats[] = ['value' => $k, 'label' => $c['label']];
        }
        $ptypes = [];
        foreach (Content::PROVIDER_TYPES as $k => $l) {
            $ptypes[] = ['value' => $k, 'label' => $l];
        }
        $areas = [];
        foreach (Content::BODY_AREAS as $k => $l) {
            $areas[] = ['value' => $k, 'label' => $l];
        }
        $status = [['value' => 'published', 'label' => 'Published'], ['value' => 'draft', 'label' => 'Draft']];
        $icons = array_map(fn($k) => ['value' => $k, 'label' => $k], array_keys(require APP . '/lib/icons.php'));
        array_unshift($icons, ['value' => '', 'label' => 'Category default']);
        $seo = [
            ['key' => 'meta_title', 'label' => 'SEO title', 'type' => 'text', 'group' => 'seo', 'max' => 70, 'help' => 'Shown in Google results and browser tabs. ~50–60 characters is ideal.'],
            ['key' => 'meta_description', 'label' => 'Meta description', 'type' => 'textarea', 'group' => 'seo', 'max' => 170, 'rows' => 3, 'help' => '~140–160 characters summarising the page.'],
        ];

        return [
            'services' => [
                'table' => 'services', 'label' => 'Treatments', 'singular' => 'Treatment', 'icon' => 'activity',
                'titleKey' => 'title', 'slug' => true, 'url' => Content::SERVICE_BASE . '/{slug}/', 'orderable' => true, 'review' => true,
                'search' => ['title', 'slug', 'excerpt'],
                'columns' => [
                    ['key' => 'title', 'label' => 'Treatment', 'type' => 'title'],
                    ['key' => 'category', 'label' => 'Category', 'type' => 'option'],
                    ['key' => 'featured', 'label' => 'Featured', 'type' => 'bool'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                    ['key' => 'updated_at', 'label' => 'Updated', 'type' => 'date'],
                ],
                'filters' => ['status' => $status, 'category' => $cats],
                'groups' => ['content' => 'Overview', 'sections' => 'Page sections', 'faq' => 'FAQ', 'seo' => 'SEO'],
                'fields' => array_merge([
                    ['key' => 'title', 'label' => 'Treatment name', 'type' => 'text', 'required' => true, 'group' => 'content'],
                    ['key' => 'slug', 'label' => 'URL slug', 'type' => 'slug', 'group' => 'content', 'prefix' => '/' . Content::SERVICE_BASE . '/', 'help' => 'Keep existing slugs unchanged to protect SEO. Add a redirect if you change one.'],
                    ['key' => 'menu_label', 'label' => 'Menu label', 'type' => 'text', 'group' => 'content', 'help' => 'Optional shorter/longer name used in the navigation.'],
                    ['key' => 'excerpt', 'label' => 'Card summary', 'type' => 'textarea', 'rows' => 3, 'group' => 'content', 'help' => 'Short description used on treatment cards and listings.'],
                    ['key' => 'hero_text', 'label' => 'Hero value statement', 'type' => 'textarea', 'rows' => 2, 'group' => 'content'],
                    ['key' => 'what_is', 'label' => 'What is this treatment?', 'type' => 'richtext', 'group' => 'sections'],
                    ['key' => 'conditions', 'label' => 'What can it help with?', 'type' => 'lines', 'group' => 'sections', 'help' => 'One condition per line. Only use conditions supported by existing clinical content.'],
                    ['key' => 'how_it_works', 'label' => 'How it works', 'type' => 'richtext', 'group' => 'sections'],
                    ['key' => 'benefits', 'label' => 'Benefits / goals', 'type' => 'lines', 'group' => 'sections', 'help' => 'One per line. Use careful phrasing — "may help relieve…", "designed to support…". No guaranteed results.'],
                    ['key' => 'what_to_expect', 'label' => 'What to expect', 'type' => 'richtext', 'group' => 'sections'],
                    ['key' => 'faqs', 'label' => 'Frequently asked questions', 'type' => 'repeater', 'group' => 'faq', 'add' => 'Add question',
                        'fields' => [['key' => 'q', 'label' => 'Question', 'type' => 'text'], ['key' => 'a', 'label' => 'Answer', 'type' => 'textarea', 'rows' => 3]]],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'side' => true, 'default' => 'draft'],
                    ['key' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => $cats, 'side' => true, 'required' => true],
                    ['key' => 'image', 'label' => 'Featured image', 'type' => 'image', 'side' => true, 'help' => 'Used on cards and the page hero. Leave empty for the designed placeholder.'],
                    ['key' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => $icons, 'side' => true],
                    ['key' => 'featured', 'label' => 'Featured on homepage', 'type' => 'toggle', 'side' => true],
                    ['key' => 'show_in_menu', 'label' => 'Show in mega menu', 'type' => 'toggle', 'side' => true, 'default' => 1],
                    ['key' => 'coming_soon', 'label' => 'Coming soon badge', 'type' => 'toggle', 'side' => true],
                    ['key' => 'needs_review', 'label' => 'Needs content review', 'type' => 'toggle', 'side' => true, 'help' => 'Flags this page on the dashboard until the copy is approved.'],
                    ['key' => 'body_areas', 'label' => '"Where does it hurt?" areas', 'type' => 'checks', 'options' => $areas, 'side' => true],
                    ['key' => 'related', 'label' => 'Related treatments', 'type' => 'relation', 'source' => 'services', 'side' => true, 'help' => '3–4 internal links shown at the bottom of the page.'],
                ], $seo),
            ],

            'providers' => [
                'table' => 'providers', 'label' => 'Providers', 'singular' => 'Provider', 'icon' => 'users',
                'titleKey' => 'name', 'slug' => true, 'url' => Content::PROVIDER_BASE . '/{slug}/', 'orderable' => true, 'review' => true,
                'search' => ['name', 'title', 'slug'],
                'columns' => [
                    ['key' => 'photo', 'label' => '', 'type' => 'thumb'],
                    ['key' => 'name', 'label' => 'Name', 'type' => 'title'],
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'type', 'label' => 'Type', 'type' => 'option'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                ],
                'filters' => ['status' => $status, 'type' => $ptypes],
                'groups' => ['content' => 'Profile', 'details' => 'Biography & background', 'seo' => 'SEO'],
                'fields' => array_merge([
                    ['key' => 'name', 'label' => 'Full name', 'type' => 'text', 'required' => true, 'group' => 'content'],
                    ['key' => 'slug', 'label' => 'URL slug', 'type' => 'slug', 'group' => 'content', 'prefix' => '/' . Content::PROVIDER_BASE . '/'],
                    ['key' => 'credentials', 'label' => 'Credentials', 'type' => 'text', 'group' => 'content', 'help' => 'e.g. D.C., M.D., PA-C — use existing verified credentials only.'],
                    ['key' => 'title', 'label' => 'Role / title', 'type' => 'text', 'group' => 'content'],
                    ['key' => 'short_bio', 'label' => 'Short introduction', 'type' => 'textarea', 'rows' => 3, 'group' => 'content'],
                    ['key' => 'bio', 'label' => 'Biography', 'type' => 'richtext', 'group' => 'details'],
                    ['key' => 'education', 'label' => 'Education', 'type' => 'richtext', 'group' => 'details'],
                    ['key' => 'experience', 'label' => 'Experience', 'type' => 'richtext', 'group' => 'details'],
                    ['key' => 'philosophy', 'label' => 'Treatment philosophy', 'type' => 'richtext', 'group' => 'details'],
                    ['key' => 'focus', 'label' => 'Areas of focus', 'type' => 'lines', 'group' => 'details', 'help' => 'One per line.'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'side' => true, 'default' => 'draft'],
                    ['key' => 'type', 'label' => 'Provider type', 'type' => 'select', 'options' => $ptypes, 'side' => true],
                    ['key' => 'photo', 'label' => 'Photo', 'type' => 'image', 'side' => true, 'focal' => 'photo_position', 'help' => 'Click the photo to set the focal point so faces are never cropped.'],
                    ['key' => 'photo_position', 'label' => 'Photo focal point', 'type' => 'hidden', 'side' => true, 'default' => '50% 20%'],
                    ['key' => 'locations', 'label' => 'Locations', 'type' => 'checks', 'options' => self::locationOptions(), 'side' => true],
                    ['key' => 'services', 'label' => 'Relevant services', 'type' => 'relation', 'source' => 'services', 'side' => true],
                    ['key' => 'needs_review', 'label' => 'Needs content review', 'type' => 'toggle', 'side' => true],
                ], $seo),
            ],

            'pages' => [
                'table' => 'pages', 'label' => 'Pages', 'singular' => 'Page', 'icon' => 'file-text',
                'titleKey' => 'title', 'slug' => true, 'url' => '{slug}/', 'orderable' => false, 'review' => true,
                'search' => ['title', 'slug'],
                'columns' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'title'],
                    ['key' => 'slug', 'label' => 'URL', 'type' => 'path'],
                    ['key' => 'template', 'label' => 'Template', 'type' => 'option'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                    ['key' => 'updated_at', 'label' => 'Updated', 'type' => 'date'],
                ],
                'filters' => ['status' => $status],
                'groups' => ['content' => 'Content', 'seo' => 'SEO'],
                'fields' => array_merge([
                    ['key' => 'title', 'label' => 'Page title (H1)', 'type' => 'text', 'required' => true, 'group' => 'content'],
                    ['key' => 'slug', 'label' => 'URL slug', 'type' => 'slug', 'group' => 'content', 'prefix' => '/'],
                    ['key' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'group' => 'content', 'help' => 'Small label above the title, e.g. PATIENT CENTER.'],
                    ['key' => 'intro', 'label' => 'Intro text', 'type' => 'textarea', 'rows' => 3, 'group' => 'content'],
                    ['key' => 'content', 'label' => 'Body content', 'type' => 'richtext', 'group' => 'content'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'side' => true, 'default' => 'draft'],
                    ['key' => 'template', 'label' => 'Template', 'type' => 'select', 'side' => true, 'options' => [
                        ['value' => 'default', 'label' => 'Standard content'], ['value' => 'legal', 'label' => 'Legal / narrow'],
                        ['value' => 'treatments', 'label' => 'Services index'],
                        ['value' => 'appointment', 'label' => 'Appointment'], ['value' => 'contact', 'label' => 'Contact'],
                        ['value' => 'sitemap', 'label' => 'Sitemap'],
                    ]],
                    ['key' => 'image', 'label' => 'Hero image', 'type' => 'image', 'side' => true],
                    ['key' => 'show_cta', 'label' => 'Show appointment CTA', 'type' => 'toggle', 'side' => true, 'default' => 1],
                    ['key' => 'needs_review', 'label' => 'Needs content review', 'type' => 'toggle', 'side' => true],
                ], $seo),
            ],

            'testimonials' => [
                'table' => 'testimonials', 'label' => 'Testimonials', 'singular' => 'Testimonial', 'icon' => 'quote',
                'titleKey' => 'name', 'slug' => false, 'orderable' => true,
                'search' => ['name', 'content'],
                'columns' => [
                    ['key' => 'name', 'label' => 'Patient', 'type' => 'title'],
                    ['key' => 'content', 'label' => 'Testimonial', 'type' => 'excerpt'],
                    ['key' => 'featured', 'label' => 'Homepage', 'type' => 'bool'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                ],
                'filters' => ['status' => $status],
                'groups' => ['content' => 'Testimonial'],
                'fields' => [
                    ['key' => 'name', 'label' => 'Patient name (as published)', 'type' => 'text', 'required' => true, 'group' => 'content'],
                    ['key' => 'label', 'label' => 'Label', 'type' => 'text', 'group' => 'content', 'help' => 'e.g. Verified Patient'],
                    ['key' => 'content', 'label' => 'Testimonial text', 'type' => 'textarea', 'rows' => 8, 'group' => 'content', 'help' => 'Use the original wording. Never change a testimonial\'s meaning.'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'side' => true, 'default' => 'draft'],
                    ['key' => 'rating', 'label' => 'Star rating', 'type' => 'select', 'side' => true, 'default' => '5', 'options' => array_map(fn($n) => ['value' => (string)$n, 'label' => $n . ' stars'], [5, 4, 3, 2, 1])],
                    ['key' => 'featured', 'label' => 'Show on homepage', 'type' => 'toggle', 'side' => true, 'default' => 1],
                ],
            ],

            'faqs' => [
                'table' => 'faqs', 'label' => 'FAQs', 'singular' => 'FAQ', 'icon' => 'message-square',
                'titleKey' => 'question', 'slug' => false, 'orderable' => true,
                'search' => ['question', 'answer'],
                'columns' => [
                    ['key' => 'question', 'label' => 'Question', 'type' => 'title'],
                    ['key' => 'grp', 'label' => 'Shown on', 'type' => 'option'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                ],
                'filters' => ['status' => $status, 'grp' => self::options(Content::FAQ_GROUPS)],
                'groups' => ['content' => 'Question'],
                'fields' => [
                    ['key' => 'question', 'label' => 'Question', 'type' => 'text', 'required' => true, 'group' => 'content'],
                    ['key' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'rows' => 6, 'group' => 'content'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'side' => true, 'default' => 'published'],
                    ['key' => 'grp', 'label' => 'Shown on', 'type' => 'select', 'side' => true, 'options' => self::options(Content::FAQ_GROUPS)],
                ],
            ],

            'locations' => [
                'table' => 'locations', 'label' => 'Locations', 'singular' => 'Location', 'icon' => 'map-pin',
                'titleKey' => 'name', 'slug' => true, 'url' => 'locations/#{slug}', 'orderable' => true,
                'search' => ['name', 'address', 'city'],
                'columns' => [
                    ['key' => 'name', 'label' => 'Office', 'type' => 'title'],
                    ['key' => 'address', 'label' => 'Address', 'type' => 'text'],
                    ['key' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                ],
                'filters' => ['status' => $status],
                'groups' => ['content' => 'Office details', 'hours' => 'Hours', 'map' => 'Map'],
                'fields' => [
                    ['key' => 'name', 'label' => 'Office name', 'type' => 'text', 'required' => true, 'group' => 'content'],
                    ['key' => 'slug', 'label' => 'Anchor slug', 'type' => 'slug', 'group' => 'content', 'prefix' => '#'],
                    ['key' => 'address', 'label' => 'Street address', 'type' => 'text', 'group' => 'content', 'required' => true],
                    ['key' => 'city', 'label' => 'City', 'type' => 'text', 'group' => 'content', 'half' => true],
                    ['key' => 'state', 'label' => 'State', 'type' => 'text', 'group' => 'content', 'half' => true],
                    ['key' => 'zip', 'label' => 'ZIP', 'type' => 'text', 'group' => 'content', 'half' => true, 'help' => 'Keep NAP (name, address, phone) identical everywhere.'],
                    ['key' => 'phone', 'label' => 'Phone', 'type' => 'text', 'group' => 'content', 'half' => true],
                    ['key' => 'fax', 'label' => 'Fax', 'type' => 'text', 'group' => 'content', 'half' => true],
                    ['key' => 'description', 'label' => 'Short description', 'type' => 'textarea', 'rows' => 3, 'group' => 'content'],
                    ['key' => 'hours', 'label' => 'Office hours', 'type' => 'repeater', 'group' => 'hours', 'add' => 'Add hours row',
                        'fields' => [['key' => 'days', 'label' => 'Days', 'type' => 'text'], ['key' => 'time', 'label' => 'Hours', 'type' => 'text'], ['key' => 'schema', 'label' => 'Schema (e.g. Mo-Th 07:30-17:30)', 'type' => 'text']]],
                    ['key' => 'map_embed', 'label' => 'Google Maps embed URL or <iframe> code', 'type' => 'textarea', 'rows' => 3, 'group' => 'map', 'help' => 'Optional. Google Maps → Share → Embed a map → copy HTML. Leave empty to auto-generate from the address.'],
                    ['key' => 'directions_url', 'label' => 'Directions link', 'type' => 'text', 'group' => 'map', 'help' => 'Optional Google Business Profile link.'],
                    ['key' => 'latitude', 'label' => 'Latitude', 'type' => 'text', 'group' => 'map', 'half' => true],
                    ['key' => 'longitude', 'label' => 'Longitude', 'type' => 'text', 'group' => 'map', 'half' => true],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $status, 'side' => true, 'default' => 'published'],
                    ['key' => 'image', 'label' => 'Office photo', 'type' => 'image', 'side' => true],
                ],
            ],

            'redirects' => [
                'table' => 'redirects', 'label' => 'Redirects', 'singular' => 'Redirect', 'icon' => 'route',
                'titleKey' => 'source', 'slug' => false, 'orderable' => false, 'cap' => 'redirects.manage',
                'search' => ['source', 'target', 'note'],
                'columns' => [
                    ['key' => 'source', 'label' => 'From', 'type' => 'title'],
                    ['key' => 'target', 'label' => 'To', 'type' => 'path'],
                    ['key' => 'code', 'label' => 'Type', 'type' => 'text'],
                    ['key' => 'hits', 'label' => 'Hits', 'type' => 'number'],
                ],
                'filters' => [],
                'groups' => ['content' => 'Redirect'],
                'fields' => [
                    ['key' => 'source', 'label' => 'Old path', 'type' => 'text', 'required' => true, 'group' => 'content', 'placeholder' => '/old-page/', 'help' => 'Path on this site, starting with /'],
                    ['key' => 'target', 'label' => 'New path or URL', 'type' => 'text', 'required' => true, 'group' => 'content', 'placeholder' => '/service/new-page/'],
                    ['key' => 'code', 'label' => 'Redirect type', 'type' => 'select', 'group' => 'content', 'default' => '301', 'options' => [['value' => '301', 'label' => '301 — Permanent (SEO)'], ['value' => '302', 'label' => '302 — Temporary']]],
                    ['key' => 'note', 'label' => 'Note', 'type' => 'text', 'group' => 'content'],
                ],
            ],
        ];
    }

    /** [key => label] to the [['value'=>…, 'label'=>…]] option format used by the dashboard. */
    private static function options(array $map): array
    {
        return array_map(fn($k, $l) => ['value' => $k, 'label' => $l], array_keys($map), $map);
    }

    /** Location checkboxes for provider profiles, from the Locations table. */
    private static function locationOptions(): array
    {
        try {
            return array_map(fn($l) => ['value' => $l['slug'], 'label' => $l['name']], DB::all('SELECT slug, name FROM locations ORDER BY sort_order, name'));
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    /** Settings screen definition (admin only). */
    public static function settings(): array
    {
        return [
            'general' => ['label' => 'General', 'icon' => 'building', 'fields' => [
                ['key' => 'site_name', 'label' => 'Full brand name', 'type' => 'text'],
                ['key' => 'site_short_name', 'label' => 'Short name', 'type' => 'text'],
                ['key' => 'tagline', 'label' => 'Positioning / tagline', 'type' => 'text'],
                ['key' => 'brand_description', 'label' => 'Footer brand description', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'phone', 'label' => 'Main phone (call)', 'type' => 'text', 'half' => true],
                ['key' => 'sms_phone', 'label' => 'Text / SMS number', 'type' => 'text', 'half' => true],
                ['key' => 'email', 'label' => 'Public email (optional)', 'type' => 'text', 'half' => true],
                ['key' => 'notify_email', 'label' => 'Form notification email', 'type' => 'text', 'half' => true, 'help' => 'New form submissions are emailed here.'],
                ['key' => 'communities', 'label' => 'Communities served (comma separated)', 'type' => 'text'],
                ['key' => 'founded_year', 'label' => 'Founded (year)', 'type' => 'text', 'half' => true],
                ['key' => 'cookie_notice', 'label' => 'Show cookie notice', 'type' => 'toggle'],
            ]],
            'branding' => ['label' => 'Branding', 'icon' => 'sparkles', 'fields' => [
                ['key' => 'logo', 'label' => 'Logo (light backgrounds)', 'type' => 'image', 'help' => 'Leave empty to use the built-in wordmark.'],
                ['key' => 'logo_light', 'label' => 'Logo (dark footer)', 'type' => 'image'],
                ['key' => 'favicon', 'label' => 'Favicon', 'type' => 'image'],
                ['key' => 'color_primary', 'label' => 'Primary (deep blue)', 'type' => 'color', 'third' => true],
                ['key' => 'color_accent', 'label' => 'Accent (sky blue, buttons)', 'type' => 'color', 'third' => true],
                ['key' => 'color_highlight', 'label' => 'Highlight', 'type' => 'color', 'third' => true],
            ]],
            'homepage' => ['label' => 'Homepage', 'icon' => 'heart-pulse', 'fields' => [
                ['key' => 'hero_line1', 'label' => 'Headline — line 1', 'type' => 'text', 'half' => true],
                ['key' => 'hero_line2', 'label' => 'Headline — line 2 (accent)', 'type' => 'text', 'half' => true],
                ['key' => 'hero_copy', 'label' => 'Hero supporting copy', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'hero_badges', 'label' => 'Trust indicators', 'type' => 'lines', 'help' => 'One per line.'],
                ['key' => 'hero_image', 'label' => 'Hero photo', 'type' => 'image', 'help' => 'Media → Auto-assign uses a file named home-hero.'],
                ['key' => 'about_image', 'label' => 'About section photo', 'type' => 'image', 'help' => 'Media → Auto-assign uses a file named home-about.'],
                ['key' => 'testimonials_image', 'label' => 'Testimonials section photo', 'type' => 'image', 'help' => 'Shown beside patient testimonials on the homepage. Media → Auto-assign uses a file named home-testimonials.'],
                ['key' => 'stats', 'label' => 'Experience statistics', 'type' => 'repeater', 'add' => 'Add statistic', 'help' => 'Never invent statistics — add a 4th only if verified.',
                    'fields' => [['key' => 'value', 'label' => 'Number', 'type' => 'text'], ['key' => 'suffix', 'label' => 'Suffix', 'type' => 'text'], ['key' => 'label', 'label' => 'Label', 'type' => 'text']]],
                ['key' => 'featured_services', 'label' => 'Featured treatments (explorer)', 'type' => 'relation', 'source' => 'services'],
                ['key' => 'cta_headline', 'label' => 'Appointment CTA headline', 'type' => 'text'],
                ['key' => 'cta_copy', 'label' => 'Appointment CTA copy', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'cta_small_print', 'label' => 'Appointment small print', 'type' => 'textarea', 'rows' => 2],
            ]],
            'integrations' => ['label' => 'Forms & Integrations', 'icon' => 'zap', 'fields' => [
                ['key' => 'ghl_appointment_embed', 'label' => 'GoHighLevel appointment form embed', 'type' => 'code', 'help' => 'Paste the LeadConnector/GHL embed code. When set it replaces the built-in appointment form.'],
                ['key' => 'ghl_contact_embed', 'label' => 'GoHighLevel contact form embed', 'type' => 'code'],
                ['key' => 'ghl_benefits_embed', 'label' => 'GoHighLevel benefits-check form embed', 'type' => 'code'],
                ['key' => 'ghl_webhook_url', 'label' => 'GHL inbound webhook URL (built-in forms)', 'type' => 'text', 'help' => 'Optional. Built-in form submissions are POSTed here as JSON so GHL still receives every lead.'],
                ['key' => 'chat_widget', 'label' => 'Chat / text widget code', 'type' => 'code', 'help' => 'LeadConnector chat widget or other texting tool script.'],
                ['key' => 'ga_id', 'label' => 'Google Analytics 4 measurement ID', 'type' => 'text', 'placeholder' => 'G-XXXXXXXXXX'],
                ['key' => 'head_scripts', 'label' => 'Extra <head> code', 'type' => 'code', 'help' => 'Tracking pixels, verification tags, etc.'],
                ['key' => 'body_scripts', 'label' => 'Extra code before </body>', 'type' => 'code'],
            ]],
            'forms' => ['label' => 'Patient Forms', 'icon' => 'file-text', 'fields' => [
                ['key' => 'form_new_patient_en', 'label' => 'New Patient Forms — English', 'type' => 'file', 'help' => 'Optional. Leave empty to use a PDF in the Media Library named new-patient-form-english.pdf (and likewise for the others). Forms with no file are hidden.'],
                ['key' => 'form_new_patient_es', 'label' => 'New Patient Forms — Spanish', 'type' => 'file'],
                ['key' => 'form_accident_en', 'label' => 'Accident Forms — English', 'type' => 'file'],
                ['key' => 'form_accident_es', 'label' => 'Accident Forms — Spanish', 'type' => 'file'],
            ]],
            'social' => ['label' => 'Social', 'icon' => 'globe', 'fields' => [
                ['key' => 'social_facebook', 'label' => 'Facebook URL', 'type' => 'text'],
                ['key' => 'social_x', 'label' => 'X / Twitter URL', 'type' => 'text'],
                ['key' => 'social_instagram', 'label' => 'Instagram URL', 'type' => 'text'],
            ]],
            'seo' => ['label' => 'SEO', 'icon' => 'search', 'fields' => [
                ['key' => 'seo_noindex', 'label' => 'Hide site from search engines (staging)', 'type' => 'toggle', 'help' => 'Keep ON while on the staging domain. Turn OFF at launch.'],
                ['key' => 'seo_title_suffix', 'label' => 'Title suffix', 'type' => 'text'],
                ['key' => 'seo_default_description', 'label' => 'Default meta description', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'seo_og_image', 'label' => 'Default social share image', 'type' => 'image'],
            ]],
        ];
    }

    /** Clean a submitted value according to its field definition. */
    public static function clean(array $f, $v)
    {
        $type = $f['type'];
        switch ($type) {
            case 'richtext':
                return Html::clean((string)$v);
            case 'code':
                return (string)$v;
            case 'toggle':
                return $v === true || $v === 1 || $v === '1' || $v === 'true' ? 1 : 0;
            case 'number':
                return (int)$v;
            case 'slug':
                return slugify((string)$v);
            case 'select':
                $v = (string)$v;
                $allowed = array_map(fn($o) => (string)$o['value'], $f['options']);
                return in_array($v, $allowed, true) ? $v : (string)($f['default'] ?? ($allowed[0] ?? ''));
            case 'checks':
                $vals = is_array($v) ? $v : csv_list((string)$v);
                $allowed = array_map(fn($o) => (string)$o['value'], $f['options']);
                return implode(',', array_values(array_intersect($allowed, array_map('strval', $vals))));
            case 'relation':
                $vals = is_array($v) ? $v : csv_list((string)$v);
                return implode(',', array_filter(array_map(fn($s) => slugify((string)$s), $vals)));
            case 'repeater':
                $rows = is_string($v) ? json_decode($v, true) : $v;
                $out = [];
                foreach ((array)$rows as $row) {
                    if (!is_array($row)) continue;
                    $r = [];
                    $empty = true;
                    foreach ($f['fields'] as $sf) {
                        $val = trim(strip_tags((string)($row[$sf['key']] ?? '')));
                        if ($sf['key'] === 'schema') {
                            $val = array_values(array_filter(array_map('trim', explode(';', $val))));
                        } elseif ($sf['key'] === 'value' && is_numeric($val)) {
                            $val = (int)$val;
                        }
                        if ($val !== '' && $val !== []) $empty = false;
                        $r[$sf['key']] = $val;
                    }
                    if (!$empty) $out[] = $r;
                }
                return json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            case 'image':
            case 'file':
                $v = trim((string)$v);
                if ($v === '' || preg_match('~^(uploads/|https://)~', $v)) {
                    return str_contains($v, '..') ? '' : $v;
                }
                return '';
            case 'color':
                return preg_match('/^#[0-9a-f]{6}$/i', (string)$v) ? strtolower($v) : '';
            case 'hidden':
                return mb_substr(trim(strip_tags((string)$v)), 0, 60);
            case 'lines':
            case 'textarea':
                return trim(strip_tags((string)$v));
            default:
                return mb_substr(trim(strip_tags((string)$v)), 0, (int)($f['max'] ?? 500) ?: 500);
        }
    }

    /** Convert a stored row into form values for the editor. */
    public static function present(array $def, array $row): array
    {
        $out = ['id' => (int)$row['id']];
        foreach ($def['fields'] as $f) {
            $v = $row[$f['key']] ?? ($f['default'] ?? '');
            if ($f['type'] === 'repeater') {
                $rows = json_list((string)$v);
                foreach ($rows as &$r) {
                    if (isset($r['schema']) && is_array($r['schema'])) {
                        $r['schema'] = implode('; ', $r['schema']);
                    }
                }
                unset($r);
                $v = $rows;
            } elseif ($f['type'] === 'checks' || $f['type'] === 'relation') {
                $v = csv_list((string)$v);
            } elseif ($f['type'] === 'toggle') {
                $v = (int)$v === 1;
            }
            $out[$f['key']] = $v;
        }
        foreach (['created_at', 'updated_at', 'status', 'is_system'] as $k) {
            if (array_key_exists($k, $row)) {
                $out[$k] = $row[$k];
            }
        }
        if (!empty($def['url']) && !empty($row['slug'])) {
            $out['_url'] = url(str_replace('{slug}', $row['slug'], $def['url']));
        } elseif (($def['table'] ?? '') === 'pages') {
            $out['_url'] = url($row['slug'] . '/');
        }
        return $out;
    }
}
