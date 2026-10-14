<?php
/**
 * (Re-)import the FSCD pages, header/footer and media from app/seed/fscd/ into an installed site.
 * The installer runs the same import on a fresh database; use this for local resets and for
 * adding files that were missing at install time.
 *
 *   php app/cli/import-fscd.php            add new media; create missing pages/settings; keep existing ones
 *   php app/cli/import-fscd.php --force    also overwrite the pages, header/footer and settings from the seed
 *                                          (the previous page content is kept as a revision)
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
require dirname(__DIR__) . '/bootstrap.php';
require APP . '/lib/Forms.php';
require APP . '/lib/HtmlPage.php';
require APP . '/lib/FscdImport.php';
require APP . '/schema.php';

if (!is_installed()) {
    fwrite(STDERR, "Site is not installed yet. Run the installer first (it imports everything).\n");
    exit(1);
}
schema_install(); // adds tables introduced after the site was installed (e.g. page_revisions)
$o = getopt('', ['force']);
try {
    $res = DB::tx(fn() => (new FscdImport(isset($o['force'])))->run());
} catch (Throwable $e) {
    fwrite(STDERR, 'Import failed: ' . $e->getMessage() . "\n");
    exit(1);
}
foreach ($res['log'] as $l) {
    echo "  - $l\n";
}
foreach ($res['warnings'] as $w) {
    echo "  ! $w\n";
}
echo "Done.\n";
