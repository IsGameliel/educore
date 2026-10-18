#!/bin/sh
set -eu
PROJECT_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
PHP_BIN=${PHP_BIN:-php}
cd "$PROJECT_ROOT"
# Short-lived workers suit cPanel accounts without Supervisor. The cron example
# uses flock to prevent overlapping runs. Host process/time limits still apply.
case "${1:-institutions}" in
    institutions) exec "$PHP_BIN" artisan tenancy:work --stop-when-empty --max-jobs=100 ;;
    central) exec "$PHP_BIN" artisan queue:work database --queue=default --stop-when-empty --max-time=50 --max-jobs=50 --timeout=120 --tries=3 --backoff=60 ;;
    *) echo 'Usage: worker.sh institutions|central' >&2; exit 1 ;;
esac
