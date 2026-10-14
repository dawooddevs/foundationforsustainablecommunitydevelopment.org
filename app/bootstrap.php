<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', __DIR__);
define('STORAGE', APP . '/storage');
define('VERSION', '1.0.0');

$GLOBALS['config'] = is_file(APP . '/config.php') ? require APP . '/config.php' : null;

// Work out the URL prefix the site is served from (supports sub-folder installs).
(function () {
    $base = '';
    $scriptFile = str_replace('\\', '/', (string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $root = str_replace('\\', '/', ROOT);
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptFile !== '' && str_starts_with($scriptFile, $root) && PHP_SAPI !== 'cli') {
        $rel = substr($scriptFile, strlen($root));
        if ($rel !== '' && str_ends_with($scriptName, $rel)) {
            $base = substr($scriptName, 0, strlen($scriptName) - strlen($rel));
        }
    }
    if (isset($GLOBALS['config']['base_path']) && $GLOBALS['config']['base_path'] !== null && $base === '') {
        $base = (string)$GLOBALS['config']['base_path'];
    }
    define('BASE_PATH', rtrim($base, '/'));
})();

require APP . '/lib/helpers.php';
require APP . '/lib/DB.php';
require APP . '/lib/Settings.php';
require APP . '/lib/Auth.php';
require APP . '/lib/Html.php';
require APP . '/lib/Media.php';
require APP . '/lib/Content.php';
require APP . '/lib/Seo.php';

date_default_timezone_set('UTC'); // TODO: set the organisation's timezone, e.g. 'America/New_York'
mb_internal_encoding('UTF-8');

$debug = (bool)cfg('debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(STORAGE) && is_writable(STORAGE)) {
    ini_set('error_log', STORAGE . '/php-error.log');
}

function is_installed(): bool
{
    return is_array($GLOBALS['config']) && !empty($GLOBALS['config']['installed']);
}

if (is_installed()) {
    try {
        DB::connect($GLOBALS['config']['db'] ?? []);
    } catch (Throwable $e) {
        log_error('DB connect failed: ' . $e->getMessage());
        http_response_code(503);
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . "\n");
            exit(1);
        }
        echo '<!doctype html><meta charset="utf-8"><title>Temporarily unavailable</title><body style="font-family:system-ui;padding:3rem;text-align:center"><h1>We\'ll be right back</h1><p>The website is temporarily unavailable. Please try again in a few minutes.</p>';
        exit;
    }
}
