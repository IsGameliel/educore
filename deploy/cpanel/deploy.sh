#!/usr/bin/env bash
set -euo pipefail
umask 027
REPO_ROOT=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
test -f "$HOME/.config/euvion/deploy.env" || { echo 'Configure ~/.config/euvion/deploy.env first.'; exit 1; }
# This is an account-owned private operator configuration, never repository input.
source "$HOME/.config/euvion/deploy.env"
: "${APP_ROOT:?}" "${PHP_BIN:?}" "${COMPOSER_BIN:?}"
NPM_BIN=${NPM_BIN:-npm}
sha=${1:-}
mode=${2:-deploy}
[[ "$sha" =~ ^[a-f0-9]{40}$ ]] || { echo 'Supply the full commit SHA.'; exit 1; }
[[ "$mode" == deploy || "$mode" == --prepare ]] || exit 1
[[ "$APP_ROOT" == "$HOME/"* && "$APP_ROOT" != *'..'* && "$APP_ROOT" != *' '* ]] || { echo 'Use an absolute application directory beneath this account home.'; exit 1; }
mkdir -p "$APP_ROOT/releases" "$APP_ROOT/shared/storage/app/private" "$APP_ROOT/shared/storage/app/public" \
  "$APP_ROOT/shared/storage/framework/cache/data" "$APP_ROOT/shared/storage/framework/sessions" \
  "$APP_ROOT/shared/storage/framework/views" "$APP_ROOT/shared/storage/logs"
exec 9>"$APP_ROOT/deploy.lock"
flock -n 9 || { echo 'Another deployment is running.'; exit 1; }
test -f "$APP_ROOT/shared/.env" || { echo 'Create the private shared/.env first.'; exit 1; }
cd "$REPO_ROOT"
test "$(git rev-parse "$sha^{commit}")" = "$sha"
release="$APP_ROOT/releases/$sha"
if [ ! -f "$release/.prepared" ]; then
  test ! -e "$release" || { echo 'An incomplete release already exists; inspect it before retrying.'; exit 1; }
  mkdir "$release"
  git archive "$sha" | tar -x -C "$release"
  if [ -d "$release/storage" ]; then mv "$release/storage" "$release/storage-template"; fi
  ln -s "$APP_ROOT/shared/storage" "$release/storage"
  ln -s "$APP_ROOT/shared/.env" "$release/.env"
  mkdir -p "$release/bootstrap/cache"
  cd "$release"
  "$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
  "$COMPOSER_BIN" check-platform-reqs --no-dev
  "$NPM_BIN" ci
  "$NPM_BIN" run build
  test -f public/build/manifest.json
  "$PHP_BIN" artisan view:cache
  "$PHP_BIN" artisan storage:link
  touch "$release/.prepared"
fi
cd "$release"
if [ "$mode" = --prepare ]; then
  echo "Prepared release: $release"
  echo 'Complete the one-time database bootstrap here, then rerun without --prepare.'
  exit 0
fi
"$PHP_BIN" artisan config:cache
# Files update automatically. Schema changes remain the existing supervised,
# backed-up operator workflow. A pending migration never silently mutates data.
"$PHP_BIN" artisan migrate:status --database=mysql --pending=1
"$PHP_BIN" artisan migrate:status --database=central --path=database/migrations \
  --path=database/central-migrations --path=database/tenant-migrations --pending=1
"$PHP_BIN" artisan tenancy:status
test ! -e "$APP_ROOT/current" || test -L "$APP_ROOT/current" || { echo 'current must be a release symlink.'; exit 1; }
test ! -e "$APP_ROOT/current-next" && test ! -L "$APP_ROOT/current-next"
ln -s "$release" "$APP_ROOT/current-next"
mv -Tf "$APP_ROOT/current-next" "$APP_ROOT/current"
echo "Activated commit $sha"
echo 'Verify HTTPS, platform login, school portals and cron logs. Prior releases are retained.'
