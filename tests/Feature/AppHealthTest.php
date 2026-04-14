<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

it('serves public get routes without dead responses', function () {
    $routes = collect(Route::getRoutes())
        ->filter(function ($route) {
            $methods = $route->methods();
            $middleware = collect($route->gatherMiddleware());

            if (! in_array('GET', $methods, true) && ! in_array('HEAD', $methods, true)) {
                return false;
            }

            if (Str::contains($route->uri(), '{')) {
                return false;
            }

            return ! $middleware->contains(fn (string $name) => Str::contains($name, ['auth', 'verified']));
        })
        ->map(fn ($route) => '/'.ltrim($route->uri(), '/'))
        ->unique()
        ->values();

    $issues = [];

    foreach ($routes as $uri) {
        $response = $this->get($uri);
        $status = $response->getStatusCode();

        if ($status === 404 || $status >= 500) {
            $issues[] = sprintf('%s returned HTTP %d', $uri, $status);
        }
    }

    expect($issues)->toBe([], implode(PHP_EOL, $issues));
});

it('does not expose dead internal links or missing assets on public pages', function () {
    $pages = ['/', '/login', '/register', '/forgot-password'];
    $issues = [];
    $pageCache = [];

    foreach ($pages as $page) {
        $response = $this->get($page);
        $status = $response->getStatusCode();

        expect($status)->toBeLessThan(400);

        $html = $response->getContent();
        $pageCache[$page] = $html;

        foreach (extractHtmlTargets($html) as $target) {
            if (shouldSkipTarget($target)) {
                continue;
            }

            [$path, $fragment] = normalizeInternalTarget($page, $target);

            if ($path === null) {
                continue;
            }

            if (isStaticAssetPath($path) && fileExistsForPublicPath($path)) {
                continue;
            }

            $linkedResponse = $this->get($path);
            $linkedStatus = $linkedResponse->getStatusCode();

            if ($linkedStatus === 404 || $linkedStatus >= 500) {
                $issues[] = sprintf('%s links to %s which returns HTTP %d', $page, $target, $linkedStatus);
                continue;
            }

            if ($fragment !== null) {
                $linkedHtml = $pageCache[$path] ?? $linkedResponse->getContent();
                $pageCache[$path] = $linkedHtml;

                if (! pageContainsFragment($linkedHtml, $fragment)) {
                    $issues[] = sprintf('%s links to missing fragment %s on %s', $page, '#'.$fragment, $path);
                }
            }
        }
    }

    expect($issues)->toBe([], implode(PHP_EOL, $issues));
});

function extractHtmlTargets(string $html): array
{
    preg_match_all('/<(?:a|link|script|img)\b[^>]+\b(?:href|src)=["\']([^"\']+)["\']/i', $html, $matches);

    return array_values(array_unique($matches[1] ?? []));
}

function shouldSkipTarget(string $target): bool
{
    return $target === ''
        || $target === '#'
        || Str::startsWith($target, ['mailto:', 'tel:', 'javascript:', 'data:']);
}

function normalizeInternalTarget(string $currentPath, string $target): array
{
    $appHost = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
    $parts = parse_url($target);

    if (($parts['scheme'] ?? null) !== null) {
        $targetHost = $parts['host'] ?? null;

        if ($targetHost !== null && ! in_array($targetHost, [$appHost, 'localhost', '127.0.0.1'], true)) {
            return [null, null];
        }
    }

    if (Str::startsWith($target, '#')) {
        return [$currentPath, ltrim($target, '#')];
    }

    $path = $parts['path'] ?? '';
    $fragment = $parts['fragment'] ?? null;

    if ($path === '') {
        return [$currentPath, $fragment];
    }

    if (! Str::startsWith($path, '/')) {
        $directory = Str::beforeLast($currentPath, '/');
        $directory = $directory === '' ? '/' : $directory.'/';
        $path = $directory.ltrim($path, '/');
    }

    return ['/'.ltrim($path, '/'), $fragment];
}

function isStaticAssetPath(string $path): bool
{
    return preg_match('/\.(?:css|js|png|jpe?g|gif|svg|ico|woff2?|ttf|eot|map)$/i', $path) === 1;
}

function fileExistsForPublicPath(string $path): bool
{
    $normalized = ltrim($path, '/');

    return file_exists(public_path($normalized));
}

function pageContainsFragment(string $html, string $fragment): bool
{
    return Str::contains($html, sprintf('id="%s"', $fragment))
        || Str::contains($html, sprintf("id='%s'", $fragment));
}
