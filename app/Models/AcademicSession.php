<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    protected $fillable = [
        'name',
        'start_year',
        'end_year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    public static function isValidFormat(string $value): bool
    {
        if (preg_match('/^(?<start>\d{4})\/(?<end>\d{4})$/', trim($value), $matches) !== 1) {
            return false;
        }

        return ((int) $matches['end']) === ((int) $matches['start'] + 1);
    }

    public static function parseYears(string $value): array
    {
        preg_match('/^(?<start>\d{4})\/(?<end>\d{4})$/', trim($value), $matches);

        return [
            'start_year' => (int) ($matches['start'] ?? 0),
            'end_year' => (int) ($matches['end'] ?? 0),
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function current(): ?self
    {
        return static::query()
            ->active()
            ->orderByDesc('start_year')
            ->first();
    }

    public static function currentName(): ?string
    {
        return static::current()?->name;
    }
}
