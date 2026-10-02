# Educore Backup Cron Setup

Educore already includes a Laravel backup command:

```bash
php artisan backup:database
```

The app scheduler registers that command every Sunday at `01:00` Africa/Lagos time (every seven days) in `routes/console.php`.

## Windows automatic backups

On this computer, with Windows set to West Central Africa time, install the weekly task from PowerShell as an administrator:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File ./scripts/install-weekly-backup.ps1
```

The task runs every Sunday at 01:00 local Windows time, including while signed out. If the computer is unavailable at that time, Windows runs the missed backup when available. PHP and MySQL must be available, and the project must remain at its installed location. The task runs under the installing user without storing their password. Re-run the installer if the project or PHP location changes.

Use either this Windows task or the Laravel/cPanel scheduler for this database to avoid duplicate weekly backups.

## Recommended cPanel setup

Use the Laravel scheduler in cPanel so all scheduled tasks stay in one place.

1. In cPanel, open `Cron Jobs`.
2. Create a cron job that runs every minute.
3. Point it to the scheduler wrapper script:

```bash
/bin/sh /home/USERNAME/path-to-educore/scripts/cpanel-scheduler.sh >/dev/null 2>&1
```

This runs `php artisan schedule:run`, and Laravel will trigger the weekly database backup at the configured time.

## Direct backup-only cPanel setup

If you prefer a dedicated cron job just for backups, create a weekly cron job such as:

```bash
0 1 * * 0 /bin/sh /home/USERNAME/path-to-educore/scripts/cpanel-weekly-backup.sh >/dev/null 2>&1
```

This runs the backup command directly every Sunday at `01:00`.

## Notes

- Update `USERNAME` and `path-to-educore` to your real cPanel account path.
- If your hosting provider uses a custom PHP path, set `PHP_BIN` inside the shell command or edit the script.
- Backup SQL files are stored in `storage/app/backups/database`.
- Backup logs are stored in `storage/logs/backups`.
- Retention is controlled by `DB_BACKUP_RETENTION_DAYS` in `.env`.

Example with an explicit PHP binary:

```bash
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php /bin/sh /home/USERNAME/path-to-educore/scripts/cpanel-scheduler.sh >/dev/null 2>&1
```
