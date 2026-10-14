<?php
/**
 * Per-request SEO state: title, description, canonical, Open Graph and JSON-LD schema.
 */
final class Seo
{
    public static string $title = '';
    public static string $description = '';
    public static string $canonical = '';
    public static string $image = '';
    public static string $type = 'website';
    public static bool $noindex = false;
    public static array $schema = [];
    public static array $breadcrumbs = [];

    public static function set(array $o): void
    {
        foreach ($o as $k => $v) {
            if (property_exists(self::class, $k)) {
                self::${$k} = $v;
            }
        }
    }

    public static function fullTitle(): string
    {
        $suffix = (string)setting('seo_title_suffix');
        $t = trim(self::$title);
        if ($t === '') {
            return setting('site_name') . ' — ' . setting('tagline');
        }
        if ($suffix !== '' && !str_contains($t, trim($suffix, ' |-'))) {
            return $t . $suffix;
        }
        return $t;
    }

    public static function addSchema(array $s): void
    {
        self::$schema[] = $s;
    }

    public static function head(): string
    {
        $desc = self::$description ?: (string)setting('seo_default_description');
        $canonical = self::$canonical ?: abs_url(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
        $image = self::$image ?: (string)setting('seo_og_image');
        $robots = (self::$noindex || setting('seo_noindex') === '1') ? 'noindex, nofollow' : 'index, follow, max-image-preview:large';
        $h = [];
        $h[] = '<title>' . e(self::fullTitle()) . '</title>';
        $h[] = '<meta name="description" content="' . e(str_limit($desc, 165)) . '">';
        $h[] = '<link rel="canonical" href="' . e($canonical) . '">';
        $h[] = '<meta name="robots" content="' . $robots . '">';
        $h[] = '<meta property="og:site_name" content="' . e(setting('site_name')) . '">';
        $h[] = '<meta property="og:type" content="' . e(self::$type) . '">';
        $h[] = '<meta property="og:title" content="' . e(self::fullTitle()) . '">';
        $h[] = '<meta property="og:description" content="' . e(str_limit($desc, 200)) . '">';
        $h[] = '<meta property="og:url" content="' . e($canonical) . '">';
        $h[] = '<meta property="og:locale" content="en_US">';
        if ($image) {
            $h[] = '<meta property="og:image" content="' . e(abs_url($image)) . '">';
            $h[] = '<meta name="twitter:card" content="summary_large_image">';
        } else {
            $h[] = '<meta name="twitter:card" content="summary">';
        }
        $schemas = array_merge([self::organization()], self::$schema);
        if (self::$breadcrumbs) {
            $items = [];
            foreach (self::$breadcrumbs as $i => $b) {
                $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $b[0], 'item' => abs_url($b[1])];
            }
            $schemas[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
        }
        foreach ($schemas as $s) {
            $h[] = '<script type="application/ld+json">' . json_encode($s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';
        }
        return implode("\n  ", $h);
    }

    public static function organization(): array
    {
        $locs = [];
        foreach (Content::locations() as $l) {
            $hours = [];
            foreach (Content::hours($l) as $row) {
                if (!empty($row['schema'])) {
                    foreach ((array)$row['schema'] as $spec) {
                        $hours[] = $spec;
                    }
                }
            }
            $loc = [
                '@type' => ['MedicalClinic', 'LocalBusiness'],
                '@id' => abs_url('locations/') . '#' . $l['slug'],
                'name' => setting('site_short_name') . ' — ' . $l['name'],
                'telephone' => '+1-' . setting('phone'),
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $l['address'],
                    'addressLocality' => $l['city'],
                    'addressRegion' => $l['state'],
                    'postalCode' => $l['zip'],
                    'addressCountry' => 'US',
                ],
                'url' => abs_url('locations/'),
            ];
            if ($l['fax']) {
                $loc['faxNumber'] = '+1-' . $l['fax'];
            }
            if ($hours) {
                $loc['openingHours'] = $hours;
            }
            if ($l['latitude'] && $l['longitude']) {
                $loc['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $l['latitude'], 'longitude' => $l['longitude']];
            }
            $locs[] = $loc;
        }
        $same = array_values(array_filter([setting('social_facebook'), setting('social_x'), setting('social_instagram')]));
        $org = [
            '@context' => 'https://schema.org',
            '@type' => ['MedicalBusiness', 'MedicalOrganization'],
            '@id' => abs_url('') . '#organization',
            'name' => setting('site_name'),
            'alternateName' => setting('site_short_name'),
            'description' => setting('brand_description'),
            'url' => abs_url(''),
            'telephone' => preg_replace('/^tel:/', '', tel_href((string)setting('phone'))),
            'foundingDate' => setting('founded_year'),
            'areaServed' => array_map(fn($c) => ['@type' => 'City', 'name' => $c], csv_list(setting('communities'))),
            'location' => $locs,
        ];
        $org = array_filter($org, fn($v) => $v !== '' && $v !== null && $v !== []);
        if (setting('logo')) {
            $org['logo'] = abs_url(setting('logo'));
        }
        if ($same) {
            $org['sameAs'] = $same;
        }
        return $org;
    }

    public static function faqSchema(array $faqs): ?array
    {
        $items = [];
        foreach ($faqs as $f) {
            $q = $f['question'] ?? $f['q'] ?? '';
            $a = $f['answer'] ?? $f['a'] ?? '';
            if ($q && $a) {
                $items[] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($a)]];
            }
        }
        return $items ? ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items] : null;
    }
}
