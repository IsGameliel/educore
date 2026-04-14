<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(?User $actor, string $action, string $description, array $context = []): ActivityLog
    {
        $subject = $context['subject'] ?? null;
        $targetUser = $context['target_user'] ?? null;

        return ActivityLog::create([
            'actor_id' => $actor?->id,
            'target_user_id' => $targetUser instanceof User ? $targetUser->id : ($context['target_user_id'] ?? null),
            'department_id' => $context['department_id'] ?? null,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject instanceof Model ? $subject->getMorphClass() : ($context['subject_type'] ?? null),
            'subject_id' => $subject instanceof Model ? $subject->getKey() : ($context['subject_id'] ?? null),
            'properties' => $context['properties'] ?? null,
        ]);
    }
}
