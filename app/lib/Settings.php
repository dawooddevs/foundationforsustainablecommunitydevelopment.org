<?php
/**
 * Key/value site settings with sensible defaults. Everything here is editable
 * from Dashboard → Settings.
 */
final class Settings
{
    private static ?array $cache = null;

    public static function defaults(): array
    {
        return [
            // General
            'site_name' => 'Foundation for Sustainable Community Development',
            'site_short_name' => 'FSCD',
            'tagline' => 'Empowering Communities. Building Futures.',
            'brand_description' => '',
            'phone' => '',
            'sms_phone' => '',
            'email' => 'info@thefscd.org',
            'notify_email' => '',
            'communities' => '',
            'founded_year' => '',

            // Branding (the dashboard also uses color_primary and color_accent)
            'logo' => '',
            'logo_light' => '',
            'favicon' => '',
            'color_primary' => '#062b4b',
            'color_accent' => '#4c8f2f',
            'color_highlight' => '#2f7cf6',

            // Homepage (optional fields the frontend may use)
            'hero_line1' => '',
            'hero_line2' => '',
            'hero_copy' => '',
            'hero_image' => '',
            'hero_badges' => '',
            'about_image' => '',
            'testimonials_image' => '',
            'stats' => json_encode([]),
            'featured_services' => '',

            // Integrations
            'ghl_appointment_embed' => '',
            'ghl_contact_embed' => '',
            'ghl_benefits_embed' => '',
            'ghl_webhook_url' => '',
            'chat_widget' => '',
            'head_scripts' => '',
            'body_scripts' => '',
            'ga_id' => '',

            // Patient forms (media library URLs)
            'form_new_patient_en' => '',
            'form_new_patient_es' => '',
            'form_accident_en' => '',
            'form_accident_es' => '',
            'form_pain_questionnaire' => '',

            // Social
            'social_facebook' => '',
            'social_x' => '',
            'social_instagram' => '',

            // SEO
            'seo_title_suffix' => ' | FSCD',
            'seo_default_description' => '',
            'seo_og_image' => '',
            'seo_noindex' => '1',
            'cookie_notice' => '0',

            // Appointment CTA
            'cta_headline' => 'Ready to Take the Next Step?',
            'cta_copy' => '',
            'cta_small_print' => '',
        ];
    }

    private static function load(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            if (DB::connected()) {
                try {
                    foreach (DB::all('SELECT k, v FROM settings') as $r) {
                        self::$cache[$r['k']] = $r['v'];
                    }
                } catch (Throwable $e) {
                    // table may not exist yet during install
                }
            }
        }
        return self::$cache;
    }

    public static function get(string $key, $default = null)
    {
        $all = self::load();
        if (array_key_exists($key, $all) && $all[$key] !== null) {
            return $all[$key];
        }
        $d = self::defaults();
        return $d[$key] ?? $default;
    }

    public static function all(): array
    {
        return array_merge(self::defaults(), array_filter(self::load(), fn($v) => $v !== null));
    }

    public static function set(string $key, $value): void
    {
        $value = is_array($value) ? json_encode($value) : (string)$value;
        if (DB::val('SELECT COUNT(*) FROM settings WHERE k = ?', [$key])) {
            DB::update('settings', ['v' => $value], 'k = ?', [$key]);
        } else {
            DB::insert('settings', ['k' => $key, 'v' => $value]);
        }
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
