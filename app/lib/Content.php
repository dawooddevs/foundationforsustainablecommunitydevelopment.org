<?php
/**
 * Read-side content repository used by the public templates.
 */
final class Content
{
    /**
     * Public URL prefixes: services live at /SERVICE_BASE/<slug>/ and provider profiles at
     * /PROVIDER_BASE/<slug>/. Change these to match the existing site's URLs (then add
     * redirects for any old URLs in Dashboard → Redirects).
     */
    public const SERVICE_BASE = 'services';
    public const PROVIDER_BASE = 'team';

    /** Service categories (dashboard dropdown, menus, filters). Edit to match the practice's services. */
    public const CATEGORIES = [
        'chiropractic' => ['label' => 'Chiropractic Care', 'short' => 'Chiropractic', 'icon' => 'spine', 'blurb' => 'Adjustments and hands-on care for the spine and joints.'],
        'therapy' => ['label' => 'Therapy & Rehab', 'short' => 'Therapy', 'icon' => 'activity', 'blurb' => 'Soft tissue therapy, exercise and rehabilitation.'],
        'injury' => ['label' => 'Injury Care', 'short' => 'Injury', 'icon' => 'car', 'blurb' => 'Care after auto, work and sports injuries.'],
        'wellness' => ['label' => 'Wellness', 'short' => 'Wellness', 'icon' => 'heart-pulse', 'blurb' => 'Ongoing care to help you stay well.'],
    ];

    /** FAQ groups: where a FAQ is shown. Templates read one group with Content::faqs('<key>'). */
    public const FAQ_GROUPS = [
        'home' => 'Homepage',
        'general' => 'FAQ page / general',
        'new-patients' => 'New patients',
        'billing' => 'Billing & insurance',
    ];

    public const PROVIDER_TYPES = [
        'chiropractor' => 'Chiropractors',
        'massage_therapist' => 'Massage Therapists',
        'therapist' => 'Therapists',
        'staff' => 'Office Team',
        'other' => 'Clinical Team',
    ];

    public const BODY_AREAS = [
        'neck' => 'Neck', 'shoulder' => 'Shoulder', 'back' => 'Back', 'hip' => 'Hip', 'knee' => 'Knee',
        'elbow' => 'Elbow', 'wrist-hand' => 'Wrist / Hand', 'leg' => 'Leg', 'ankle-foot' => 'Ankle / Foot', 'joint' => 'General Joint Pain',
    ];

    private static array $cache = [];

    /** Treatments for the homepage explorer: the featured list, or every published treatment in a category. */
    public static function explorerServices(string $cat): array
    {
        if ($cat === 'featured') {
            return self::servicesBySlugs(csv_list(setting('featured_services')));
        }
        return array_values(array_filter(self::services(), fn($s) => $s['category'] === $cat));
    }

    public static function services(bool $publishedOnly = true): array
    {
        $key = 'services' . (int)$publishedOnly;
        if (!isset(self::$cache[$key])) {
            $sql = 'SELECT * FROM services' . ($publishedOnly ? " WHERE status = 'published'" : '') . ' ORDER BY sort_order, title';
            self::$cache[$key] = DB::all($sql);
        }
        return self::$cache[$key];
    }

    public static function servicesByCategory(): array
    {
        $out = array_fill_keys(array_keys(self::CATEGORIES), []);
        foreach (self::services() as $s) {
            $out[$s['category']][] = $s;
        }
        return $out;
    }

    public static function service(string $slug): ?array
    {
        return DB::one("SELECT * FROM services WHERE slug = ? AND status = 'published'", [$slug]);
    }

    public static function servicesBySlugs(array $slugs): array
    {
        $by = [];
        foreach (self::services() as $s) {
            $by[$s['slug']] = $s;
        }
        $out = [];
        foreach ($slugs as $slug) {
            if (isset($by[$slug])) {
                $out[] = $by[$slug];
            }
        }
        return $out;
    }

    public static function providers(): array
    {
        if (!isset(self::$cache['providers'])) {
            self::$cache['providers'] = DB::all("SELECT * FROM providers WHERE status = 'published' ORDER BY sort_order, name");
        }
        return self::$cache['providers'];
    }

    public static function provider(string $slug): ?array
    {
        return DB::one("SELECT * FROM providers WHERE slug = ? AND status = 'published'", [$slug]);
    }

    public static function page(string $slug): ?array
    {
        return DB::one("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$slug]);
    }

    public static function locations(): array
    {
        if (!isset(self::$cache['locations'])) {
            self::$cache['locations'] = DB::all("SELECT * FROM locations WHERE status = 'published' ORDER BY sort_order, name");
        }
        return self::$cache['locations'];
    }

    public static function testimonials(bool $featuredOnly = false): array
    {
        $sql = "SELECT * FROM testimonials WHERE status = 'published' AND content IS NOT NULL AND content <> ''" . ($featuredOnly ? ' AND featured = 1' : '') . ' ORDER BY sort_order, id';
        return DB::all($sql);
    }

    public static function faqs(string $group): array
    {
        return DB::all("SELECT * FROM faqs WHERE grp = ? AND status = 'published' ORDER BY sort_order, id", [$group]);
    }

    public static function serviceUrl(array $s): string
    {
        return url(self::SERVICE_BASE . '/' . $s['slug'] . '/');
    }

    public static function providerUrl(array $p): string
    {
        return url(self::PROVIDER_BASE . '/' . $p['slug'] . '/');
    }

    public static function categoryLabel(string $cat): string
    {
        return self::CATEGORIES[$cat]['label'] ?? ucfirst($cat);
    }

    public static function serviceIcon(array $s): string
    {
        return $s['icon'] ?: (self::CATEGORIES[$s['category']]['icon'] ?? 'activity');
    }

    /** Related services: explicit list first, then same-category siblings. */
    public static function related(array $s, int $limit = 4): array
    {
        $out = self::servicesBySlugs(csv_list($s['related'] ?? ''));
        if (count($out) < $limit) {
            foreach (self::services() as $o) {
                if ($o['category'] === $s['category'] && $o['slug'] !== $s['slug'] && !in_array($o, $out, true)) {
                    $out[] = $o;
                }
                if (count($out) >= $limit) {
                    break;
                }
            }
        }
        return array_slice(array_values(array_filter($out, fn($o) => $o['slug'] !== $s['slug'])), 0, $limit);
    }

    public static function bodyAreaMap(): array
    {
        // Group matches per area and category, then interleave categories so
        // each area shows a balanced mix (max 8) rather than one category.
        $buckets = array_fill_keys(array_keys(self::BODY_AREAS), []);
        foreach (self::services() as $s) {
            foreach (csv_list($s['body_areas']) as $a) {
                if (isset($buckets[$a])) {
                    $buckets[$a][$s['category']][] = [
                        'title' => $s['menu_label'] ?: $s['title'],
                        'url' => self::serviceUrl($s),
                        'category' => self::CATEGORIES[$s['category']]['short'] ?? '',
                    ];
                }
            }
        }
        $map = [];
        foreach ($buckets as $area => $cats) {
            $map[$area] = [];
            while (count($map[$area]) < 8 && $cats) {
                foreach ($cats as $c => &$items) {
                    if ($items && count($map[$area]) < 8) {
                        $map[$area][] = array_shift($items);
                    }
                    if (!$items) {
                        unset($cats[$c]);
                    }
                }
                unset($items);
            }
        }
        return $map;
    }

    /**
     * Downloadable patient PDFs shown on the appointment page. Each item is linked from its
     * Settings → Patient forms value, or found in the Media Library by 'file' name. Items with
     * no file are hidden, and a group with no available items is not shown.
     */
    public const FORM_GROUPS = [
        'patient' => [
            'title' => 'Patient Forms', 'icon' => 'clipboard',
            'text' => 'New patient paperwork. Complete it before your first visit to save time at check-in.',
            'items' => [
                ['label' => 'New Patient Form — English', 'file' => 'new-patient-form-english.pdf', 'setting' => 'form_new_patient_en'],
                ['label' => 'New Patient Form — Spanish', 'file' => 'new-patient-form-spanish.pdf', 'setting' => 'form_new_patient_es'],
            ],
        ],
        'accident' => [
            'title' => 'Accident Forms', 'icon' => 'car',
            'text' => 'New patient paperwork with accident information, for car accident and injury visits.',
            'items' => [
                ['label' => 'Accident Forms — English', 'file' => 'accident-form-english.pdf', 'setting' => 'form_accident_en'],
                ['label' => 'Accident Forms — Spanish', 'file' => 'accident-form-spanish.pdf', 'setting' => 'form_accident_es'],
            ],
        ],
    ];

    private static ?array $formGroups = null;

    /** FORM_GROUPS with each item resolved to a url and its source (setting or library); missing items are dropped. */
    public static function formGroups(): array
    {
        if (self::$formGroups !== null) {
            return self::$formGroups;
        }
        $library = Media::byMatchKey('document');
        $groups = [];
        foreach (self::FORM_GROUPS as $gk => $g) {
            $items = [];
            foreach ($g['items'] as $f) {
                $set = isset($f['setting']) ? trim((string)setting($f['setting'], '')) : '';
                $path = Media::findByName($library, $f['file'])['path'] ?? null;
                if ($set !== '') {
                    $items[] = $f + ['url' => media_url($set), 'source' => 'setting'];
                } elseif ($path) {
                    $items[] = $f + ['url' => media_url($path), 'source' => 'library'];
                }
            }
            if ($items) {
                $groups[$gk] = ['items' => $items] + $g;
            }
        }
        return self::$formGroups = $groups;
    }

    /** Short list for menus and sidebars: every available patient PDF. */
    public static function patientForms(): array
    {
        $out = [];
        foreach (self::formGroups() as $g) {
            foreach ($g['items'] as $f) {
                $out[] = ['label' => $f['label'], 'url' => $f['url'], 'available' => true, 'download' => true, 'source' => $f['source']];
            }
        }
        return $out;
    }

    /**
     * Site videos. Each is found in the Media Library by 'file' name (or one of its 'alt' names,
     * see Media::matchKey), unless a different file is chosen in Settings → Videos. Add entries
     * here (and a matching setting in Settings/Resources) when the practice supplies videos.
     */
    public const VIDEOS = [];

    private static ?array $videos = null;

    /** All VIDEOS resolved: url, mime and source ('setting', 'library' or 'missing'). */
    public static function videos(): array
    {
        if (self::$videos !== null) {
            return self::$videos;
        }
        $library = Media::byMatchKey('video');
        $out = [];
        foreach (self::VIDEOS as $key => $v) {
            $set = trim((string)setting($v['setting'], ''));
            $row = null;
            foreach (array_merge([$v['file']], $v['alt'] ?? []) as $name) {
                if ($row = Media::findByName($library, $name)) break;
            }
            $path = $set !== '' ? $set : ($row['path'] ?? '');
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $out[$key] = $v + [
                'url' => $path !== '' ? media_url($path) : '',
                'mime' => ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/mp4'][$ext] ?? 'video/mp4',
                'source' => $set !== '' ? 'setting' : ($row ? 'library' : 'missing'),
            ];
        }
        return self::$videos = $out;
    }

    /** The requested videos that are available, in order. */
    public static function videoList(array $keys): array
    {
        $all = self::videos();
        return array_values(array_filter(array_map(fn($k) => $all[$k] ?? null, $keys), fn($v) => $v && $v['url'] !== ''));
    }

    /**
     * Find a provider's headshot among image rows by name: every part of their name except titles,
     * credentials and initials must appear in the file name ("Dr. Jane Smith.png",
     * "jane-smith.jpg" and "provider-dr-jane-smith.webp" all match Dr. Jane Smith), falling
     * back to the last name alone. Newest upload wins.
     */
    public static function providerPhotoMatch(array $provider, array $images): ?array
    {
        $skip = ['dr', 'doctor', 'md', 'dc', 'do', 'pa', 'pac', 'np', 'provider', 'headshot', 'photo', 'image', 'img'];
        $words = fn(string $s): array => array_values(array_filter(explode('-', slugify($s)), fn($w) => strlen($w) > 1 && !in_array($w, $skip, true)));
        $need = $words((string)$provider['name']);
        if (!$need) {
            return null;
        }
        // Full name first; then the last name alone (e.g. "Dr.-Smith.png"), as last names are unique on the team.
        foreach ([$need, [end($need)]] as $tokens) {
            $best = null;
            foreach ($images as $m) {
                $name = pathinfo((string)($m['original_name'] ?: $m['path']), PATHINFO_FILENAME);
                $have = array_merge($words($name), $words(pathinfo((string)$m['path'], PATHINFO_FILENAME)));
                if (!array_diff($tokens, $have) && (!$best || (int)$m['id'] > (int)$best['id'])) {
                    $best = $m;
                }
            }
            if ($best) {
                return $best;
            }
        }
        return null;
    }

    public static function hours(array $loc): array
    {
        return json_list($loc['hours'] ?? '[]');
    }

    /**
     * Combined weekly hours for several locations, grouped into rows of consecutive days with the
     * same hours everywhere: [['days' => 'Tue – Wed', 'iso' => [2, 3], 'times' => [slug => '7:30 AM – 5:30 PM' | null]], …].
     * Built from the schema.org strings ("Mo-Th 07:30-17:30"); returns [] if any location lacks them.
     */
    public static function hoursTable(array $locs): array
    {
        $codes = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
        $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $fmt = function (string $t): string {
            [$h, $m] = array_map('intval', explode(':', $t));
            return ($h % 12 ?: 12) . ':' . str_pad((string)$m, 2, '0', STR_PAD_LEFT) . ($h < 12 ? ' AM' : ' PM');
        };
        $week = [];
        foreach ($locs as $l) {
            $days = array_fill(0, 7, null);
            foreach (self::hours($l) as $h) {
                if (empty($h['schema'])) {
                    if (stripos((string)($h['time'] ?? ''), 'closed') === false) return [];
                    continue;
                }
                foreach ((array)$h['schema'] as $spec) {
                    if (!preg_match('/^([A-Za-z,\-]+)\s+(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/', trim($spec), $m)) return [];
                    foreach (explode(',', $m[1]) as $part) {
                        $range = explode('-', $part);
                        $a = array_search($range[0], $codes, true);
                        $b = array_search($range[1] ?? $range[0], $codes, true);
                        if ($a === false || $b === false) return [];
                        for ($d = $a; $d <= $b; $d++) $days[$d] = $fmt($m[2]) . ' – ' . $fmt($m[3]);
                    }
                }
            }
            $week[$l['slug']] = $days;
        }
        $rows = [];
        for ($d = 0; $d < 7; $d++) {
            $times = array_map(fn($days) => $days[$d], $week);
            $last = count($rows) - 1;
            if ($last >= 0 && $rows[$last]['times'] === $times) {
                $rows[$last]['to'] = $d;
            } else {
                $rows[] = ['from' => $d, 'to' => $d, 'times' => $times];
            }
        }
        return array_map(fn($r) => [
            'days' => $names[$r['from']] . ($r['to'] > $r['from'] ? ' – ' . $names[$r['to']] : ''),
            'iso' => range($r['from'] + 1, $r['to'] + 1), // ISO weekday numbers, for highlighting "today" in the browser
            'times' => $r['times'],
        ], $rows);
    }

    public static function mapsDirections(array $loc): string
    {
        if (!empty($loc['directions_url'])) {
            return $loc['directions_url'];
        }
        $q = $loc['address'] . ', ' . $loc['city'] . ', ' . $loc['state'] . ' ' . $loc['zip'];
        return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($q);
    }

    public static function mapEmbed(array $loc): string
    {
        if (!empty($loc['map_embed'])) {
            if (preg_match('~src="([^"]+)"~', $loc['map_embed'], $m)) {
                return html_entity_decode($m[1]);
            }
            return $loc['map_embed'];
        }
        $q = $loc['address'] . ', ' . $loc['city'] . ', ' . $loc['state'] . ' ' . $loc['zip'];
        return 'https://www.google.com/maps?q=' . rawurlencode(setting('site_name') . ', ' . $q) . '&output=embed';
    }

    public static function fullAddress(array $loc): string
    {
        return $loc['address'] . ', ' . $loc['city'] . ', ' . $loc['state'] . ' ' . $loc['zip'];
    }
}
