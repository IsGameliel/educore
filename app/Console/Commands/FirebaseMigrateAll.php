<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kreait\Firebase\Contract\Firestore;

// ✅ Import your models (adjust to match your app)
use App\Models\User;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Test;
use App\Models\Question;
use App\Models\Response;
use App\Models\Result;

class FirebaseMigrateAll extends Command
{
    protected $signature = 'firebase:migrate-all
        {--chunk=300 : How many SQL rows to read per chunk}
        {--dry-run : Don’t write to Firestore (just count)}
        {--only= : Comma list of groups: core,academic,assessments,results}
        {--skip= : Comma list of groups to skip}';

    protected $description = 'Migrate ALL SQL data to Firebase Firestore in the correct order';

    public function handle(Firestore $firestore): int
    {
        $db = $firestore->database();
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $only = $this->csvOption('only');
        $skip = $this->csvOption('skip');

        $run = function (string $group) use ($only, $skip): bool {
            if (!empty($only) && !in_array($group, $only, true)) return false;
            if (in_array($group, $skip, true)) return false;
            return true;
        };

        // ✅ GROUP 1: Core reference data
        if ($run('core')) {
            $this->info('==> Migrating CORE (faculties, departments, users)...');
            $this->migrateTable($db, Faculty::query()->orderBy('id'), 'faculties', $chunk, $dryRun, fn($r) => [
                'id' => (int) $r->id,
                'name' => $r->name ?? null,
                'createdAt' => optional($r->created_at)->toIso8601String(),
                'updatedAt' => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->migrateTable($db, Department::query()->orderBy('id'), 'departments', $chunk, $dryRun, fn($r) => [
                'id' => (int) $r->id,
                'facultyId' => isset($r->faculty_id) ? (string) $r->faculty_id : null,
                'name' => $r->name ?? null,
                'createdAt' => optional($r->created_at)->toIso8601String(),
                'updatedAt' => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->migrateTable($db, User::query()->orderBy('id'), 'users', $chunk, $dryRun, fn($r) => [
                'id' => (int) $r->id,
                'name' => $r->name ?? null,
                'email' => $r->email ?? null,
                'departmentId' => isset($r->department_id) ? (string) $r->department_id : null,
                'level' => $r->level ?? null,
                'createdAt' => optional($r->created_at)->toIso8601String(),
                'updatedAt' => optional($r->updated_at)->toIso8601String(),
            ]);
        }

        // ✅ GROUP 2: Academic (courses, registrations, materials, schedules, prerequisites)
        if ($run('academic')) {
            $this->info('==> Migrating ACADEMIC (courses, course_registrations)...');
            $this->migrateTable($db, Course::query()->orderBy('id'), 'courses', $chunk, $dryRun, fn($r) => [
                'id' => (int) $r->id,
                'code' => $r->code ?? null,
                'title' => $r->title ?? null,
                'unit' => $r->unit ?? null,
                'departmentId' => isset($r->department_id) ? (string) $r->department_id : null,
                'level' => $r->level ?? null,
                'createdAt' => optional($r->created_at)->toIso8601String(),
                'updatedAt' => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->migrateTable($db, CourseRegistration::query()->orderBy('id'), 'course_registrations', $chunk, $dryRun, fn($r) => [
                'id' => (int) $r->id,
                'userId' => isset($r->user_id) ? (string) $r->user_id : null,
                'courseId' => isset($r->course_id) ? (string) $r->course_id : null,
                'session' => $r->session ?? null,
                'semester' => $r->semester ?? null,
                'createdAt' => optional($r->created_at)->toIso8601String(),
                'updatedAt' => optional($r->updated_at)->toIso8601String(),
            ]);
        }

        // ✅ GROUP 3: Assessments (tests, questions, responses)
        if ($run('assessments')) {
            $this->info('==> Migrating ASSESSMENTS (tests, questions, responses)...');
            $this->migrateTable($db, Test::query()->orderBy('id'), 'tests', $chunk, $dryRun, fn($r) => [
                'id' => (int) $r->id,
                'courseId' => isset($r->course_id) ? (string) $r->course_id : null,
                'departmentId' => isset($r->department_id) ? (string) $r->department_id : null,
                'level' => $r->level ?? null,
                'title' => $r->title ?? null,
                'createdAt' => optional($r->created_at)->toIso8601String(),
                'updatedAt' => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->migrateTable($db, Question::query()->orderBy('id'), 'questions', $chunk, $dryRun, fn($r) => [
                'id' => (int) $r->id,
                'testId' => isset($r->test_id) ? (string) $r->test_id : null,
                'question' => $r->question ?? null,
                'options' => $r->options ?? null, // if JSON in SQL, it may already be array-cast
                'answer' => $r->answer ?? null,
                'createdAt' => optional($r->created_at)->toIso8601String(),
                'updatedAt' => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->migrateTable($db, Response::query()->orderBy('id'), 'responses', $chunk, $dryRun, fn($r) => [
                'id' => (int) $r->id,
                'testId' => isset($r->test_id) ? (string) $r->test_id : null,
                'questionId' => isset($r->question_id) ? (string) $r->question_id : null,
                'userId' => isset($r->user_id) ? (string) $r->user_id : null,
                'selected' => $r->selected ?? null,
                'createdAt' => optional($r->created_at)->toIso8601String(),
                'updatedAt' => optional($r->updated_at)->toIso8601String(),
            ]);
        }

        // ✅ GROUP 4: Results
        if ($run('results')) {
            $this->info('==> Migrating RESULTS...');
            $this->migrateTable($db, Result::query()->orderBy('id'), 'results', $chunk, $dryRun, fn($r) => [
                'id' => (int) $r->id,
                'userId' => isset($r->user_id) ? (string) $r->user_id : null,
                'courseId' => isset($r->course_id) ? (string) $r->course_id : null,
                'testId' => isset($r->test_id) ? (string) $r->test_id : null,
                'score' => $r->score ?? null,
                'grade' => $r->grade ?? null,
                'transcriptPath' => $r->transcript_path ?? null,
                'createdAt' => optional($r->created_at)->toIso8601String(),
                'updatedAt' => optional($r->updated_at)->toIso8601String(),
            ]);
        }

        $this->info('✅ All selected groups migrated.');
        return self::SUCCESS;
    }

    private function migrateTable($db, $query, string $collection, int $chunk, bool $dryRun, callable $map): void
    {
        $total = 0;

        $query->chunk($chunk, function ($rows) use ($db, $collection, $map, $dryRun, &$total) {
            $batch = $db->batch();
            $ops = 0;

            foreach ($rows as $row) {
                $docId = (string) $row->id;
                $docRef = $db->collection($collection)->document($docId);

                if (!$dryRun) {
                    $batch->set($docRef, $map($row), ['merge' => true]);
                }

                $ops++;
                $total++;
            }

            if (!$dryRun && $ops > 0) {
                $batch->commit();
            }
        });

        $this->line("   -> {$collection}: {$total} rows");
    }

    private function csvOption(string $name): array
    {
        $value = (string) ($this->option($name) ?? '');
        $value = trim($value);
        if ($value === '') return [];
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
