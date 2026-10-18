<?php

use App\Services\Tenancy\InstitutionDatabaseCopies;

it('requires crash-safe rename support for production database copies', function (string $version, bool $supported) {
    expect(InstitutionDatabaseCopies::supportsAtomicRename($version))->toBe($supported);
})->with([
    ['5.7.44', false], ['8.0.40', true], ['8.4.6-commercial', true],
    ['5.5.5-10.4.32-MariaDB', false], ['10.6.0-MariaDB', false], ['10.6.1-MariaDB', true],
    ['5.5.5-10.11.8-MariaDB-0ubuntu0', true], ['unknown', false],
]);
