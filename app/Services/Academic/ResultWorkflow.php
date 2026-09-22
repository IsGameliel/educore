<?php

namespace App\Services\Academic;

use App\Models\CourseRegistration;
use App\Models\Result;
use App\Models\ResultCorrection;
use App\Models\ResultRevision;
use App\Models\TranscriptDocument;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultWorkflow
{
    public static function record(Result $result, ?array $before, string $action, string $reason, ?int $approver = null, ?int $actorId = null): void
    {
        ResultRevision::create([
            'result_id' => $result->id, 'user_id' => $result->user_id, 'department_id' => $result->department_id, 'version' => $result->version ?? 1, 'action' => $action,
            'actor_id' => $actorId ?? auth()->id() ?? $result->uploaded_by, 'approver_id' => $approver,
            'reason' => $reason, 'before' => $before, 'after' => $result->attributesToArray(),
        ]);
    }

    public static function problems(Result $result): array
    {
        $scope = Result::where('department_id', $result->department_id)->where('session', $result->session)
            ->where('semester', $result->semester)->where('course_code', $result->course_code);
        $results = $scope->get();
        $registrations = CourseRegistration::where('session', $result->session)->where('semester', $result->semester)
            ->whereIn('status', ['registered', 'approved', 'completed'])
            ->whereHas('course', fn ($q) => $q->where('department_id', $result->department_id)
                ->where('code', $result->course_code)->forAcademicSession($result->session))
            ->pluck('user_id')->unique();
        $problems = [];
        foreach ($registrations->diff($results->pluck('user_id')) as $id) {
            $problems[] = "Missing result for student #{$id}.";
        }
        foreach ($results->pluck('user_id')->diff($registrations)->unique() as $id) {
            $problems[] = "Student #{$id} is not registered for this course/session.";
        }
        foreach ($results->groupBy('user_id') as $id => $rows) {
            foreach ($rows->groupBy(fn ($r) => $r->attempt_type === 'resit' ? 'resit' : 'primary') as $attempts) {
                if ($attempts->count() > 1) {
                    $problems[] = "Duplicate results for student #{$id}.";
                }
            }
        }
        foreach ($results as $row) {
            if (! $row->course_registration_id) {
                $problems[] = "Result #{$row->id} is not linked to a course registration. Correct the registration, then save the draft.";
            } else {
                try {
                    ResultRegistration::validate($row);
                } catch (ValidationException $exception) {
                    $problems[] = "Result #{$row->id} has an invalid course registration.";
                }
            }
            if (in_array($row->outcome_status, ['not_submitted', 'incomplete'], true)) {
                $problems[] = "Unresolved {$row->outcome_status} result for student #{$row->user_id}.";
            }
            if ($row->outcome_status === 'graded' && ($row->score === null || $row->grade === null || $row->getRawOriginal('grade_point') === null)) {
                $problems[] = "Missing graded marks for student #{$row->user_id}.";
            }
            if (! $row->policy_snapshot) {
                $problems[] = "Result #{$row->id} needs a grading policy snapshot; open and save the draft.";
            }
        }

        return $problems;
    }

    public static function transition(Result $result, User $actor, string $action, string $reason): void
    {
        DB::transaction(function () use ($result, $actor, $action, $reason) {
            User::whereKey($result->user_id)->lockForUpdate()->firstOrFail();
            $result = Result::lockForUpdate()->findOrFail($result->id);
            ResultAccess::edit($actor, $result);
            $steps = ['submit' => ['draft', 'submitted'], 'review' => ['submitted', 'reviewed'],
                'approve' => ['reviewed', 'approved'], 'publish' => ['approved', 'published']];
            $before = $result->attributesToArray();
            if ($action === 'return') {
                abort_unless($actor->dashboardRole() === 'admin'
                    || (in_array($result->workflow_status, ['submitted', 'reviewed'], true)
                        && ResultAccess::stage($actor, $result->department_id, 'review')), 403);
                if ($result->workflow_status !== 'draft') {
                    $result->version++;
                }
                foreach (TranscriptDocument::where('user_id', $result->user_id)->where('status', 'valid')->get() as $document) {
                    if (array_key_exists($result->id, $document->result_versions)) {
                        $document->update(['status' => 'revoked', 'revoked_by' => $actor->id, 'revocation_reason' => 'A source result was returned to draft.']);
                    }
                }
                $result->workflow_status = 'draft';
                $result->submitted_by = null;
                $result->approved_by = null;
                $result->published_at = null;
                $result->transcript_path = null;
                $result->full_transcript_path = null;
            } else {
                abort_unless(isset($steps[$action]), 422);
                [$from, $to] = $steps[$action];
                if ($result->workflow_status !== $from) {
                    throw ValidationException::withMessages(['action' => "This result must be {$from} first."]);
                }
                if ($action !== 'submit') {
                    abort_unless(ResultAccess::stage($actor, $result->department_id, $action), 403);
                }
                if ($actor->dashboardRole() !== 'admin' && in_array($action, ['review', 'approve'], true) && in_array($actor->id, [$result->uploaded_by, $result->submitted_by], true)) {
                    throw ValidationException::withMessages(['action' => 'Another authorized staff member must review and approve your submission.']);
                }
                if (in_array($action, ['approve', 'publish'], true) && ($issues = self::problems($result))) {
                    throw ValidationException::withMessages(['completeness' => implode(' ', $issues)]);
                }
                $result->workflow_status = $to;
                if ($action === 'submit') {
                    $result->submitted_by = $actor->id;
                }
                if ($action === 'approve') {
                    $result->approved_by = $actor->id;
                }
                if ($action === 'publish') {
                    $result->published_at = now();
                }
            }
            $result->saveQuietly();
            self::record($result, $before, $action, $reason, $action === 'approve' ? $actor->id : null, $actor->id);
            if ($action === 'publish') {
                ActivityLogger::log($actor, 'result_published', "Published {$result->course_code} result ({$result->session}, {$result->semester})", ['subject' => $result, 'target_user_id' => $result->user_id, 'department_id' => $result->department_id]);
            }
        });
    }

    public static function requestCorrection(Result $result, User $actor, array $values, string $reason, bool $adoptPolicy = false): void
    {
        DB::transaction(function () use ($result, $actor, $values, $reason, $adoptPolicy) {
            User::whereKey($result->user_id)->lockForUpdate()->firstOrFail();
            $result = Result::lockForUpdate()->findOrFail($result->id);
            ResultAccess::edit($actor, $result);
            if (! in_array($result->workflow_status, ['approved', 'published'], true)) {
                throw ValidationException::withMessages(['result' => 'Corrections are for approved or published results. Return other results to draft.']);
            }
            if (ResultCorrection::where('result_id', $result->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['result' => 'A correction is already pending.']);
            }
            $proposed = $values + $result->only(['outcome_status', 'attempt_type', 'credit_unit']);
            if ($proposed['attempt_type'] !== $result->attempt_type) {
                throw ValidationException::withMessages(['attempt_type' => 'Corrections cannot change the exam attempt type.']);
            }
            $policy = $result->policy_snapshot;
            if ($adoptPolicy) {
                abort_unless(ResultAccess::manager($actor), 403);
                $policy = Grading::policy($result->department_id, $result->session);
                $proposed['policy_snapshot'] = $policy;
                $proposed['grading_policy_id'] = $policy['id'] ?? null;
            }
            $proposed = array_merge($proposed, Grading::calculate($proposed, $policy));
            ResultCorrection::create(['result_id' => $result->id, 'base_version' => $result->version, 'requested_by' => $actor->id, 'proposed' => $proposed, 'reason' => $reason]);
            self::record($result, $result->attributesToArray(), 'correction_requested', $reason, null, $actor->id);
        });
    }

    public static function decideCorrection(ResultCorrection $correction, User $actor, string $decision, string $reason): void
    {
        DB::transaction(function () use ($correction, $actor, $decision, $reason) {
            $source = Result::findOrFail($correction->result_id);
            User::whereKey($source->user_id)->lockForUpdate()->firstOrFail();
            $result = Result::lockForUpdate()->findOrFail($correction->result_id);
            $correction = ResultCorrection::lockForUpdate()->findOrFail($correction->id);
            abort_unless(ResultAccess::stage($actor, $result->department_id, 'approve'), 403);
            if ($actor->dashboardRole() !== 'admin' && (int) $actor->id === (int) $correction->requested_by) {
                throw ValidationException::withMessages([
                    'correction' => 'Another admin or exam officer must approve or reject your correction request.',
                ]);
            }
            if ($correction->status !== 'pending' || $correction->base_version !== $result->version) {
                throw ValidationException::withMessages(['correction' => 'This correction has already been decided or its version is stale.']);
            }
            $before = $result->attributesToArray();
            if ($decision === 'approved') {
                $result->forceFill($correction->proposed);
                $result->version++;
                $result->approved_by = $actor->id;
                // Explicit publication is still required after a correction.
                $result->workflow_status = 'approved';
                $result->published_at = null;
                $result->transcript_path = null;
                $result->full_transcript_path = null;
                $result->saveQuietly();
                foreach (TranscriptDocument::where('user_id', $result->user_id)->where('status', 'valid')->get() as $document) {
                    if (array_key_exists($result->id, $document->result_versions)) {
                        $document->update(['status' => 'revoked', 'revoked_by' => $actor->id, 'revocation_reason' => 'A source result was corrected.']);
                    }
                }
            }
            $correction->update(['status' => $decision, 'decided_by' => $actor->id, 'decision_reason' => $reason]);
            self::record($result, $before, 'correction_'.$decision, $correction->reason.' | Decision: '.$reason, $actor->id, $actor->id);
        });
    }
}
