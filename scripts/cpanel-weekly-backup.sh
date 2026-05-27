#!/bin/sh

set -eu

PROJECT_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
PHP_BIN=${PHP_BIN:-php}
LOG_DIR="$PROJECT_ROOT/storage/logs/backups"
LOG_FILE="$LOG_DIR/cpanel-weekly-backup.log"

mkdir -p "$LOG_DIR"

cd "$PROJECT_ROOT"

{
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running php artisan backup:database"
    "$PHP_BIN" artisan backup:database
} >> "$LOG_FILE" 2>&1
