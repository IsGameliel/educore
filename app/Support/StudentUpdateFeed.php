<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentUpdateFeed
{
    public static function forUser(User $user, int $limit = 8): Collection
    {
        if ($user->usertype !== 'student' || !Schema::hasTable('activity_logs')) {
            return collect();
        }

        $studentLevel = (string) $user->level;

        return ActivityLog::with(['actor', 'targetUser'])
            ->whereIn('action', [
                'course_created',
                'course_updated',
                'material_uploaded',
                'material_updated',
                'test_published',
                'test_updated',
                'result_uploaded',
                'result_updated',
                'pass_mark_updated',
            ])
            ->where(function ($query) use ($user) {
                $query->where('target_user_id', $user->id)
                    ->orWhere('department_id', $user->department_id);
            })
            ->latest()
            ->take(max($limit * 4, 30))
            ->get()
            ->filter(function (ActivityLog $activity) use ($user, $studentLevel) {
                if (in_array($activity->action, ['result_uploaded', 'result_updated'], true)) {
                    return (int) $activity->target_user_id === (int) $user->id;
                }

                if ($activity->action === 'pass_mark_updated') {
                    return (int) $activity->department_id === (int) $user->department_id;
                }

                if ((int) $activity->department_id !== (int) $user->department_id) {
                    return false;
                }

                $activityLevel = (string) data_get($activity->properties, 'level', '');

                return $activityLevel === '' || $activityLevel === $studentLevel;
            })
            ->take($limit)
            ->values()
            ->map(fn (ActivityLog $activity) => self::format($activity));
    }

    protected static function format(ActivityLog $activity): array
    {
        $meta = self::meta($activity->action);

        return [
            'actor' => $activity->actor?->name ?? 'System',
            'title' => $meta['label'],
            'details' => $activity->description,
            'status' => $meta['status'],
            'status_color' => $meta['color'],
            'occurred_at' => $activity->created_at,
            'course_code' => data_get($activity->properties, 'course_code'),
            'semester' => data_get($activity->properties, 'semester'),
            'icon' => $meta['icon'],
        ];
    }

    protected static function meta(string $action): array
    {
        return match ($action) {
            'course_created' => ['label' => 'Course Update', 'status' => 'Added', 'color' => 'info', 'icon' => 'mdi-book-plus'],
            'course_updated' => ['label' => 'Course Update', 'status' => 'Changed', 'color' => 'warning', 'icon' => 'mdi-book-edit'],
            'material_uploaded' => ['label' => 'Lecture Material', 'status' => 'Uploaded', 'color' => 'primary', 'icon' => 'mdi-file-upload-outline'],
            'material_updated' => ['label' => 'Lecture Material', 'status' => 'Updated', 'color' => 'warning', 'icon' => 'mdi-file-document-edit-outline'],
            'test_published' => ['label' => 'Test Notification', 'status' => 'Published', 'color' => 'danger', 'icon' => 'mdi-clipboard-text-outline'],
            'test_updated' => ['label' => 'Test Notification', 'status' => 'Updated', 'color' => 'warning', 'icon' => 'mdi-clipboard-edit-outline'],
            'result_uploaded' => ['label' => 'Result Published', 'status' => 'Published', 'color' => 'success', 'icon' => 'mdi-file-document-check-outline'],
            'result_updated' => ['label' => 'Result Updated', 'status' => 'Updated', 'color' => 'warning', 'icon' => 'mdi-file-document-edit-outline'],
            'pass_mark_updated' => ['label' => 'Pass Mark Updated', 'status' => 'Updated', 'color' => 'warning', 'icon' => 'mdi-percent-outline'],
            default => ['label' => 'Activity', 'status' => 'Updated', 'color' => 'secondary', 'icon' => 'mdi-bell-outline'],
        };
    }
}
