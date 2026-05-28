<?php

namespace App\Imports;

use App\Models\User;
use App\Support\AccountCredentialMailer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentImport implements ToCollection, WithHeadingRow
{
    protected int $created = 0;
    protected int $queuedEmails = 0;
    protected array $failedRows = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $data = [
                'name' => trim((string) ($row['name'] ?? '')),
                'email' => trim((string) ($row['email'] ?? '')),
                'matric_number' => trim((string) ($row['matric_number'] ?? '')) ?: null,
                'level' => trim((string) ($row['level'] ?? '')),
                'department_id' => $row['department_id'] ?? null,
            ];

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'matric_number' => ['nullable', 'string', 'max:255', Rule::unique('users', 'matric_number')],
                'level' => ['required', Rule::in(['100', '200', '300', '400', '500', '600'])],
                'department_id' => ['required', 'exists:departments,id'],
            ]);

            if ($validator->fails()) {
                $this->failedRows[] = [
                    'row' => $rowNumber,
                    'email' => $data['email'] ?: 'N/A',
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            $plainPassword = Str::password(10, true, true, false, false);

            try {
                $user = DB::transaction(function () use ($data, $plainPassword) {
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($plainPassword),
                        'usertype' => 'student',
                        'matric_number' => $data['matric_number'],
                        'level' => $data['level'],
                        'department_id' => $data['department_id'],
                    ]);

                    $user->ownedTeams()->create([
                        'name' => $user->name . "'s Team",
                        'personal_team' => true,
                    ]);

                    return $user;
                });

                $this->created++;

                try {
                    AccountCredentialMailer::send($user, $plainPassword, 'created');
                    $this->queuedEmails++;
                } catch (\Throwable $mailException) {
                    $mailError = str_contains(strtolower($mailException->getMessage()), 'failed to authenticate on smtp server')
                        ? 'Student was created, but the welcome email could not be queued because the mail server credentials are invalid.'
                        : 'Student was created, but the welcome email could not be queued.';

                    Log::error('Failed to queue student import welcome email.', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'exception' => $mailException,
                    ]);

                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'email' => $user->email,
                        'errors' => [$mailError],
                    ];
                }
            } catch (\Throwable $exception) {
                Log::error('Student import failed.', [
                    'row' => $rowNumber,
                    'email' => $data['email'],
                    'exception' => $exception,
                ]);

                $this->failedRows[] = [
                    'row' => $rowNumber,
                    'email' => $data['email'] ?: 'N/A',
                    'errors' => ['This row could not be imported.'],
                ];
            }
        }
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function emailedCount(): int
    {
        return $this->queuedEmails;
    }

    public function failedRows(): array
    {
        return $this->failedRows;
    }
}
