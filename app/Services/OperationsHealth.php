<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OperationsHealth
{
    public static function record(string $name, string $status, ?string $message = null): void
    {
        // Monitoring must never turn a successful payment/job into a failure.
        try {
            if (! Schema::hasTable('operation_health')) { return; }
            $timestamp = match ($status) {
                'running' => 'started_at', 'failed' => 'failed_at', default => 'succeeded_at',
            };
            DB::table('operation_health')->updateOrInsert(['name' => $name], [
                'status' => $status, $timestamp => now(), 'message' => $message, 'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Could not record operations health.', ['operation' => $name]);
        }
    }

    public static function rows(): array
    {
        $records = DB::table('operation_health')->get()->keyBy('name');
        $rows = [];
        foreach (['scheduler' => ['Scheduler', 5], 'queue' => ['Queue worker', 5],
            'backup' => ['Scheduled database backup', 8 * 24 * 60],
            'payments' => ['Payment reconciliation', 30], 'billing'=>['Invoice generation', 45],
            'reminders'=>['Tuition reminders', 26 * 60], 'settlements'=>['Settlement reconciliation', 26 * 60]] as $name => [$label, $minutes]) {
            $record = $records->get($name);
            $status = $record?->status ?? 'not observed';
            if ($record && $status === 'success' && (! $record->succeeded_at || now()->diffInMinutes(\Carbon\Carbon::parse($record->succeeded_at), true) > $minutes)) {
                $status = 'overdue';
            }
            if ($record && $status === 'running' && $record->started_at && now()->diffInMinutes(\Carbon\Carbon::parse($record->started_at), true) > 30) {
                $status = 'stalled';
            }
            if ($name === 'queue' && config('queue.default') === 'sync') { $status = 'synchronous'; }
            if ($name === 'queue' && config('queue.default') === 'null') { $status = 'disabled'; }
            $rows[] = compact('name', 'label', 'status', 'record');
        }
        if ($record = $records->get('finance_notifications')) {
            $rows[] = ['name'=>'finance_notifications', 'label'=>'Financial approval notifications', 'status'=>$record->status, 'record'=>$record];
        }
        return $rows;
    }
}
