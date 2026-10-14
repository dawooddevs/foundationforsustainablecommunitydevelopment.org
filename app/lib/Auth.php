<?php
/**
 * Session authentication, CSRF protection and role-based capabilities.
 */
final class Auth
{
    public const ROLES = [
        'admin' => [
            'label' => 'Administrator',
            'description' => 'Full access: content, media, users, settings and integrations.',
            'caps' => ['*'],
        ],
        'editor' => [
            'label' => 'Editor',
            'description' => 'Create, edit, publish and delete all content and media. Manage form submissions and redirects.',
            'caps' => ['content.view', 'content.edit', 'content.publish', 'content.delete', 'media.view', 'media.upload', 'media.delete', 'submissions.view', 'submissions.manage', 'redirects.manage', 'activity.view'],
        ],
        'author' => [
            'label' => 'Author',
            'description' => 'Create and edit content as drafts and upload media. Cannot publish or delete.',
            'caps' => ['content.view', 'content.edit', 'media.view', 'media.upload', 'submissions.view'],
        ],
        'viewer' => [
            'label' => 'Viewer',
            'description' => 'Read-only access to the dashboard, content and form submissions.',
            'caps' => ['content.view', 'media.view', 'submissions.view'],
        ],
    ];

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_name('ash_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => (BASE_PATH ?: '') . '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (is_dir(STORAGE) && is_writable(STORAGE)) {
            $dir = STORAGE . '/sessions';
            if (!is_dir($dir)) {
                @mkdir($dir, 0700, true);
            }
            if (is_dir($dir)) {
                session_save_path($dir);
            }
        }
        ini_set('session.gc_maxlifetime', '43200');
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        self::start();
        $id = (int)($_SESSION['uid'] ?? 0);
        if (!$id) {
            return null;
        }
        // Idle timeout: 12 hours
        if (!empty($_SESSION['seen']) && time() - (int)$_SESSION['seen'] > 43200) {
            self::logout();
            return null;
        }
        $_SESSION['seen'] = time();
        $u = DB::one('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$u || $u['status'] !== 'active' || ($_SESSION['pwh'] ?? '') !== substr(sha1($u['password']), 0, 16)) {
            self::logout();
            return null;
        }
        self::$user = $u;
        return $u;
    }

    public static function attempt(string $login, string $password): array
    {
        $ip = client_ip();
        $since = date('Y-m-d H:i:s', time() - 900);
        $recent = (int)DB::val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > ?', [$ip, $since]);
        if ($recent >= 8) {
            return ['ok' => false, 'error' => 'Too many failed attempts. Please wait 15 minutes and try again.'];
        }
        $u = DB::one('SELECT * FROM users WHERE username = ? OR email = ?', [$login, strtolower($login)]);
        if (!$u || !password_verify($password, $u['password'])) {
            DB::insert('login_attempts', ['ip' => $ip, 'username' => substr($login, 0, 120), 'created_at' => now()]);
            usleep(300000);
            return ['ok' => false, 'error' => 'Incorrect username or password.'];
        }
        if ($u['status'] !== 'active') {
            return ['ok' => false, 'error' => 'This account has been disabled. Contact an administrator.'];
        }
        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
            DB::update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
            $u = DB::one('SELECT * FROM users WHERE id = ?', [$u['id']]);
        }
        self::login($u);
        DB::delete('login_attempts', 'ip = ?', [$ip]);
        DB::update('users', ['last_login' => now()], 'id = ?', [$u['id']]);
        self::log('login', 'user', (int)$u['id'], $u['name'] ?: $u['username']);
        return ['ok' => true, 'user' => self::publicUser($u)];
    }

    public static function login(array $u): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        $_SESSION['pwh'] = substr(sha1($u['password']), 0, 16);
        $_SESSION['seen'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        self::$user = $u;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
    }

    public static function csrf(): string
    {
        self::start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function checkCsrf(?string $token): bool
    {
        self::start();
        return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
    }

    public static function caps(?string $role): array
    {
        return self::ROLES[$role ?? '']['caps'] ?? [];
    }

    public static function can(string $cap, ?array $user = null): bool
    {
        $user = $user ?? self::user();
        if (!$user) {
            return false;
        }
        $caps = self::caps($user['role']);
        return in_array('*', $caps, true) || in_array($cap, $caps, true);
    }

    public static function publicUser(array $u): array
    {
        $caps = self::caps($u['role']);
        if (in_array('*', $caps, true)) {
            $caps = ['*'];
        }
        return [
            'id' => (int)$u['id'],
            'username' => $u['username'],
            'email' => $u['email'],
            'name' => $u['name'],
            'role' => $u['role'],
            'role_label' => self::ROLES[$u['role']]['label'] ?? $u['role'],
            'avatar' => $u['avatar'] ? media_url($u['avatar']) : '',
            'caps' => $caps,
            'last_login' => $u['last_login'],
        ];
    }

    public static function log(string $action, string $type = '', ?int $id = null, string $label = ''): void
    {
        try {
            $u = self::$user;
            DB::insert('activity', [
                'user_id' => $u ? (int)$u['id'] : null,
                'user_name' => $u ? ($u['name'] ?: $u['username']) : 'System',
                'action' => $action,
                'object_type' => $type,
                'object_id' => $id,
                'label' => mb_substr($label, 0, 250),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            log_error('activity log: ' . $e->getMessage());
        }
    }
}
