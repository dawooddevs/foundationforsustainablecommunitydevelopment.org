<?php
/**
 * Media library: uploads, image optimisation (resize + WebP + thumbnail) and lookups.
 */
final class Media
{
    public const MAX_BYTES = 100 * 1024 * 1024;
    public const MAX_DIMENSION = 2400;

    private const TYPES = [
        'jpg' => ['image/jpeg', 'image'], 'jpeg' => ['image/jpeg', 'image'], 'png' => ['image/png', 'image'],
        'gif' => ['image/gif', 'image'], 'webp' => ['image/webp', 'image'], 'avif' => ['image/avif', 'image'],
        'pdf' => ['application/pdf', 'document'], 'doc' => ['application/msword', 'document'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'document'],
        'xls' => ['application/vnd.ms-excel', 'document'], 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'document'],
        'mp4' => ['video/mp4', 'video'], 'webm' => ['video/webm', 'video'], 'mov' => ['video/quicktime', 'video'],
    ];

    private static ?array $map = null;

    public static function allowedExtensions(): array
    {
        return array_keys(self::TYPES);
    }

    /** Find a media row by its stored path (cached for the request). */
    public static function lookup(string $path): ?array
    {
        if (!DB::connected() || preg_match('~^(https?:)?//~', $path)) {
            return null;
        }
        if (self::$map === null) {
            self::$map = [];
            try {
                foreach (DB::all('SELECT path, webp, width, height, alt FROM media') as $r) {
                    self::$map[$r['path']] = $r;
                }
            } catch (Throwable $e) {
            }
        }
        return self::$map[ltrim($path, '/')] ?? null;
    }

    /** File-name key that ignores case, spaces and punctuation ("WOMAC.pdf" = "womac.pdf"). */
    public static function exactKey(string $name): string
    {
        return substr(slugify(pathinfo($name, PATHINFO_FILENAME)), 0, 60);
    }

    /** Looser key that also ignores copy suffixes like "-1" or " (2)" ("WOMAC (1).pdf" = "WOMAC.pdf"). */
    public static function matchKey(string $name): string
    {
        return (string)preg_replace('/-\d{1,2}$/', '', self::exactKey($name));
    }

    /** Media rows of one kind indexed by exactKey() and matchKey() of the original and stored names; newest upload wins. */
    public static function byMatchKey(string $kind): array
    {
        $out = ['exact' => [], 'loose' => []];
        foreach (DB::all('SELECT * FROM media WHERE kind = ? ORDER BY id', [$kind]) as $m) {
            foreach ([(string)$m['original_name'], basename((string)$m['path'])] as $n) {
                $out['exact'][self::exactKey($n)] = $m;
                $out['loose'][self::matchKey($n)] = $m;
            }
        }
        return $out;
    }

    /**
     * Find a file in a byMatchKey() index: the exact name first, then an upload whose name only adds a
     * copy suffix ("WOMAC (1).pdf" for "WOMAC.pdf"). The wanted name itself is never shortened, so a
     * missing "Video 2" can't fall back to "Video 1".
     */
    public static function findByName(array $index, string $name): ?array
    {
        $key = self::exactKey($name);
        return $index['exact'][$key] ?? $index['loose'][$key] ?? null;
    }

    public static function upload(array $file, int $userId, string $folder = ''): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $codes = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds the server upload limit (' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_FORM_SIZE => 'File is too large.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            ];
            throw new RuntimeException($codes[$file['error'] ?? 4] ?? 'Upload failed.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('File is larger than 100 MB.');
        }
        $original = basename((string)$file['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$ext])) {
            throw new RuntimeException('File type .' . $ext . ' is not allowed.');
        }
        [$mime, $kind] = self::TYPES[$ext];
        $detected = function_exists('mime_content_type') ? (string)@mime_content_type($file['tmp_name']) : '';
        if ($kind === 'image' && $detected !== '' && !str_starts_with($detected, 'image/')) {
            throw new RuntimeException('That file does not look like a valid image.');
        }
        if ($ext === 'pdf' && $detected !== '' && !in_array($detected, ['application/pdf', 'application/x-pdf', 'application/octet-stream'], true)) {
            throw new RuntimeException('That file does not look like a valid PDF.');
        }

        return self::store($file['tmp_name'], $original, $userId, $folder, true);
    }

    /**
     * Import a file that is already on the server (seed content, CLI imports) through the same
     * pipeline as an upload: copied into uploads/YYYY/MM/, WebP version, thumbnail, dimensions
     * and a media row. The source file is left in place.
     */
    public static function importLocal(string $path, string $folder = 'fscd', int $userId = 0, string $alt = ''): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('File not found: ' . basename($path));
        }
        if (filesize($path) > self::MAX_BYTES) {
            throw new RuntimeException('File is larger than 100 MB.');
        }
        $original = basename($path);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$ext])) {
            throw new RuntimeException('File type .' . $ext . ' is not allowed.');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'imp');
        if ($tmp === false || !copy($path, $tmp)) {
            throw new RuntimeException('Could not read ' . $original . '.');
        }
        $row = self::store($tmp, $original, $userId, $folder, false);
        if ($alt === '') {
            $alt = ucfirst(trim((string)preg_replace('/[\s_\-]+/', ' ', pathinfo($original, PATHINFO_FILENAME))));
        }
        if ($row['kind'] === 'image') {
            DB::update('media', ['alt' => mb_substr($alt, 0, 250)], 'id = ?', [$row['id']]);
            $row['alt'] = $alt;
        }
        return $row;
    }

    /** Move a validated file into uploads/ and create its media row. */
    private static function store(string $tmp, string $original, int $userId, string $folder, bool $isUpload): array
    {
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        [$mime, $kind] = self::TYPES[$ext];
        $sub = 'uploads/' . date('Y/m');
        $dir = ROOT . '/' . $sub;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Could not create the uploads folder. Check permissions on /uploads.');
        }
        $base = slugify(pathinfo($original, PATHINFO_FILENAME)) ?: 'file';
        $base = substr($base, 0, 60);
        $name = $base . '.' . $ext;
        $i = 1;
        while (file_exists($dir . '/' . $name)) {
            $name = $base . '-' . (++$i) . '.' . $ext;
        }
        $dest = $dir . '/' . $name;
        $moved = ($isUpload && move_uploaded_file($tmp, $dest)) || @rename($tmp, $dest);
        if (!$moved && !$isUpload && @copy($tmp, $dest)) {
            @unlink($tmp);
            $moved = true;
        }
        if (!$moved) {
            throw new RuntimeException('Could not save the uploaded file.');
        }
        @chmod($dest, 0644);

        $row = [
            'path' => $sub . '/' . $name,
            'webp' => '',
            'thumb' => '',
            'original_name' => mb_substr($original, 0, 250),
            'mime' => $mime,
            'kind' => $kind,
            'size' => (int)filesize($dest),
            'width' => 0,
            'height' => 0,
            'alt' => '',
            'title' => ucwords(str_replace('-', ' ', $base)),
            'folder' => substr(slugify($folder), 0, 80),
            'uploaded_by' => $userId ?: null,
            'created_at' => now(),
        ];
        if ($kind === 'image') {
            $row = array_merge($row, self::processImage($dest, $sub, pathinfo($name, PATHINFO_FILENAME), $ext));
            $row['size'] = (int)filesize($dest);
        }
        $row['id'] = DB::insert('media', $row);
        self::$map = null;
        return $row;
    }

    private static function processImage(string $file, string $sub, string $base, string $ext): array
    {
        $out = ['width' => 0, 'height' => 0, 'webp' => '', 'thumb' => ''];
        $info = @getimagesize($file);
        if (!$info) {
            return $out;
        }
        [$w, $h] = $info;
        $out['width'] = $w;
        $out['height'] = $h;
        if (!extension_loaded('gd') || in_array($ext, ['gif', 'avif'], true)) {
            return $out;
        }
        $src = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($file),
            'png' => @imagecreatefrompng($file),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file) : false,
            default => false,
        };
        if (!$src) {
            return $out;
        }
        if (in_array($ext, ['jpg', 'jpeg'], true) && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file);
            $o = (int)($exif['Orientation'] ?? 1);
            $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($rot) {
                $r = imagerotate($src, $rot, 0);
                if ($r) {
                    imagedestroy($src);
                    $src = $r;
                    $w = imagesx($src);
                    $h = imagesy($src);
                    $out['width'] = $w;
                    $out['height'] = $h;
                }
            }
        }
        // Downscale very large originals in place
        if ($w > self::MAX_DIMENSION || $h > self::MAX_DIMENSION) {
            $scale = self::MAX_DIMENSION / max($w, $h);
            $resized = self::resample($src, (int)round($w * $scale), (int)round($h * $scale));
            imagedestroy($src);
            $src = $resized;
            $w = imagesx($src);
            $h = imagesy($src);
            $out['width'] = $w;
            $out['height'] = $h;
            if (in_array($ext, ['jpg', 'jpeg'], true)) {
                imagejpeg($src, $file, 84);
            } elseif ($ext === 'png') {
                imagepng($src, $file, 7);
            } elseif ($ext === 'webp') {
                imagewebp($src, $file, 82);
            }
        }
        if (function_exists('imagewebp')) {
            if ($ext !== 'webp') {
                $webp = $sub . '/' . $base . '.webp';
                if (!file_exists(ROOT . '/' . $webp) && @imagewebp($src, ROOT . '/' . $webp, 80)) {
                    $out['webp'] = $webp;
                }
            }
            $tw = 480;
            if ($w > $tw) {
                $thumb = self::resample($src, $tw, (int)round($h * $tw / $w));
                $tp = $sub . '/' . $base . '-thumb.webp';
                if (@imagewebp($thumb, ROOT . '/' . $tp, 78)) {
                    $out['thumb'] = $tp;
                }
                imagedestroy($thumb);
            }
        }
        imagedestroy($src);
        return $out;
    }

    private static function resample($src, int $nw, int $nh)
    {
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, imagesx($src), imagesy($src));
        return $dst;
    }

    public static function deleteFiles(array $row): void
    {
        foreach (['path', 'webp', 'thumb'] as $k) {
            $p = (string)($row[$k] ?? '');
            if ($p !== '' && str_starts_with($p, 'uploads/') && !str_contains($p, '..')) {
                @unlink(ROOT . '/' . $p);
            }
        }
        self::$map = null;
    }

    /** Where content stores media paths: [table, column, label column]. Settings are handled separately. */
    private const REF_COLUMNS = [
        ['services', 'image', 'title'],
        ['pages', 'image', 'title'],
        ['providers', 'photo', 'name'],
        ['locations', 'image', 'name'],
    ];

    /** Point every content field and setting that uses $old at $new ('' clears it). Returns how many were changed. */
    public static function repoint(string $old, string $new): int
    {
        if ($old === '' || $old === $new) {
            return 0;
        }
        $n = 0;
        foreach (self::REF_COLUMNS as [$table, $col]) {
            $n += DB::update($table, [$col => $new, 'updated_at' => now()], DB::ident($col) . ' = ?', [$old]);
        }
        foreach (DB::all('SELECT k FROM settings WHERE v = ?', [$old]) as $r) {
            Settings::set($r['k'], $new);
            $n++;
        }
        return $n;
    }

    /**
     * Replace the file behind a media item: the new upload takes over the old item's alt text, title
     * and folder, every reference is re-pointed, and the old files are removed. Returns [row, refs].
     */
    public static function replace(array $old, array $file, int $userId): array
    {
        $new = self::upload($file, $userId, (string)$old['folder']);
        // Keep the item's name too: pages refer to library files as {{media:<original name>}}
        $keep = array_filter(['alt' => (string)$old['alt'], 'title' => (string)$old['title'], 'original_name' => (string)$old['original_name']], fn($v) => trim($v) !== '');
        if ($keep) {
            DB::update('media', $keep, 'id = ?', [$new['id']]);
            $new = array_merge($new, $keep);
        }
        $refs = self::repoint((string)$old['path'], (string)$new['path']);
        self::deleteFiles($old);
        DB::delete('media', 'id = ?', [(int)$old['id']]);
        return [$new, $refs];
    }

    /** True when $path is an uploaded file that no longer exists on disk. */
    public static function isMissing(string $path): bool
    {
        return str_starts_with($path, 'uploads/') && !is_file(ROOT . '/' . $path);
    }

    /**
     * Match images to content by file name ("chiropractic-care.jpg" → the chiropractic-care service,
     * "page-about-us", "location-main", provider names, "home-hero", "logo", …). A field that
     * points at a deleted file counts as empty, so a fresh upload fills it without "overwrite".
     */
    public static function autoAssign(bool $apply, bool $overwrite, bool $includeSettings): array
    {
        $images = DB::all("SELECT * FROM media WHERE kind = 'image' ORDER BY id ASC");
        $keyOf = fn(string $path): string => (string)preg_replace('/-(\d+|thumb)$/', '', slugify(pathinfo($path, PATHINFO_FILENAME)));
        $files = [];
        foreach ($images as $m) {
            $files[$keyOf($m['path'])] = $m; // newest upload wins
        }
        $find = function (array $keys) use ($files) {
            foreach ($keys as $k) {
                $k = slugify((string)$k);
                if ($k !== '' && isset($files[$k])) return $files[$k];
            }
            return null;
        };
        $matches = [];
        $add = function (string $kind, string $label, string $current, ?array $m, callable $save) use (&$matches, $apply, $overwrite, $keyOf) {
            if (!$m) return;
            $broken = $current !== '' && self::isMissing($current);
            $same = $current === $m['path'];
            // A newer upload with the same file name (e.g. "chiropractic-care-2.jpg") replaces the older one
            $newer = !$same && $current !== '' && $keyOf($current) === $keyOf($m['path']);
            $skip = $same || ($current !== '' && !$broken && !$newer && !$overwrite);
            $matches[] = ['kind' => $kind, 'label' => $label, 'file' => basename($m['path']), 'thumb' => media_url($m['thumb'] ?: ($m['webp'] ?: $m['path'])), 'current' => $current !== '' && !$broken, 'status' => $same ? 'already set' : ($skip ? 'kept existing' : ($apply ? 'assigned' : 'will assign'))];
            if ($apply && !$skip) $save($m);
        };
        $alt = function (array $m, string $text) {
            if (trim((string)$m['alt']) === '') DB::update('media', ['alt' => mb_substr($text, 0, 250)], 'id = ?', [$m['id']]);
        };
        $site = setting('site_short_name');
        foreach (DB::all('SELECT id, slug, title, menu_label, image FROM services ORDER BY sort_order') as $r) {
            $add('Treatment', $r['title'], (string)$r['image'], $find([$r['slug'], $r['title'], $r['menu_label']]), function ($m) use ($r, $alt, $site) {
                DB::update('services', ['image' => $m['path'], 'updated_at' => now()], 'id = ?', [$r['id']]);
                $alt($m, $r['title'] . ' at ' . $site);
            });
        }
        foreach (DB::all('SELECT id, slug, title, image FROM pages') as $r) {
            $add('Page', $r['title'], (string)$r['image'], $find(['page-' . $r['slug']]), function ($m) use ($r, $alt, $site) {
                DB::update('pages', ['image' => $m['path'], 'updated_at' => now()], 'id = ?', [$r['id']]);
                $alt($m, $r['title'] . ' — ' . $site);
            });
        }
        foreach (DB::all('SELECT id, slug, name, image FROM locations') as $r) {
            $add('Location', $r['name'] . ' office', (string)$r['image'], $find(['location-' . $r['slug']]), function ($m) use ($r, $alt, $site) {
                DB::update('locations', ['image' => $m['path'], 'updated_at' => now()], 'id = ?', [$r['id']]);
                $alt($m, $site . ' ' . $r['name'] . ' office');
            });
        }
        foreach (DB::all('SELECT id, slug, name, photo FROM providers') as $r) {
            $add('Provider', $r['name'], (string)$r['photo'], $find(['provider-' . $r['slug'], $r['slug']]) ?? Content::providerPhotoMatch($r, $images), function ($m) use ($r, $alt) {
                DB::update('providers', ['photo' => $m['path'], 'updated_at' => now()], 'id = ?', [$r['id']]);
                $alt($m, $r['name']);
            });
        }
        if ($includeSettings) {
            foreach ([['hero_image', 'Homepage hero', 'home-hero'], ['about_image', 'Homepage about section', ['home-about', 'home-integrated-care']], ['testimonials_image', 'Homepage testimonials', 'home-testimonials'], ['seo_og_image', 'Social share image', 'social-share'], ['logo', 'Logo', 'logo'], ['logo_light', 'Logo (dark footer)', 'logo-light'], ['favicon', 'Favicon', 'favicon']] as [$key, $label, $file]) {
                $add('Site', $label, (string)setting($key, ''), $find((array)$file), function ($m) use ($key) { Settings::set($key, $m['path']); });
            }
        }
        return ['matches' => $matches, 'files' => count($files)];
    }

    /** Clear content fields and image settings that still point at deleted files. Returns the cleared labels. */
    public static function clearBroken(): array
    {
        $cleared = [];
        foreach (self::REF_COLUMNS as [$table, $col, $label]) {
            foreach (DB::all('SELECT id, ' . DB::ident($label) . ' AS label, ' . DB::ident($col) . ' AS path FROM ' . DB::ident($table) . ' WHERE ' . DB::ident($col) . " <> ''") as $r) {
                if (self::isMissing((string)$r['path'])) {
                    DB::update($table, [$col => '', 'updated_at' => now()], 'id = ?', [$r['id']]);
                    $cleared[] = $r['label'] . ' (' . basename((string)$r['path']) . ')';
                }
            }
        }
        foreach (DB::all("SELECT k, v FROM settings WHERE v LIKE 'uploads/%'") as $r) {
            if (self::isMissing((string)$r['v'])) {
                Settings::set($r['k'], '');
                $cleared[] = 'setting ' . $r['k'] . ' (' . basename((string)$r['v']) . ')';
            }
        }
        return $cleared;
    }

    public static function present(array $r): array
    {
        $r['id'] = (int)$r['id'];
        $r['size'] = (int)$r['size'];
        $r['width'] = (int)$r['width'];
        $r['height'] = (int)$r['height'];
        $r['url'] = media_url($r['path']);
        $r['thumb_url'] = media_url($r['thumb'] ?: ($r['webp'] ?: $r['path']));
        $r['abs_url'] = abs_url($r['path']);
        return $r;
    }
}
