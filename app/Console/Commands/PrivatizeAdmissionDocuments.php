<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PrivatizeAdmissionDocuments extends Command
{
    protected $signature = 'admissions:privatize-documents';

    protected $description = 'Move existing admission uploads to private storage, verifying every copy before removing its public source';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $count = 0;
        foreach ($public->allFiles('admission-documents') as $path) {
            $contents = $public->get($path);
            if ($private->exists($path) && ! hash_equals(hash('sha256', $contents), hash('sha256', $private->get($path)))) {
                throw new RuntimeException("Private copy differs for {$path}; public source was retained. Resolve the conflict and rerun.");
            }
            if (! $private->exists($path) && ! $private->put($path, $contents)) {
                throw new RuntimeException("Could not write private copy for {$path}; public source was retained.");
            }
            if (! hash_equals(hash('sha256', $contents), hash('sha256', $private->get($path))) || ! $public->delete($path)) {
                throw new RuntimeException("Could not verify or remove public source for {$path}. Rerun after resolving storage permissions.");
            }
            $count++;
        }
        $this->info("Privatized {$count} admission documents. Existing database paths remain valid.");

        return self::SUCCESS;
    }
}
