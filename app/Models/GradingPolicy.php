<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradingPolicy extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['bands' => 'array', 'required_courses' => 'array'];

    public static function defaults(?int $passMark = null): array
    {
        return [
            'ca_max' => 30, 'exam_max' => 70, 'pass_mark' => $passMark ?? 40,
            'bands' => [
                ['min' => 70, 'grade' => 'A', 'point' => 5], ['min' => 60, 'grade' => 'B', 'point' => 4],
                ['min' => 50, 'grade' => 'C', 'point' => 3], ['min' => 45, 'grade' => 'D', 'point' => 2],
                ['min' => 40, 'grade' => 'E', 'point' => 1], ['min' => 0, 'grade' => 'F', 'point' => 0],
            ],
            'repeat_rule' => 'all', 'graduation_credits' => null, 'graduation_cgpa' => null, 'required_courses' => [],
        ];
    }

    public function snapshot(): array
    {
        return $this->only(array_merge(array_keys(self::defaults()), ['id', 'version', 'department_id', 'session']));
    }
}
