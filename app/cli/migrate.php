<?php
/**
 * Runs pending content migrations in app/migrations/ (oldest first). Each one runs once;
 * applied migrations are recorded in the settings table. Called by the deploy workflow.
 *
 *   php app/cli/migrate.php            run pending migrations
 *   php app/cli/migrate.php --list     show status
 *   php app/cli/migrate.php --rerun=ID run one migration again (e.g. 2026_10_03_001_wp_content_import)
 *
 * A migration returns an array of log lines when finished, or false to be retried on the
 * next run (for example when a download failed). After 3 unfinished attempts it is skipped.
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
require dirname(__DIR__) . '/bootstrap.php';

if (!is_installed()) {
    echo "Site is not installed yet; nothing to migrate.\n";
    exit(0);
}

$o = getopt('', ['list', 'rerun:']);
$applied = json_decode((string)Settings::get('migrations_applied', '[]'), true) ?: [];
$attempts = json_decode((string)Settings::get('migration_attempts', '{}'), true) ?: [];
$files = glob(APP . '/migrations/*.php') ?: [];
sort($files);

if (isset($o['list'])) {
    foreach ($files as $f) {
        $id = basename($f, '.php');
        echo (in_array($id, $applied, true) ? '[done]    ' : '[pending] ') . $id . (isset($attempts[$id]) ? " (attempts: {$attempts[$id]})" : '') . "\n";
    }
    exit(0);
}

$rerun = $o['rerun'] ?? null;
$ran = 0;
$exit = 0;
foreach ($files as $f) {
    $id = basename($f, '.php');
    if ($rerun !== null) {
        if ($id !== $rerun) continue;
    } elseif (in_array($id, $applied, true)) {
        continue;
    } elseif (($attempts[$id] ?? 0) >= 3) {
        echo "Skipping {$id}: not finished after 3 attempts (run with --rerun={$id} to try again).\n";
        continue;
    }
    echo "Running {$id}\n";
    $ran++;
    try {
        $fn = require $f;
        $result = $fn();
    } catch (Throwable $e) {
        fwrite(STDERR, "  FAILED: " . $e->getMessage() . "\n");
        $exit = 1;
        break;
    }
    if ($result === false) {
        $attempts[$id] = ($attempts[$id] ?? 0) + 1;
        Settings::set('migration_attempts', json_encode($attempts));
        echo "  not finished, will retry on the next run (attempt {$attempts[$id]} of 3)\n";
        continue;
    }
    foreach ((array)$result as $line) {
        echo "  - {$line}\n";
    }
    if (!in_array($id, $applied, true)) {
        $applied[] = $id;
        Settings::set('migrations_applied', json_encode($applied));
    }
    unset($attempts[$id]);
    Settings::set('migration_attempts', json_encode((object)$attempts));
    echo "  done\n";
}
if ($rerun !== null && !$ran) {
    fwrite(STDERR, "No migration named {$rerun}\n");
    $exit = 1;
}
if (!$ran && $rerun === null) {
    echo "Nothing to migrate.\n";
}
exit($exit);
