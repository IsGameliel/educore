<?php

namespace App\Services\Tenancy;

use App\Jobs\RunInstitutionTask;
use App\Models\Central\Institution;
use App\Services\OperationsHealth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstitutionTasks
{
    public const COMMANDS = ['billing' => 'tuition:generate', 'reminders' => 'tuition:remind', 'payments' => 'payments:reconcile', 'settlements' => 'payments:settlements'];

    public function dispatchDue(Institution $school, bool $force = false): int
    {
        if (! in_array($school->status, ['ready', 'suspended', 'archived'], true)) {
            return 0;
        }
        $now = now($school->timezone);
        $count = 0;
        foreach (['heartbeat', 'subscriptions', ...array_keys(self::COMMANDS)] as $task) {
            $financial = in_array($task, ['payments', 'settlements'], true);
            if (! $financial && $school->status !== 'ready') {
                continue;
            }
            if (in_array($task, ['billing', 'reminders'], true) && ! app(SemesterBilling::class)->accessible($school)) {
                continue;
            }
            if ($financial && ! app(InstitutionIntegrations::class)->key($school->id)) {
                continue;
            }
            $slot = match ($task) {
                'billing' => $now->format('Y-m-d-H-').intdiv((int) $now->format('i'), 15), 'payments' => $now->format('Y-m-d-H-').intdiv((int) $now->format('i'), 10), 'reminders','settlements' => $now->format('Y-m-d'), default => $now->format('Y-m-d-H-i')
            };
            if (! $force && (($task === 'reminders' && (int) $now->format('H') < 8) || ($task === 'settlements' && (int) $now->format('H') < 6))) {
                continue;
            }
            if ($force) {
                $slot .= '-manual-'.bin2hex(random_bytes(4));
            }
            $db = DB::connection(config('tenancy.central_connection'));
            $claimed = $db->transaction(function () use ($db, $school, $task, $slot) {
                $where = ['institution_id' => $school->id, 'task' => $task, 'slot' => $slot];
                $inserted = $db->table('institution_task_runs')->insertOrIgnore([...$where, 'status' => 'queued', 'attempts' => 1, 'started_at' => now()]);
                if ($inserted) {
                    return true;
                }
                $record = $db->table('institution_task_runs')->where($where)->lockForUpdate()->first();
                if ($record->status === 'success' || $record->attempts >= 3 || Carbon::parse($record->started_at)->gt(now()->subMinutes(5))) {
                    return false;
                }
                $db->table('institution_task_runs')->where('id', $record->id)->update(['status' => 'queued', 'attempts' => $record->attempts + 1, 'started_at' => now(), 'finished_at' => null]);

                return true;
            });
            if ($claimed) {
                try {
                    RunInstitutionTask::dispatch($school->id, $task, $slot);
                    $count++;
                } catch (\Throwable $e) {
                    $db->table('institution_task_runs')->where(['institution_id' => $school->id, 'task' => $task, 'slot' => $slot])->update(['status' => 'failed', 'failure_type' => $e::class, 'finished_at' => now()]);
                    Log::warning('Institution task enqueue failed.', ['institution_id' => $school->id, 'task' => $task, 'type' => $e::class]);
                }
            }
        }

        return $count;
    }

    public function execute(string $id, string $task, string $slot): void
    {
        $school = Institution::findOrFail($id);
        $db = DB::connection(config('tenancy.central_connection'));
        $query = $db->table('institution_task_runs')->where(['institution_id' => $id, 'task' => $task, 'slot' => $slot]);
        $record = (clone $query)->first();
        if (! $record || $record->status === 'success') {
            return;
        }
        $financial = in_array($task, ['payments', 'settlements'], true);
        if ((! $financial && $school->status !== 'ready') || (in_array($task, ['billing', 'reminders'], true) && (! app(SemesterBilling::class)->accessible($school) || ! app(FeaturePermissions::class)->allows('payment_management')))) {
            (clone $query)->update(['status' => 'skipped', 'finished_at' => now()]);

            return;
        }
        (clone $query)->update(['status' => 'running', 'started_at' => now()]);
        try {
            if ($task === 'heartbeat') {
                OperationsHealth::record('scheduler', 'success', 'Institution scheduler heartbeat.');
            } elseif ($task === 'subscriptions') {
                $status = app(SemesterBilling::class)->status($school);
                if ($status !== $school->subscription_status) {
                    $school->forceFill(['subscription_status' => $status])->save();
                    InstitutionAudit::record($id, 'subscription_state_changed', 'scheduler', ['status' => $status]);
                }
            } else {
                $command = self::COMMANDS[$task] ?? throw new \RuntimeException('Unknown institution task.');
                $run = fn () => Artisan::call($command);
                $code = $financial ? app(InstitutionIntegrations::class)->financialEvent($run) : $run();
                if ($code !== 0) {
                    throw new \RuntimeException('Institution task did not complete.');
                }
            }
            (clone $query)->update(['status' => 'success', 'finished_at' => now(), 'failure_type' => null]);
        } catch (\Throwable $e) {
            (clone $query)->update(['status' => 'failed', 'finished_at' => now(), 'failure_type' => $e::class]);
            throw $e;
        }
    }
}
