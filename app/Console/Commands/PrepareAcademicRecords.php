<?php

namespace App\Console\Commands;

use App\Models\Result;
use App\Models\TranscriptDocument;
use App\Services\Academic\Grading;
use App\Services\Academic\ResultWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrepareAcademicRecords extends Command
{
    protected $signature = 'academic:prepare {--dry-run : Show counts without changing records or files}';

    protected $description = 'Snapshot legacy grading policies and move public transcripts into a private archive';

    public function handle(): int
    {
        $files = Storage::disk('public')->allFiles('documents/transcripts');
        $legacy = Result::whereNull('policy_snapshot')->count();
        $this->info("{$legacy} result(s) need baseline snapshots; ".count($files).' public transcript file(s) need private archival.');
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }
        // Keep legacy scores and grades exactly as stored; the baseline policy is not a claim about historical rules.
        Result::whereNull('policy_snapshot')->chunkById(100, function ($results) {
            foreach ($results as $result) {
                DB::transaction(function () use ($result) {
                    $result = Result::lockForUpdate()->findOrFail($result->id);
                    if ($result->policy_snapshot) {
                        return;
                    }
                    $snapshot = Grading::policy($result->department_id, $result->session);
                    $snapshot['legacy_baseline'] = true;
                    $before = $result->attributesToArray();
                    $result->policy_snapshot = $snapshot;
                    $result->grading_policy_id = $snapshot['id'] ?? null;
                    $result->saveQuietly();
                    ResultWorkflow::record($result, $before, 'legacy_baseline', 'Imported existing marks unchanged; policy requires review before publication.');
                });
            }
        });
        foreach ($files as $path) {
            $archive = 'legacy-transcripts/'.basename($path);
            $bytes = Storage::disk('public')->get($path);
            if (! Storage::disk('local')->put($archive, $bytes) || hash('sha256', Storage::disk('local')->get($archive)) !== hash('sha256', $bytes)) {
                $this->error('Failed to verify private archival of '.basename($path));

                return self::FAILURE;
            }
            if (! Storage::disk('public')->delete($path)) {
                $this->error('Could not remove public copy of '.basename($path));

                return self::FAILURE;
            }
        }
        Result::whereNotNull('transcript_path')->orWhereNotNull('full_transcript_path')->get()->each(function ($result) {
            foreach (['transcript_path', 'full_transcript_path'] as $field) {
                if ($result->$field && ! TranscriptDocument::where('path', 'documents/transcripts/'.basename($result->$field))->exists()) {
                    DB::table('results')->where('id', $result->id)->update([$field => null]);
                }
            }
        });
        $this->info('Legacy marks preserved as drafts. Legacy PDFs are archived privately; publish reviewed results and issue new documents.');

        return self::SUCCESS;
    }
}
