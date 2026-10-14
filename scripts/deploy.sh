#!/usr/bin/env bash
# Deploy the website to SiteGround (or any host) over FTP/FTPS/SFTP.
#
#   FTP_HOST=ftp.example.com FTP_USER=user FTP_PASS='secret' FTP_DIR=/public_html ./scripts/deploy.sh
#
# Optional:
#   FTP_PROTO=ftp|sftp   (default ftp — uses explicit TLS when the server supports it)
#   FTP_PORT=21|18765    (SiteGround SFTP uses port 18765)
#   DRY_RUN=1            (show what would be uploaded)
#
# Never uploads or deletes: app/config.php, the database/logs/sessions in app/storage,
# media in uploads/ (only the protective .htaccess files are uploaded), .git, node_modules. Existing server files are never deleted,
# so content, the database config and the media library are always preserved.
set -euo pipefail
cd "$(dirname "$0")/.."

: "${FTP_HOST:?Set FTP_HOST}"
: "${FTP_USER:?Set FTP_USER}"
: "${FTP_PASS:?Set FTP_PASS}"
FTP_DIR="${FTP_DIR:-/public_html}"
FTP_PROTO="${FTP_PROTO:-ftp}"
FTP_PORT="${FTP_PORT:-$([ "$FTP_PROTO" = sftp ] && echo 18765 || echo 21)}"

command -v lftp >/dev/null || { echo "lftp is required (apt-get install lftp / brew install lftp)"; exit 1; }

# Rebuild minified assets so site.min.* always match the sources
if command -v npx >/dev/null && [ -f node_modules/.bin/esbuild ]; then
  npm run -s build
fi

# PHP syntax check before anything goes live
if command -v php >/dev/null; then
  find . -name '*.php' -not -path './node_modules/*' -not -path './.git/*' -print0 | xargs -0 -n1 php -l >/dev/null
fi

DRY=""
[ "${DRY_RUN:-0}" = "1" ] && DRY="--dry-run"

lftp -p "$FTP_PORT" -u "$FTP_USER","$FTP_PASS" "$FTP_PROTO://$FTP_HOST" <<LFTP
set ftp:ssl-allow yes
set net:max-retries 3
set net:timeout 30
mirror --reverse --only-newer --no-perms --parallel=4 --verbose $DRY \
  --exclude-glob .git/ \
  --exclude-glob .github/ \
  --exclude-glob node_modules/ \
  --exclude-glob scripts/ \
  --exclude-glob docs/ \
  --exclude-glob .gitignore \
  --exclude-glob .DS_Store \
  --exclude-glob package.json \
  --exclude-glob package-lock.json \
  --exclude-glob README.md \
  --exclude-glob app/config.php \
  --exclude-glob 'app/storage/*.sqlite*' \
  --exclude-glob 'app/storage/*.log' \
  --exclude-glob app/storage/sessions/ \
  --exclude-glob uploads/20*/ \
  ./ "$FTP_DIR"
bye
LFTP

echo "Deploy complete → $FTP_DIR on $FTP_HOST"
