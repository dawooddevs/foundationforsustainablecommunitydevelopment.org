<?php
/**
 * One-time web installer. Visit /install/ after uploading the files.
 * Creates app/config.php, the database tables, seed content and the first admin.
 * Locks itself automatically once installation succeeds.
 */
require dirname(__DIR__) . '/app/bootstrap.php';
require APP . '/lib/Installer.php';

header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex');

$installed = is_installed();
$errors = [];
$done = false;
$req = Installer::requirements();
$reqOk = !in_array(false, array_intersect_key($req, array_flip(['PHP 8.0 or newer', 'PDO extension', 'PDO MySQL or SQLite driver', 'DOM / XML extension', 'mbstring extension', '/app folder is writable (config file)'])), true);

session_name('ash_install');
session_start();
if (empty($_SESSION['itok'])) {
    $_SESSION['itok'] = bin2hex(random_bytes(16));
}

$v = array_merge([
    'driver' => extension_loaded('pdo_mysql') ? 'mysql' : 'sqlite',
    'host' => 'localhost', 'port' => '3306', 'name' => '', 'user' => '', 'pass' => '',
    'admin_name' => 'Dawood', 'admin_username' => 'Dawood', 'admin_email' => '', 'noindex' => '1',
], array_map(fn($x) => is_string($x) ? trim($x) : $x, $_POST));

if (!$installed && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!hash_equals($_SESSION['itok'], (string)($_POST['_tok'] ?? ''))) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }
    $pw = (string)($_POST['admin_password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._\-]{3,60}$/', $v['admin_username'])) $errors[] = 'Admin username must be 3–60 characters (letters, numbers, dot, dash, underscore).';
    if (!filter_var($v['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid admin email address.';
    if (strlen($pw) < 8) $errors[] = 'Admin password must be at least 8 characters.';
    if ($pw !== (string)($_POST['admin_password2'] ?? '')) $errors[] = 'The two passwords do not match.';
    if ($v['driver'] === 'mysql' && ($v['name'] === '' || $v['user'] === '')) $errors[] = 'Enter the MySQL database name and user.';
    if (!$errors) {
        $db = $v['driver'] === 'sqlite'
            ? ['driver' => 'sqlite', 'sqlite_path' => 'app/storage/database.sqlite']
            : ['driver' => 'mysql', 'host' => $v['host'] ?: 'localhost', 'port' => (int)($v['port'] ?: 3306), 'name' => $v['name'], 'user' => $v['user'], 'pass' => (string)($_POST['pass'] ?? '')];
        try {
            Installer::run($db, [
                'name' => $v['admin_name'], 'username' => $v['admin_username'], 'email' => $v['admin_email'], 'password' => $pw,
            ], ['site_url' => '', 'noindex' => !empty($_POST['noindex'])]);
            $done = true;
            unset($_SESSION['itok']);
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Install · Website</title>
<link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>">
<style>
  body { background: var(--bg); }
  .wrap { max-width: 760px; margin: 40px auto; padding: 0 16px 60px; }
  .hero { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; }
  .hero span { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; background: linear-gradient(135deg, #e23744, #ff6b5b); color: #fff; font-size: 22px; }
  .req { list-style: none; padding: 0; margin: 0; display: grid; gap: 6px; font-size: .9rem; }
  .req li { display: flex; justify-content: space-between; gap: 10px; padding: 8px 12px; border-radius: 10px; background: var(--panel-2); }
  .ok { color: var(--green); font-weight: 700; } .bad { color: var(--red); font-weight: 700; }
  .radio-row { display: flex; gap: 10px; flex-wrap: wrap; }
  .radio-row label { flex: 1 1 200px; }
  .err { padding: 12px 14px; border-radius: 12px; background: var(--red-50); color: var(--red); font-weight: 600; margin-bottom: 16px; }
  .err p { margin: 0; }
  section + section { margin-top: 18px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hero"><span>+</span><div><h1 style="font-size:1.6rem">Website Setup</h1><p class="muted" style="margin:2px 0 0">Creates the database, loads all content and your administrator account.</p></div></div>

<?php if ($done): ?>
  <section class="card"><div class="card__b">
    <h2 style="margin-bottom:8px">✅ Installation complete</h2>
    <p>The website and dashboard are ready. This installer is now locked.</p>
    <p class="muted">Search engines are <?= !empty($_POST['noindex']) ? '<strong>blocked</strong> (recommended for staging). Turn this off in Dashboard → Settings → SEO at launch.' : '<strong>allowed</strong>.' ?></p>
    <div class="ph__actions" style="margin-top:16px">
      <a class="btn btn--primary btn--lg" href="<?= e(url('admin/')) ?>">Open the dashboard →</a>
      <a class="btn btn--ghost btn--lg" href="<?= e(url('')) ?>" target="_blank">View website</a>
    </div>
  </div></section>
<?php elseif ($installed): ?>
  <section class="card"><div class="card__b">
    <h2 style="margin-bottom:8px">Already installed</h2>
    <p>This website is already set up. To reinstall, delete <code>app/config.php</code> on the server first (this does not delete your database).</p>
    <a class="btn btn--primary" href="<?= e(url('admin/')) ?>">Go to the dashboard</a>
  </div></section>
<?php else: ?>
  <?php if ($errors): ?><div class="err"><?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div><?php endif; ?>

  <section class="card">
    <div class="card__h"><h2>1. Server check</h2><span class="<?= $reqOk ? 'ok' : 'bad' ?>"><?= $reqOk ? 'Ready' : 'Action needed' ?></span></div>
    <div class="card__b"><ul class="req">
      <?php foreach ($req as $label => $okv): ?><li><span><?= e($label) ?></span><span class="<?= $okv ? 'ok' : 'bad' ?>"><?= $okv ? '✓' : '✗' ?></span></li><?php endforeach; ?>
    </ul></div>
  </section>

  <form method="post" autocomplete="off">
    <input type="hidden" name="_tok" value="<?= e($_SESSION['itok']) ?>">
    <section class="card">
      <div class="card__h"><h2>2. Database</h2></div>
      <div class="fields">
        <div class="f">
          <span class="f__label">Database type</span>
          <div class="radio-row">
            <label class="role-card"><input type="radio" name="driver" value="mysql"<?= $v['driver'] === 'mysql' ? ' checked' : '' ?>><span><strong>MySQL (recommended)</strong><small>SiteGround → Site Tools → Site → MySQL: create a database and user, then enter them below.</small></span></label>
            <label class="role-card"><input type="radio" name="driver" value="sqlite"<?= $v['driver'] === 'sqlite' ? ' checked' : '' ?>><span><strong>SQLite</strong><small>Zero-config file database stored in /app/storage. Fine for small sites and testing.</small></span></label>
          </div>
        </div>
        <div class="f f--half" data-mysql><label>Database host</label><input class="in" name="host" value="<?= e($v['host']) ?>"></div>
        <div class="f f--half" data-mysql><label>Port</label><input class="in" name="port" value="<?= e($v['port']) ?>"></div>
        <div class="f" data-mysql><label>Database name</label><input class="in" name="name" value="<?= e($v['name']) ?>" placeholder="e.g. dbabc123_site"></div>
        <div class="f f--half" data-mysql><label>Database user</label><input class="in" name="user" value="<?= e($v['user']) ?>"></div>
        <div class="f f--half" data-mysql><label>Database password</label><input class="in" type="password" name="pass" autocomplete="new-password"></div>
      </div>
    </section>

    <section class="card">
      <div class="card__h"><h2>3. Administrator account</h2></div>
      <div class="fields">
        <div class="f f--half"><label>Full name</label><input class="in" name="admin_name" value="<?= e($v['admin_name']) ?>"></div>
        <div class="f f--half"><label>Username</label><input class="in" name="admin_username" value="<?= e($v['admin_username']) ?>" required></div>
        <div class="f"><label>Email (also receives form notifications)</label><input class="in" type="email" name="admin_email" value="<?= e($v['admin_email']) ?>" required></div>
        <div class="f f--half"><label>Password</label><input class="in" type="password" name="admin_password" autocomplete="new-password" required minlength="8"></div>
        <div class="f f--half"><label>Confirm password</label><input class="in" type="password" name="admin_password2" autocomplete="new-password" required minlength="8"></div>
        <div class="f"><label class="toggle"><span>Hide the site from search engines (recommended while on the staging domain)</span><input type="checkbox" name="noindex" value="1"<?= $v['noindex'] ? ' checked' : '' ?>><span class="toggle__ui"></span></label></div>
      </div>
    </section>

    <section style="display:flex;justify-content:flex-end">
      <button class="btn btn--accent btn--lg" type="submit"<?= $reqOk ? '' : ' disabled' ?>>Install website</button>
    </section>
  </form>
  <script>
    (function () {
      function sync() { var my = document.querySelector('input[name=driver]:checked').value === 'mysql'; document.querySelectorAll('[data-mysql]').forEach(function (e) { e.style.display = my ? '' : 'none'; }); }
      document.querySelectorAll('input[name=driver]').forEach(function (r) { r.addEventListener('change', sync); });
      sync();
    })();
  </script>
<?php endif; ?>
</div>
</body>
</html>
