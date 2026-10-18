<?php

// Fresh-install SOURCE bundle. Composer and Vite builds run on the target/build host.
// Explicit allowlist prevents shipping local .env, school files, SQL or cached config.
$root = dirname(__DIR__);
$out = $root.'/storage/app/deployment';
if (! is_dir($out) && ! mkdir($out, 0750, true)) {
    throw new RuntimeException('Cannot create private deployment directory.');
}
$path = $out.'/euvion-cpanel-source-'.gmdate('Ymd-His').'.zip';
$zip = new ZipArchive;
if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    throw new RuntimeException('Cannot create deployment ZIP.');
}
$add = static function (string $relative) use ($root, $zip): void {
    $file = $root.'/'.$relative;
    if (is_link($file) || ! is_file($file)) {
        throw new RuntimeException('Missing or linked release input: '.$relative);
    }
    if (! $zip->addFile($file, str_replace('\\', '/', $relative))) {
        throw new RuntimeException('Cannot add release input: '.$relative);
    }
};
foreach (['app', 'config', 'resources', 'routes', 'database/migrations', 'database/central-migrations',
    'database/tenant-migrations', 'database/factories', 'database/seeders', 'public/asset', 'public/dash', 'deploy/cpanel'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isLink()) {
            throw new RuntimeException('Linked release input is not allowed.');
        }
        if (! $file->isFile()) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $filename = $file->getFilename();
        if ($filename === '.DS_Store' || $filename === 'Thumbs.db') {
            continue;
        }
        if (preg_match('/\.(sql|sqlite|zip|log|pem|key)$/i', $filename) || str_starts_with($filename, '.env') && $relative !== 'deploy/cpanel/.env.example') {
            throw new RuntimeException('Unexpected private file in release allowlist: '.$relative);
        }
        $add($relative);
    }
}
foreach (['artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'vite.config.js',
    'tailwind.config.js', 'postcss.config.js', 'bootstrap/app.php', 'bootstrap/providers.php',
    'public/index.php', 'public/.htaccess', 'public/favicon.ico', 'public/robots.txt',
    'scripts/cpanel-scheduler.sh', 'docs/cpanel-deployment.md', 'docs/domain-routing.md'] as $file) {
    $add($file);
}
foreach (['storage/app/private', 'storage/app/public', 'storage/framework/cache/data',
    'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $directory) {
    $zip->addEmptyDir($directory);
}
$zip->close();
file_put_contents($path.'.sha256', hash_file('sha256', $path).'  '.basename($path).PHP_EOL);
echo 'Source bundle: '.$path.PHP_EOL;
echo 'SHA-256: '.hash_file('sha256', $path).PHP_EOL;
echo 'Requires composer install and npm ci / npm run build before serving.'.PHP_EOL;
