#!/usr/bin/env bash
# Hostinger deployment helper. Executed over SSH from GitHub Actions.
# Does not run migrations, seeders or change application environment settings.
set -Eeuo pipefail
APP_NAME="${1:?app name required}"
EXPECTED_SHA="${2:?commit SHA required}"
ARCHIVE="${3:?build archive path required}"

case "$APP_NAME" in
  traceability) RELATIVE_APP="traceability"; HOST="traceability.devalejandre.com" ;;
  lpa) RELATIVE_APP="lpa"; HOST="lpa.devalejandre.com" ;;
  *) echo "Unknown application" >&2; exit 2 ;;
esac

BASE="/home/u641948971/domains/devalejandre.com"
APP="$BASE/apps/$RELATIVE_APP"
PUBLIC="$BASE/public_html/$RELATIVE_APP"
PHP="/opt/alt/php84/usr/bin/php"
COMPOSER="/usr/local/bin/composer2"

[[ "$EXPECTED_SHA" =~ ^[a-f0-9]{40}$ ]] || { echo "Invalid commit SHA" >&2; exit 2; }
[[ -f "$ARCHIVE" && -s "$ARCHIVE" ]] || { echo "Missing build archive" >&2; exit 2; }
[[ -x "$PHP" && -f "$COMPOSER" ]] || { echo "PHP/Composer missing" >&2; exit 2; }
[[ -f "$APP/.env" && -f "$APP/artisan" && -f "$PUBLIC/index.php" && -f "$PUBLIC/.htaccess" ]] || { echo "Incomplete deployment layout" >&2; exit 2; }
cd "$APP"

[[ "$(git branch --show-current)" == "main" ]] || { echo "Expected main checkout" >&2; exit 2; }
[[ -z "$(git status --porcelain --untracked-files=no)" ]] || { echo "Tracked files modified on server; refusing deployment" >&2; exit 2; }
grep -qx 'APP_ENV=production' .env || { echo "APP_ENV must be production" >&2; exit 2; }
grep -qx 'APP_DEBUG=false' .env || { echo "APP_DEBUG must be false" >&2; exit 2; }

LOCK="$APP/storage/app/hostinger-deploy.lock"
mkdir -p "$APP/storage/app"
mkdir "$LOCK" 2>/dev/null || { echo "Deployment already in progress. Inspect and remove stale lock manually." >&2; exit 2; }
cleanup() { rmdir "$LOCK" 2>/dev/null || true; }
trap cleanup EXIT

OLD_SHA="$(git rev-parse HEAD)"
git fetch --no-tags origin main
[[ "$(git rev-parse origin/main)" == "$EXPECTED_SHA" ]] || { echo "Expected SHA differs from remote main" >&2; exit 2; }
git merge-base --is-ancestor "$OLD_SHA" "$EXPECTED_SHA" || { echo "Not a fast-forward deployment" >&2; exit 2; }
if ! git diff --quiet "$OLD_SHA" "$EXPECTED_SHA" -- database/migrations/; then
  echo "Database migrations changed. Stop and follow a separate DB change plan." >&2
  exit 2
fi

# Avoid deployment if build archive is missing the exact Vite manifest.
tar -tzf "$ARCHIVE" | grep -qx 'build/manifest.json' || { echo "Build manifest missing" >&2; exit 2; }
BACKUPS="$APP/storage/app/deploy-backups"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP="$BACKUPS/$STAMP-$OLD_SHA"
mkdir -p "$BACKUP"
printf '%s\n' "$OLD_SHA" > "$BACKUP/previous-commit.txt"
[[ -d "$APP/public/build" && -d "$PUBLIC/build" ]] || { echo "Existing Vite assets missing" >&2; exit 2; }
tar -czf "$BACKUP/private-build.tgz" -C "$APP/public" build
tar -czf "$BACKUP/public-build.tgz" -C "$PUBLIC" build
echo "Backup created: $BACKUP"

if [[ "$OLD_SHA" == "$EXPECTED_SHA" ]]; then
  echo "Commit already deployed; no code changes."
  exit 0
fi

# No database writes. Keep the site in maintenance until deployment or rollback succeeds.
"$PHP" artisan down --retry=60
rollback() {
  local rc=$?
  if [[ $rc -ne 0 ]]; then
    echo "Deploy failed. Attempting code/assets rollback to $OLD_SHA" >&2
    git reset --hard "$OLD_SHA" || true
    "$PHP" "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction || true
    rm -rf "$APP/public/build" "$PUBLIC/build"
    tar -xzf "$BACKUP/private-build.tgz" -C "$APP/public" || true
    tar -xzf "$BACKUP/public-build.tgz" -C "$PUBLIC" || true
    "$PHP" artisan optimize:clear || true
    echo "Rollback attempted. Verify site and database manually." >&2
  fi
  "$PHP" artisan up || true
  cleanup
}
trap rollback EXIT

git merge --ff-only "$EXPECTED_SHA"
"$PHP" "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

STAGING="$APP/storage/app/deploy-staged-$STAMP"
mkdir -p "$STAGING"
tar -xzf "$ARCHIVE" -C "$STAGING"
[[ -s "$STAGING/build/manifest.json" ]] || { echo "Vite manifest empty" >&2; exit 3; }
rm -rf "$APP/public/build" "$PUBLIC/build"
cp -a "$STAGING/build" "$APP/public/build"
cp -a "$STAGING/build" "$PUBLIC/build"
rm -rf "$STAGING"
"$PHP" artisan optimize:clear
"$PHP" artisan route:list --path=demo --compact >/dev/null 2>&1 || "$PHP" artisan route:list --path=demo >/dev/null
[[ -s "$APP/public/build/manifest.json" && -s "$PUBLIC/build/manifest.json" ]] || exit 3
echo "Deployment successful: $HOST ($EXPECTED_SHA)"
 .env || { echo "APP_ENV must be production" >&2; exit 2; }
grep -qx 'APP_DEBUG=false' .env || { echo "APP_DEBUG must be false" >&2; exit 2; }

LOCK="$APP/storage/app/hostinger-deploy.lock"
mkdir -p "$APP/storage/app"
mkdir "$LOCK" 2>/dev/null || { echo "Deployment already in progress. Inspect and remove stale lock manually." >&2; exit 2; }
cleanup() { rmdir "$LOCK" 2>/dev/null || true; }
trap cleanup EXIT

OLD_SHA="$(git rev-parse HEAD)"
git fetch --no-tags origin main
[[ "$(git rev-parse origin/main)" == "$EXPECTED_SHA" ]] || { echo "Expected SHA differs from remote main" >&2; exit 2; }
git merge-base --is-ancestor "$OLD_SHA" "$EXPECTED_SHA" || { echo "Not a fast-forward deployment" >&2; exit 2; }
if ! git diff --quiet "$OLD_SHA" "$EXPECTED_SHA" -- database/migrations/; then
  echo "Database migrations changed. Stop and follow a separate DB change plan." >&2
  exit 2
fi

# Avoid deployment if build archive is missing the exact Vite manifest.
tar -tzf "$ARCHIVE" | grep -qx 'build/manifest.json' || { echo "Build manifest missing" >&2; exit 2; }
BACKUPS="$APP/storage/app/deploy-backups"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP="$BACKUPS/$STAMP-$OLD_SHA"
mkdir -p "$BACKUP"
printf '%s\n' "$OLD_SHA" > "$BACKUP/previous-commit.txt"
[[ -d "$APP/public/build" && -d "$PUBLIC/build" ]] || { echo "Existing Vite assets missing" >&2; exit 2; }
tar -czf "$BACKUP/private-build.tgz" -C "$APP/public" build
tar -czf "$BACKUP/public-build.tgz" -C "$PUBLIC" build
echo "Backup created: $BACKUP"

if [[ "$OLD_SHA" == "$EXPECTED_SHA" ]]; then
  echo "Commit already deployed; no code changes."
  exit 0
fi

# No database writes. Keep the site in maintenance until deployment or rollback succeeds.
"$PHP" artisan down --retry=60
rollback() {
  local rc=$?
  if [[ $rc -ne 0 ]]; then
    echo "Deploy failed. Attempting code/assets rollback to $OLD_SHA" >&2
    git reset --hard "$OLD_SHA" || true
    "$PHP" "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction || true
    rm -rf "$APP/public/build" "$PUBLIC/build"
    tar -xzf "$BACKUP/private-build.tgz" -C "$APP/public" || true
    tar -xzf "$BACKUP/public-build.tgz" -C "$PUBLIC" || true
    "$PHP" artisan optimize:clear || true
    echo "Rollback attempted. Verify site and database manually." >&2
  fi
  "$PHP" artisan up || true
  cleanup
}
trap rollback EXIT

git merge --ff-only "$EXPECTED_SHA"
"$PHP" "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

STAGING="$APP/storage/app/deploy-staged-$STAMP"
mkdir -p "$STAGING"
tar -xzf "$ARCHIVE" -C "$STAGING"
[[ -s "$STAGING/build/manifest.json" ]] || { echo "Vite manifest empty" >&2; exit 3; }
rm -rf "$APP/public/build" "$PUBLIC/build"
cp -a "$STAGING/build" "$APP/public/build"
cp -a "$STAGING/build" "$PUBLIC/build"
rm -rf "$STAGING"
"$PHP" artisan optimize:clear
"$PHP" artisan route:list --path=demo --compact >/dev/null 2>&1 || "$PHP" artisan route:list --path=demo >/dev/null
[[ -s "$APP/public/build/manifest.json" && -s "$PUBLIC/build/manifest.json" ]] || exit 3
echo "Deployment successful: $HOST ($EXPECTED_SHA)"
 .env || { echo "APP_DEBUG must be false" >&2; exit 2; }

LOCK="$APP/storage/app/hostinger-deploy.lock"
mkdir -p "$APP/storage/app"
mkdir "$LOCK" 2>/dev/null || { echo "Deployment already in progress. Inspect and remove stale lock manually." >&2; exit 2; }
cleanup() { rmdir "$LOCK" 2>/dev/null || true; }
trap cleanup EXIT

OLD_SHA="$(git rev-parse HEAD)"
git fetch --no-tags origin main
[[ "$(git rev-parse origin/main)" == "$EXPECTED_SHA" ]] || { echo "Expected SHA differs from remote main" >&2; exit 2; }
git merge-base --is-ancestor "$OLD_SHA" "$EXPECTED_SHA" || { echo "Not a fast-forward deployment" >&2; exit 2; }
if ! git diff --quiet "$OLD_SHA" "$EXPECTED_SHA" -- database/migrations/; then
  echo "Database migrations changed. Stop and follow a separate DB change plan." >&2
  exit 2
fi

# Avoid deployment if build archive is missing the exact Vite manifest.
tar -tzf "$ARCHIVE" | grep -qx 'build/manifest.json' || { echo "Build manifest missing" >&2; exit 2; }
BACKUPS="$APP/storage/app/deploy-backups"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP="$BACKUPS/$STAMP-$OLD_SHA"
mkdir -p "$BACKUP"
printf '%s\n' "$OLD_SHA" > "$BACKUP/previous-commit.txt"
[[ -d "$APP/public/build" && -d "$PUBLIC/build" ]] || { echo "Existing Vite assets missing" >&2; exit 2; }
tar -czf "$BACKUP/private-build.tgz" -C "$APP/public" build
tar -czf "$BACKUP/public-build.tgz" -C "$PUBLIC" build
echo "Backup created: $BACKUP"

if [[ "$OLD_SHA" == "$EXPECTED_SHA" ]]; then
  echo "Commit already deployed; no code changes."
  exit 0
fi

# No database writes. Keep the site in maintenance until deployment or rollback succeeds.
"$PHP" artisan down --retry=60
rollback() {
  local rc=$?
  if [[ $rc -ne 0 ]]; then
    echo "Deploy failed. Attempting code/assets rollback to $OLD_SHA" >&2
    git reset --hard "$OLD_SHA" || true
    "$PHP" "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction || true
    rm -rf "$APP/public/build" "$PUBLIC/build"
    tar -xzf "$BACKUP/private-build.tgz" -C "$APP/public" || true
    tar -xzf "$BACKUP/public-build.tgz" -C "$PUBLIC" || true
    "$PHP" artisan optimize:clear || true
    echo "Rollback attempted. Verify site and database manually." >&2
  fi
  "$PHP" artisan up || true
  cleanup
}
trap rollback EXIT

git merge --ff-only "$EXPECTED_SHA"
"$PHP" "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

STAGING="$APP/storage/app/deploy-staged-$STAMP"
mkdir -p "$STAGING"
tar -xzf "$ARCHIVE" -C "$STAGING"
[[ -s "$STAGING/build/manifest.json" ]] || { echo "Vite manifest empty" >&2; exit 3; }
rm -rf "$APP/public/build" "$PUBLIC/build"
cp -a "$STAGING/build" "$APP/public/build"
cp -a "$STAGING/build" "$PUBLIC/build"
rm -rf "$STAGING"
"$PHP" artisan optimize:clear
"$PHP" artisan route:list --path=demo --compact >/dev/null 2>&1 || "$PHP" artisan route:list --path=demo >/dev/null
[[ -s "$APP/public/build/manifest.json" && -s "$PUBLIC/build/manifest.json" ]] || exit 3
echo "Deployment successful: $HOST ($EXPECTED_SHA)"
 .env || { echo "APP_ENV must be production" >&2; exit 2; }
grep -qx 'APP_DEBUG=false' .env || { echo "APP_DEBUG must be false" >&2; exit 2; }

LOCK="$APP/storage/app/hostinger-deploy.lock"
mkdir -p "$APP/storage/app"
mkdir "$LOCK" 2>/dev/null || { echo "Deployment already in progress. Inspect and remove stale lock manually." >&2; exit 2; }
cleanup() { rmdir "$LOCK" 2>/dev/null || true; }
trap cleanup EXIT

OLD_SHA="$(git rev-parse HEAD)"
git fetch --no-tags origin main
[[ "$(git rev-parse origin/main)" == "$EXPECTED_SHA" ]] || { echo "Expected SHA differs from remote main" >&2; exit 2; }
git merge-base --is-ancestor "$OLD_SHA" "$EXPECTED_SHA" || { echo "Not a fast-forward deployment" >&2; exit 2; }
if ! git diff --quiet "$OLD_SHA" "$EXPECTED_SHA" -- database/migrations/; then
  echo "Database migrations changed. Stop and follow a separate DB change plan." >&2
  exit 2
fi

# Avoid deployment if build archive is missing the exact Vite manifest.
tar -tzf "$ARCHIVE" | grep -qx 'build/manifest.json' || { echo "Build manifest missing" >&2; exit 2; }
BACKUPS="$APP/storage/app/deploy-backups"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP="$BACKUPS/$STAMP-$OLD_SHA"
mkdir -p "$BACKUP"
printf '%s\n' "$OLD_SHA" > "$BACKUP/previous-commit.txt"
[[ -d "$APP/public/build" && -d "$PUBLIC/build" ]] || { echo "Existing Vite assets missing" >&2; exit 2; }
tar -czf "$BACKUP/private-build.tgz" -C "$APP/public" build
tar -czf "$BACKUP/public-build.tgz" -C "$PUBLIC" build
echo "Backup created: $BACKUP"

if [[ "$OLD_SHA" == "$EXPECTED_SHA" ]]; then
  echo "Commit already deployed; no code changes."
  exit 0
fi

# No database writes. Keep the site in maintenance until deployment or rollback succeeds.
"$PHP" artisan down --retry=60
rollback() {
  local rc=$?
  if [[ $rc -ne 0 ]]; then
    echo "Deploy failed. Attempting code/assets rollback to $OLD_SHA" >&2
    git reset --hard "$OLD_SHA" || true
    "$PHP" "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction || true
    rm -rf "$APP/public/build" "$PUBLIC/build"
    tar -xzf "$BACKUP/private-build.tgz" -C "$APP/public" || true
    tar -xzf "$BACKUP/public-build.tgz" -C "$PUBLIC" || true
    "$PHP" artisan optimize:clear || true
    echo "Rollback attempted. Verify site and database manually." >&2
  fi
  "$PHP" artisan up || true
  cleanup
}
trap rollback EXIT

git merge --ff-only "$EXPECTED_SHA"
"$PHP" "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

STAGING="$APP/storage/app/deploy-staged-$STAMP"
mkdir -p "$STAGING"
tar -xzf "$ARCHIVE" -C "$STAGING"
[[ -s "$STAGING/build/manifest.json" ]] || { echo "Vite manifest empty" >&2; exit 3; }
rm -rf "$APP/public/build" "$PUBLIC/build"
cp -a "$STAGING/build" "$APP/public/build"
cp -a "$STAGING/build" "$PUBLIC/build"
rm -rf "$STAGING"
"$PHP" artisan optimize:clear
"$PHP" artisan route:list --path=demo --compact >/dev/null 2>&1 || "$PHP" artisan route:list --path=demo >/dev/null
[[ -s "$APP/public/build/manifest.json" && -s "$PUBLIC/build/manifest.json" ]] || exit 3
echo "Deployment successful: $HOST ($EXPECTED_SHA)"
