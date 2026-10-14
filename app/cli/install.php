<?php
/**
 * CLI installer (local development / scripted installs).
 *
 *   php app/cli/install.php --driver=sqlite --username=admin --email=you@example.com --password=secret
 *   php app/cli/install.php --driver=mysql --host=localhost --name=db --user=u --pass=p --username=... --email=... --password=...
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
require dirname(__DIR__) . '/bootstrap.php';
require APP . '/lib/Installer.php';

$o = getopt('', ['driver::', 'host::', 'port::', 'name::', 'user::', 'pass::', 'sqlite::', 'username:', 'email:', 'password:', 'fullname::', 'site-url::', 'noindex::', 'force']);
if (is_installed() && !isset($o['force'])) {
    exit("Already installed (app/config.php exists). Use --force to reinstall.\n");
}
$driver = $o['driver'] ?? 'sqlite';
$db = $driver === 'sqlite'
    ? ['driver' => 'sqlite', 'sqlite_path' => $o['sqlite'] ?? 'app/storage/database.sqlite']
    : ['driver' => 'mysql', 'host' => $o['host'] ?? 'localhost', 'port' => (int)($o['port'] ?? 3306), 'name' => $o['name'] ?? '', 'user' => $o['user'] ?? '', 'pass' => $o['pass'] ?? ''];
if (isset($o['force']) && $driver === 'sqlite') {
    $p = ROOT . '/' . $db['sqlite_path'];
    foreach ([$p, $p . '-wal', $p . '-shm'] as $f) {
        @unlink($f);
    }
}
try {
    Installer::run($db, [
        'name' => $o['fullname'] ?? $o['username'],
        'username' => $o['username'],
        'email' => $o['email'],
        'password' => $o['password'],
    ], ['site_url' => $o['site-url'] ?? '', 'noindex' => ($o['noindex'] ?? '1') === '1', 'force' => isset($o['force'])]);
    echo "Installed successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Install failed: ' . $e->getMessage() . "\n");
    exit(1);
}
