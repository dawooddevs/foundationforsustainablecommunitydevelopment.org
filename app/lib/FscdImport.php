<?php
/**
 * Imports the FSCD pages prepared in app/seed/fscd/ (see that folder's README and BRIEF.md §6.2):
 * Media Library files, the shared header and footer, the 14 pages (template "html") and redirects.
 *
 * Used by the installer seed (fresh database) and by app/cli/import-fscd.php (re-runnable).
 * On a re-run, existing pages and settings are kept unless $force is set; new media files are added.
 */
final class FscdImport
{
    public const DIR = APP . '/seed/fscd';

    /** Pages in menu order (sort_order), with their SEO titles and meta descriptions. */
    public const PAGES = [
        // Meta descriptions for pages other than Home are sentences taken verbatim from the page's own
        // hero or intro text (flagged "needs review" so the team can confirm them).
        'home' => ['meta_title' => null, 'meta_description' => null],
        'about-us' => ['meta_description' => 'The Foundation for Sustainable Community Development exists to help communities build the knowledge, systems, partnerships, and practical resources needed for long-term self-reliance.'],
        'leadership' => ['meta_description' => 'FSCD pairs long-term vision with accountable governance.'],
        'dilip-limaye' => ['meta_description' => 'Dilip R. Limaye is an entrepreneur and senior executive with more than 40 years of international experience in the financing and management of sustainability programs.'],
        'our-programs' => ['meta_description' => 'The Foundation connects health care, reliable infrastructure, training, digital access and strategic partnerships so communities can build capability that lasts.'],
        'eye-care' => ['meta_description' => 'FSCD’s Vision Care Initiative is being developed for rural and underserved communities, especially in health care deserts.'],
        'clean-energy' => ['meta_description' => 'In remote communities, it can determine whether vaccines stay cold, medical equipment operates, classrooms connect to digital learning, and essential public services remain available when people need them most.'],
        'global-benefits' => ['meta_description' => 'FSCD’s framework is designed around connected outcomes rather than isolated interventions.'],
        'changing-lives' => ['meta_description' => 'FSCD is building global programs to impact underserved communities, with initial implementation activity focused in India and an international purpose.'],
        'strategic-partners' => ['meta_description' => 'Sustainable community development requires more than funding alone.'],
        'resources' => ['meta_description' => 'FSCD treats training and local capacity as essential infrastructure.'],
        'contact' => ['meta_description' => 'Share a few structured details so FSCD can understand your location, role, community need, and the kind of contribution or partnership you want to explore'],
        'privacy-policy-2' => ['meta_description' => 'This policy applies to information collected through the FSCD website and through communications submitted to the Foundation.'],
        'terms-of-service' => ['meta_description' => 'These Terms of Service govern your access to and use of the website operated by the Foundation for Sustainable Community Development ("FSCD," "we," "our," or "us").'],
    ];

    public const REDIRECTS = [
        '/privacy-policy/' => '/privacy-policy-2/',
    ];

    /** @var string[] */
    private array $log = [];
    /** @var string[] */
    private array $warnings = [];
    /** File names available in the Media Library import folder. */
    private array $mediaFiles = [];

    public function __construct(private bool $force = false, private int $userId = 0)
    {
    }

    /** Run everything. Returns ['log' => [...], 'warnings' => [...]]. */
    public function run(): array
    {
        $manifest = json_decode((string)@file_get_contents(self::DIR . '/manifest.json'), true);
        if (!is_array($manifest) || empty($manifest['pages'])) {
            throw new RuntimeException('app/seed/fscd/manifest.json is missing or invalid.');
        }
        $this->settings();
        $this->media();
        $this->shell();
        $this->pages($manifest['pages']);
        $this->redirects();
        Settings::set('fscd_import_warnings', json_encode($this->warnings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return ['log' => $this->log, 'warnings' => $this->warnings];
    }

    private function settings(): void
    {
        $values = [
            'site_name' => 'Foundation for Sustainable Community Development',
            'site_short_name' => 'FSCD',
            'tagline' => 'Empowering Communities. Building Futures.',
            'email' => 'info@thefscd.org',
            'notify_email' => 'info@thefscd.org',
            'seo_title_suffix' => ' – Foundation for Sustainable Community Development',
            'color_primary' => '#062b4b',
            'color_accent' => '#4c8f2f',
        ];
        $n = 0;
        foreach ($values as $k => $v) {
            if ($this->force || (string)DB::val('SELECT v FROM settings WHERE k = ?', [$k]) === '') {
                Settings::set($k, $v);
                $n++;
            }
        }
        $this->log[] = "settings: $n set";
    }

    /** Every file in media/ (and media/stock/) into the Media Library, once (matched by original file name). */
    private function media(): void
    {
        $files = array_merge(glob(self::DIR . '/media/*.*') ?: [], glob(self::DIR . '/media/stock/*.*') ?: []);
        sort($files);
        $added = 0;
        foreach ($files as $file) {
            $name = basename($file);
            $this->mediaFiles[strtolower($name)] = $name;
            if (DB::val('SELECT id FROM media WHERE original_name = ?', [$name])) {
                continue;
            }
            Media::importLocal($file, 'fscd', $this->userId);
            $added++;
        }
        HtmlPage::flushMedia();
        $this->log[] = "media: $added added, " . (count($files) - $added) . ' already in the library';
    }

    /** The canonical header (Home's) and footer (the majority footer, which Home shares) as settings. */
    private function shell(): void
    {
        $home = $this->read('home');
        [, , $blocks] = HtmlPage::replaceShells($home);
        if (empty($blocks['header']) || empty($blocks['footer'])) {
            throw new RuntimeException('Could not find the master header/footer in home.html.');
        }
        $header = HtmlPage::clearActive($this->rewriteUrls($blocks['header']));
        $footer = (string)preg_replace("/\n{2,}/", "\n", $this->rewriteUrls($blocks['footer']));
        foreach (['site_header_html' => $header, 'site_footer_html' => $footer] as $k => $v) {
            if ($this->force || trim((string)DB::val('SELECT v FROM settings WHERE k = ?', [$k])) === '') {
                Settings::set($k, $v);
                $this->log[] = "$k: set";
            }
        }
    }

    private function pages(array $manifestPages): void
    {
        $bySlug = [];
        foreach ($manifestPages as $p) {
            $bySlug[$p['slug']] = $p;
        }
        $order = array_keys(self::PAGES);
        foreach ($bySlug as $slug => $p) {
            if (!in_array($slug, $order, true)) {
                $order[] = $slug;
            }
        }
        $now = now();
        foreach ($order as $i => $slug) {
            if (!isset($bySlug[$slug])) {
                continue;
            }
            $m = $bySlug[$slug];
            $html = $this->read($slug);
            $metaTitle = $m['title'];
            $metaDesc = self::PAGES[$slug]['meta_description'] ?? '';
            if ($slug === 'home') {
                [$html, $metaTitle, $metaDesc] = $this->homeFragment($html);
            }
            $content = $this->transform($slug, $html);
            $row = [
                'title' => $m['title'],
                'content' => $content,
                'template' => 'html',
                'show_cta' => 0,
                'is_system' => 1,
                'needs_review' => $slug === 'home' ? 0 : 1,
                'sort_order' => ($i + 1) * 10,
                'status' => 'published',
                'meta_title' => $metaTitle,
                'meta_description' => $metaDesc,
                'updated_at' => $now,
            ];
            $existing = DB::one('SELECT * FROM pages WHERE slug = ?', [$slug]);
            if ($existing && !$this->force) {
                $this->log[] = "page $slug: kept (exists; use --force to overwrite)";
                continue;
            }
            if ($existing) {
                if ((string)$existing['content'] !== $content) {
                    HtmlPage::saveRevision((int)$existing['id'], (string)$existing['content'], $this->userId);
                }
                DB::update('pages', $row, 'id = ?', [$existing['id']]);
                $this->log[] = "page $slug: updated";
            } else {
                DB::insert('pages', $row + ['slug' => $slug, 'eyebrow' => '', 'intro' => '', 'image' => '', 'created_at' => $now]);
                $this->log[] = "page $slug: created";
            }
            foreach (HtmlPage::unknownTokens($content) as $t) {
                $this->warnings[] = "$slug: unresolved token $t";
            }
        }
    }

    private function redirects(): void
    {
        foreach (self::REDIRECTS as $from => $to) {
            if (!DB::val('SELECT id FROM redirects WHERE source = ?', [$from])) {
                DB::insert('redirects', ['source' => $from, 'target' => $to, 'code' => 301, 'hits' => 0, 'note' => 'FSCD: old WordPress URL', 'created_at' => now(), 'updated_at' => now()]);
                $this->log[] = "redirect $from → $to";
            }
        }
    }

    private function read(string $slug): string
    {
        $f = self::DIR . '/pages/' . $slug . '.html';
        if (!is_file($f)) {
            throw new RuntimeException("Missing app/seed/fscd/pages/$slug.html");
        }
        return (string)file_get_contents($f);
    }

    /**
     * home.html is a full document: keep the <head> contents (minus charset, viewport, title and
     * description, which the layout outputs) followed by the <body> contents, in order.
     * Returns [fragment, title, description].
     */
    private function homeFragment(string $doc): array
    {
        if (!preg_match('~<head\b[^>]*>(.*?)</head>~is', $doc, $hm) || !preg_match('~<body\b[^>]*>(.*)</body>~is', $doc, $bm)) {
            throw new RuntimeException('home.html: could not find <head> and <body>.');
        }
        $head = $hm[1];
        $title = preg_match('~<title>(.*?)</title>~is', $head, $t) ? html_entity_decode(trim($t[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'Home';
        $desc = '';
        if (preg_match('~<meta\b(?=[^>]*\bname=["\']description["\'])[^>]*>~i', $head, $d) && preg_match('~\bcontent="([^"]*)"~i', $d[0], $c)) {
            $desc = html_entity_decode($c[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $head = (string)preg_replace([
            '~[ \t]*<meta\b[^>]*\bcharset=[^>]*>\s*~i',
            '~[ \t]*<meta\b(?=[^>]*\bname=["\'](?:viewport|description)["\'])[^>]*>\s*~i',
            '~[ \t]*<title>.*?</title>\s*~is',
        ], '', $head);
        return [trim($head) . "\n" . ltrim($bm[1]), $title, $desc];
    }

    /** Page HTML → stored fragment. */
    public function transform(string $slug, string $html): string
    {
        [$html] = HtmlPage::replaceShells($html);
        if ($slug === 'eye-care') {
            $html = $this->inlineEyeCare($html);
        }
        $html = $this->rewriteUrls($html);
        if ($slug === 'home') {
            $html = $this->homeForm($html);
        } elseif ($slug === 'contact') {
            $html = $this->contactForm($html);
        }
        return $html;
    }

    /** Replace the Eye Care stylesheet/script tags with the files' contents, at the same positions. */
    private function inlineEyeCare(string $html): string
    {
        $dir = self::DIR . '/eye-care/';
        $css = is_file($dir . 'fscd-eye.css') ? (string)file_get_contents($dir . 'fscd-eye.css') : null;
        $js = is_file($dir . 'fscd-eye.js') ? (string)file_get_contents($dir . 'fscd-eye.js') : null;
        if ($css === null || $js === null) {
            $this->warnings[] = 'eye-care: fscd-eye.css / fscd-eye.js missing from app/seed/fscd/eye-care/ (page imported without them)';
        }
        if ($css !== null) {
            $html = (string)preg_replace_callback('~<link\b[^>]*\bhref="[^"]*/fscd-eye\.css[^"]*"[^>]*>~i', fn() => "<style>\n" . rtrim($css) . "\n</style>", $html, 1);
        }
        if ($js !== null) {
            $html = (string)preg_replace_callback('~<script\b[^>]*\bsrc="[^"]*/fscd-eye\.js[^"]*"[^>]*>\s*</script>~i', fn() => "<script defer>\n" . rtrim(str_ireplace('</script', '<\/script', $js)) . "\n</script>", $html, 1);
        }
        return $html;
    }

    /**
     * WordPress upload URLs → {{media:file}} when the file is in the Media Library import; other
     * links to the old staging host become root-relative (they then point at the new site).
     */
    public function rewriteUrls(string $html): string
    {
        $html = (string)preg_replace_callback(
            '~(?:https?://(?:ahrons18\.sg-host\.com|(?:www\.)?foundationforsustainablecommunitydevelopment\.org))?/wp-content/uploads/(?:[^"\'()\s<>?#]*/)?([^/"\'()\s<>?#]+)(?:\?[^"\'()\s<>#]*)?~i',
            function ($m) {
                $file = $this->mediaFiles[strtolower(rawurldecode($m[1]))] ?? null;
                if ($file !== null) {
                    return '{{media:' . $file . '}}';
                }
                return (string)preg_replace('~^https?://ahrons18\.sg-host\.com~i', '', $m[0]);
            },
            $html
        );
        return (string)preg_replace('~https?://ahrons18\.sg-host\.com(?=/|["\'\s])~i', '', $html);
    }

    /** Replace exact snippets; warn (and skip) if a snippet is not found. */
    private function swap(string $slug, string $html, array $pairs): string
    {
        foreach ($pairs as $from => $to) {
            $n = substr_count($html, $from);
            if ($n !== 1) {
                $this->warnings[] = "$slug: form rewrite expected 1 match, found $n: " . mb_substr($from, 0, 80);
                continue;
            }
            $html = str_replace($from, $to, $html);
        }
        return $html;
    }

    /** Home page form: post to the built-in form handler instead of formsubmit.co. */
    private function homeForm(string $html): string
    {
        if (!preg_match('~<form\b[^>]*class="fscd-form-grid"[^>]*>.*?</form>~is', $html, $fm)) {
            $this->warnings[] = 'home: contact form not found';
            return $html;
        }
        $form = $this->swap('home', $fm[0], [
            '<form action="https://formsubmit.co/info@thefscd.org" class="fscd-form-grid" method="POST">' => '<form action="/api/form" class="fscd-form-grid" method="POST">',
            '<input name="_subject" type="hidden" value="New FSCD Website Inquiry"/>' . "\n" => '<input name="_subject" type="hidden" value="New FSCD Website Inquiry"/>' . "\n"
                . '<input name="_form" type="hidden" value="home_inquiry"/>' . "\n"
                . '<input name="_token" type="hidden" value="{{form_token}}"/>' . "\n"
                . '<input name="_page" type="hidden" value="/"/>' . "\n",
            '<input name="_template" type="hidden" value="table"/>' . "\n" => '',
            '<input name="_captcha" type="hidden" value="false"/>' . "\n" => '',
            'name="First Name"' => 'name="first_name"',
            'name="Last Name"' => 'name="last_name"',
            'name="Email"' => 'name="email"',
            'name="Organization"' => 'name="organization"',
            'name="Interest"' => 'name="interest"',
            'name="Message"' => 'name="message"',
            // formsubmit showed its own thank-you page; show the site's success message after the redirect back
            "</div>\n</form>" => "</div>\n"
                . '<div class="fscd-form-status" id="fscd-form-status" role="status" aria-live="polite" style="display:none;grid-column:1/-1;padding:13px 15px;border-radius:10px;border:1px solid rgba(76,143,47,.30);background:#f2f8ee;color:#2f641d;font-size:14px;font-weight:700;line-height:1.55"><i class="fa-solid fa-circle-check"></i> Thank you. Your message has been sent to FSCD.</div>' . "\n"
                . '<script>if(/[?&]submitted=1(&|$)/.test(location.search)){document.getElementById("fscd-form-status").style.display="block";}</script>' . "\n"
                . '</form>',
        ]);
        return str_replace($fm[0], $form, $html);
    }

    /** Contact page form (AJAX): same fields, posted to the built-in form handler. */
    private function contactForm(string $html): string
    {
        if (!preg_match('~<form\b[^>]*id="fscd-c-form"[^>]*>.*?</form>~is', $html, $fm)) {
            $this->warnings[] = 'contact: contact form not found';
            return $html;
        }
        $form = $this->swap('contact', $fm[0], [
            '<form action="https://formsubmit.co/info@thefscd.org"' => '<form action="/api/form"',
            '<input id="fscd-c-topic" name="Topic" type="hidden" value="General Inquiry"/>' => '<input id="fscd-c-topic" name="topic" type="hidden" value="General Inquiry"/>',
            '<input name="_template" type="hidden" value="table"/>' => '<input name="_form" type="hidden" value="fscd_contact"/>',
            '<input name="_captcha" type="hidden" value="false"/>' => '<input name="_token" type="hidden" value="{{form_token}}"/>',
            '<input name="_next" type="hidden" value="/contact/?submitted=1#conversation"/>' => '<input name="_page" type="hidden" value="/contact/"/>',
            '<input name="_url" type="hidden" value="/contact/"/>' => '',
            'name="_honey"' => 'name="website"',
            'name="Organization"' => 'name="organization"',
            'name="Role / Title"' => 'name="role_title"',
            'name="Location"' => 'name="location"',
            'name="Country / Location"' => 'name="country_location"',
            'name="Role / Community Profile"' => 'name="role_community_profile"',
            'name="Community Need"' => 'name="community_need"',
            'name="Subject"' => 'name="subject"',
            'name="Message"' => 'name="message"',
        ]);
        $html = str_replace($fm[0], $form, $html);
        return $this->swap('contact', $html, [
            "fetch('https://formsubmit.co/ajax/info@thefscd.org'," => "fetch('/api/form',",
            "const ok=response.ok&&(result.success===true||result.success==='true'||result.success===undefined);\n      if(!ok)throw new Error(result.message||'Submission failed');"
                => "const ok=response.ok&&result.ok===true;\n      if(!ok)throw new Error(result.error||'Submission failed');",
        ]);
    }
}
