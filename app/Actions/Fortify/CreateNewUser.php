<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Illuminate\Validation\Rule; // <-- Import the Rule class

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // 1. Validation with new Role and Department fields
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            'usertype' => ['required', 'string', Rule::in(['user', 'student'])],
            'department' => [
                Rule::requiredIf(isset($input['usertype']) && $input['usertype'] === 'student'),
                'nullable',
                'integer',
                'exists:departments,id',
            ],
            'level' => [
                Rule::requiredIf(isset($input['usertype']) && $input['usertype'] === 'student'),
                'nullable',
                'string',
                'max:10',
            ],
        ])->validate();

        return DB::transaction(function () use ($input) {
            // Use 'usertype' from the form input, defaulting to 'user' as a safety measure
            $usertype = $input['usertype'] ?? 'user';

            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'usertype' => $usertype,
                'department_id' => $usertype === 'student' ? $input['department'] : null,
                'level' => $usertype === 'student' ? $input['level'] : null,
            ]), function (User $user) {
                $this->createTeam($user);
            });
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
