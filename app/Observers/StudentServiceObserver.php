<?php

namespace App\Observers;

use App\Models\{ResultAppeal, TranscriptDocument, TranscriptRequest};
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class StudentServiceObserver
{
    public function created(Model $record): void
    {
        // Official documents are announced only once issuance has completed.
        if ($record instanceof TranscriptDocument) { return; }
        $this->record($record);
    }

    public function updated(Model $record): void
    {
        if ($record instanceof TranscriptDocument) {
            if (!$record->official || !$record->wasChanged('status') || $record->status !== 'revoked') { return; }
        } elseif (!$record->wasChanged(['status','response','decision_reason'])) {
            return;
        }
        $this->record($record);
    }

    private function record(Model $record): void
    {
        $kind = $record instanceof ResultAppeal ? 'appeal' : ($record instanceof TranscriptRequest ? 'transcript' : 'transcript_document');
        $label = $kind === 'appeal' ? 'Appeal' : ($kind === 'transcript' ? 'Transcript request' : 'Transcript document');
        ActivityLogger::log(auth()->user(), 'student_service_'.$kind, $label.' #'.$record->id.': '.str_replace('_',' ',$record->status).'.', [
            'subject'=>$record, 'target_user_id'=>$record->user_id,
            'properties'=>['status'=>$record->status, 'previous_status'=>$record->getRawOriginal('status'),
                'response'=>$record->response ?? $record->decision_reason ?? $record->revocation_reason,
                'course_code'=>$record->course_code, 'semester'=>$record->semester],
        ]);
    }
}
