<?php
/**
 * Database schema. Placeholders are replaced per driver so the same definition
 * runs on MySQL (SiteGround) and SQLite (local development).
 */
function schema_statements(string $driver): array
{
    $tables = [
        'users' => "
            id {ID},
            username VARCHAR(60) NOT NULL UNIQUE,
            email VARCHAR(190) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL DEFAULT '',
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'editor',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            avatar VARCHAR(255) NOT NULL DEFAULT '',
            last_login {DT} NULL,
            created_at {DT} NULL,
            updated_at {DT} NULL",
        'settings' => "
            k VARCHAR(100) NOT NULL PRIMARY KEY,
            v {LONGTEXT} NULL",
        'services' => "
            id {ID},
            slug VARCHAR(190) NOT NULL UNIQUE,
            title VARCHAR(190) NOT NULL,
            menu_label VARCHAR(190) NOT NULL DEFAULT '',
            category VARCHAR(40) NOT NULL DEFAULT 'chiropractic',
            excerpt {TEXT} NULL,
            hero_text {TEXT} NULL,
            image VARCHAR(255) NOT NULL DEFAULT '',
            icon VARCHAR(40) NOT NULL DEFAULT '',
            what_is {LONGTEXT} NULL,
            conditions {TEXT} NULL,
            how_it_works {LONGTEXT} NULL,
            benefits {TEXT} NULL,
            what_to_expect {LONGTEXT} NULL,
            faqs {LONGTEXT} NULL,
            related {TEXT} NULL,
            body_areas VARCHAR(255) NOT NULL DEFAULT '',
            featured INT NOT NULL DEFAULT 0,
            coming_soon INT NOT NULL DEFAULT 0,
            show_in_menu INT NOT NULL DEFAULT 1,
            needs_review INT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            meta_title VARCHAR(255) NOT NULL DEFAULT '',
            meta_description {TEXT} NULL,
            updated_by INT NULL,
            created_at {DT} NULL,
            updated_at {DT} NULL",
        'providers' => "
            id {ID},
            slug VARCHAR(190) NOT NULL UNIQUE,
            name VARCHAR(190) NOT NULL,
            credentials VARCHAR(120) NOT NULL DEFAULT '',
            title VARCHAR(190) NOT NULL DEFAULT '',
            type VARCHAR(40) NOT NULL DEFAULT 'chiropractor',
            photo VARCHAR(255) NOT NULL DEFAULT '',
            photo_position VARCHAR(40) NOT NULL DEFAULT '50% 20%',
            locations VARCHAR(255) NOT NULL DEFAULT '',
            short_bio {TEXT} NULL,
            bio {LONGTEXT} NULL,
            education {LONGTEXT} NULL,
            experience {LONGTEXT} NULL,
            philosophy {LONGTEXT} NULL,
            focus {TEXT} NULL,
            services {TEXT} NULL,
            needs_review INT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            meta_title VARCHAR(255) NOT NULL DEFAULT '',
            meta_description {TEXT} NULL,
            updated_by INT NULL,
            created_at {DT} NULL,
            updated_at {DT} NULL",
        'pages' => "
            id {ID},
            slug VARCHAR(190) NOT NULL UNIQUE,
            title VARCHAR(190) NOT NULL,
            eyebrow VARCHAR(120) NOT NULL DEFAULT '',
            intro {TEXT} NULL,
            image VARCHAR(255) NOT NULL DEFAULT '',
            content {LONGTEXT} NULL,
            template VARCHAR(40) NOT NULL DEFAULT 'default',
            show_cta INT NOT NULL DEFAULT 1,
            is_system INT NOT NULL DEFAULT 0,
            needs_review INT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            meta_title VARCHAR(255) NOT NULL DEFAULT '',
            meta_description {TEXT} NULL,
            updated_by INT NULL,
            created_at {DT} NULL,
            updated_at {DT} NULL",
        'locations' => "
            id {ID},
            slug VARCHAR(120) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL,
            address VARCHAR(255) NOT NULL DEFAULT '',
            city VARCHAR(120) NOT NULL DEFAULT '',
            state VARCHAR(20) NOT NULL DEFAULT '',
            zip VARCHAR(20) NOT NULL DEFAULT '',
            phone VARCHAR(40) NOT NULL DEFAULT '',
            fax VARCHAR(40) NOT NULL DEFAULT '',
            hours {TEXT} NULL,
            map_embed {TEXT} NULL,
            directions_url {TEXT} NULL,
            image VARCHAR(255) NOT NULL DEFAULT '',
            description {TEXT} NULL,
            latitude VARCHAR(30) NOT NULL DEFAULT '',
            longitude VARCHAR(30) NOT NULL DEFAULT '',
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            created_at {DT} NULL,
            updated_at {DT} NULL",
        'testimonials' => "
            id {ID},
            name VARCHAR(120) NOT NULL,
            label VARCHAR(120) NOT NULL DEFAULT '',
            content {TEXT} NULL,
            rating INT NOT NULL DEFAULT 5,
            featured INT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            created_at {DT} NULL,
            updated_at {DT} NULL",
        'faqs' => "
            id {ID},
            question VARCHAR(255) NOT NULL,
            answer {TEXT} NULL,
            grp VARCHAR(40) NOT NULL DEFAULT 'home',
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            created_at {DT} NULL,
            updated_at {DT} NULL",
        'media' => "
            id {ID},
            path VARCHAR(255) NOT NULL,
            webp VARCHAR(255) NOT NULL DEFAULT '',
            thumb VARCHAR(255) NOT NULL DEFAULT '',
            original_name VARCHAR(255) NOT NULL DEFAULT '',
            mime VARCHAR(100) NOT NULL DEFAULT '',
            kind VARCHAR(20) NOT NULL DEFAULT 'image',
            size INT NOT NULL DEFAULT 0,
            width INT NOT NULL DEFAULT 0,
            height INT NOT NULL DEFAULT 0,
            alt VARCHAR(255) NOT NULL DEFAULT '',
            title VARCHAR(255) NOT NULL DEFAULT '',
            folder VARCHAR(80) NOT NULL DEFAULT '',
            uploaded_by INT NULL,
            created_at {DT} NULL",
        'redirects' => "
            id {ID},
            source VARCHAR(255) NOT NULL UNIQUE,
            target VARCHAR(255) NOT NULL,
            code INT NOT NULL DEFAULT 301,
            hits INT NOT NULL DEFAULT 0,
            note VARCHAR(255) NOT NULL DEFAULT '',
            created_at {DT} NULL,
            updated_at {DT} NULL",
        'submissions' => "
            id {ID},
            form VARCHAR(40) NOT NULL DEFAULT 'contact',
            name VARCHAR(190) NOT NULL DEFAULT '',
            email VARCHAR(190) NOT NULL DEFAULT '',
            phone VARCHAR(60) NOT NULL DEFAULT '',
            data {LONGTEXT} NULL,
            page VARCHAR(255) NOT NULL DEFAULT '',
            ip VARCHAR(64) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'new',
            forwarded INT NOT NULL DEFAULT 0,
            created_at {DT} NULL",
        'activity' => "
            id {ID},
            user_id INT NULL,
            user_name VARCHAR(120) NOT NULL DEFAULT '',
            action VARCHAR(60) NOT NULL,
            object_type VARCHAR(40) NOT NULL DEFAULT '',
            object_id INT NULL,
            label VARCHAR(255) NOT NULL DEFAULT '',
            created_at {DT} NULL",
        'pageviews' => "
            id {ID},
            day VARCHAR(10) NOT NULL,
            path VARCHAR(255) NOT NULL,
            views INT NOT NULL DEFAULT 0",
        'login_attempts' => "
            id {ID},
            ip VARCHAR(64) NOT NULL,
            username VARCHAR(120) NOT NULL DEFAULT '',
            created_at {DT} NULL",
    ];

    $map = $driver === 'sqlite'
        ? ['{ID}' => 'INTEGER PRIMARY KEY AUTOINCREMENT', '{TEXT}' => 'TEXT', '{LONGTEXT}' => 'TEXT', '{DT}' => 'TEXT']
        : ['{ID}' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', '{TEXT}' => 'TEXT', '{LONGTEXT}' => 'LONGTEXT', '{DT}' => 'DATETIME'];

    $suffix = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $out = [];
    foreach ($tables as $name => $cols) {
        $out[] = 'CREATE TABLE IF NOT EXISTS ' . $name . ' (' . strtr($cols, $map) . "\n)" . $suffix;
    }
    $indexes = [
        'CREATE UNIQUE INDEX idx_pageviews_day_path ON pageviews (day, path)',
        'CREATE INDEX idx_services_cat ON services (category, sort_order)',
        'CREATE INDEX idx_submissions_created ON submissions (created_at)',
        'CREATE INDEX idx_activity_created ON activity (created_at)',
        'CREATE INDEX idx_login_attempts_ip ON login_attempts (ip, created_at)',
        'CREATE INDEX idx_faqs_grp ON faqs (grp, sort_order)',
    ];
    if ($driver === 'sqlite') {
        $indexes = array_map(fn($s) => preg_replace('/^CREATE (UNIQUE )?INDEX /', 'CREATE $1INDEX IF NOT EXISTS ', $s), $indexes);
    }
    return array_merge($out, $indexes);
}

function schema_install(): void
{
    foreach (schema_statements(DB::driver()) as $sql) {
        try {
            DB::pdo()->exec($sql);
        } catch (PDOException $e) {
            // Duplicate index on re-run (MySQL has no IF NOT EXISTS for indexes)
            if (stripos($sql, 'CREATE') === 0 && stripos($sql, 'INDEX') !== false) {
                continue;
            }
            throw $e;
        }
    }
}
