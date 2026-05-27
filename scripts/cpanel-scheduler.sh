#!/bin/sh

set -eu

PROJECT_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
PHP_BIN=${PHP_BIN:-php}
LOG_DIR="$PROJECT_ROOT/storage/logs/backups"
LOG_FILE="$LOG_DIR/cpanel-scheduler.log"

mkdir -p "$LOG_DIR"

cd "$PROJECT_ROOT"

{
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running php artisan schedule:run"
    "$PHP_BIN" artisan schedule:run
} >> "$LOG_FILE" 2>&1
