<?php

return [
    'directory' => storage_path('app/backups'),
    'dump_binary' => env('DB_BACKUP_BINARY', 'mysqldump'),
    'mysql_binary' => env('DB_RESTORE_BINARY', 'mysql'),
    'timeout' => (int) env('DB_BACKUP_TIMEOUT', 900),
    'retention_days' => (int) env('DB_BACKUP_RETENTION_DAYS', 120),
];
