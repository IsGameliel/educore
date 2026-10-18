<?php

namespace App\Console\Commands;

use App\Models\Central\InstitutionExport;
use App\Services\Tenancy\InstitutionArchives;
use Illuminate\Console\Command;

class PruneInstitutionExports extends Command
{
    protected $signature = 'tenancy:prune-exports';

    protected $description = 'Remove only expired institution export archives; never delete institution records';

    public function handle(InstitutionArchives $archives): int
    {
        $count = 0;
        InstitutionExport::where('status', 'ready')->where('expires_at', '<=', now())->chunkById(50, function ($exports) use ($archives, &$count) {
            foreach ($exports as $export) {
                $archives->remove($export);
                $count++;
            }
        }, 'id');
        $this->info('Expired export archives removed: '.$count);

        return self::SUCCESS;
    }
}
