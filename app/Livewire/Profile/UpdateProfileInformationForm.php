<?php

namespace App\Livewire\Profile;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm as JetstreamUpdateProfileInformationForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;


class UpdateProfileInformationForm extends JetstreamUpdateProfileInformationForm
{
    public $departments = [];

    public function mount()
    {
        parent::mount();

        $this->departments = Department::orderBy('name')->get();
    }

    public function generateMatricNumber(): void
    {
        // Only students
        if (($this->user->usertype ?? null) !== 'student') {
            return;
        }

        // If already generated, just sync UI
        if (!blank($this->user->matric_number)) {
            $this->state['matric_number'] = $this->user->matric_number;
            return;
        }

        $deptId = $this->state['department_id'] ?? $this->user->department_id;

        // Department is required to generate the matric
        if (blank($deptId)) {
            $this->addError('matric_number', 'Please select a department before generating matric number.');
            return;
        }

        // Load department + faculty
        $department = Department::with('faculty')->find($deptId);

        if (!$department) {
            $this->addError('matric_number', 'Invalid department selected.');
            return;
        }

        // Dept code (use code if available, else derive from name)
        $deptCode = $department->code
            ?? Str::upper(Str::substr(preg_replace('/\s+/', '', $department->name ?? 'GEN'), 0, 3));

        // Faculty code (use faculty->code; fallback derive from faculty name)
        $facultyCode = $department->faculty?->code
            ?? Str::upper(Str::substr(preg_replace('/\s+/', '', $department->faculty?->name ?? 'FAC'), 0, 3));

        // ✅ STRICT: entry year must come from users.entry_year only
        $entryYearRaw = $this->user->entry_year;

        if (blank($entryYearRaw)) {
            $this->addError('matric_number', 'Entry year is required before generating matric number.');
            return;
        }

        // Keep digits only (handles if someone stored "2026/2027" accidentally)
        $yearStr = preg_replace('/\D/', '', (string) $entryYearRaw);

        // Must be either 2 digits (26) or 4 digits (2026)
        if (!in_array(strlen($yearStr), [2, 4], true)) {
            $this->addError('matric_number', 'Entry year must be 2 or 4 digits (e.g., 26 or 2026).');
            return;
        }

        // Convert to 2-digit year
        $year2 = (strlen($yearStr) === 4)
            ? substr($yearStr, -2)
            : str_pad($yearStr, 2, '0', STR_PAD_LEFT);

        // Final prefix: MUI/FAC/DEPT/YY/
        $prefix = "MUI/{$facultyCode}/{$deptCode}/{$year2}/";

        // Generate sequential number starting at 00001
        $attempts = 0;

        while ($attempts < 10) {
            $attempts++;

            $nextNumber = DB::transaction(function () use ($prefix) {
                // Lock matching rows so two users don't get the same number concurrently
                $maxSuffix = User::where('matric_number', 'like', $prefix . '%')
                    ->lockForUpdate()
                    ->selectRaw("MAX(CAST(SUBSTRING_INDEX(matric_number, '/', -1) AS UNSIGNED)) as max_num")
                    ->value('max_num');

                return ((int) $maxSuffix) + 1; // null => 1
            });

            $unique = str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
            $matric = $prefix . $unique;

            try {
                // Save
                $this->user->forceFill(['matric_number' => $matric])->save();

                // Update UI
                $this->state['matric_number'] = $matric;
                $this->dispatch('saved');
                return;
            } catch (QueryException $e) {
                // Retry only if duplicate (requires UNIQUE index on matric_number)
                if (!str_contains(strtolower($e->getMessage()), 'duplicate')) {
                    throw $e;
                }
            }
        }

        $this->addError('matric_number', 'Could not generate matric number. Please try again.');
    }

}
