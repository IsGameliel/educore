<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Auth\MustVerifyEmail;


class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  mixed  $user
     * @param  array  $input
     * @return void
     */

    public function update($user, array $input)
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'department_id' => ['nullable', 'exists:departments,id'], // Add validation for the department
            'level' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'], // Validate the photo
            'matric_number' => ['nullable', 'string'],
            'entry_year' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            ])->validateWithBag('updateProfileInformation');

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'department_id' => $input['department_id'] ?? $user->department_id, // Save department
                'level' => $input['level'],
                'matric_number' => $input['matric_number'] ?? $user->matric_number,
                'entry_year' => $input['entry_year'] ?? $user->entry_year,

            ])->save();
        }
        // Handle profile photo update
        if (isset($input['photo'])) {
            $this->updateProfilePhoto($user, $input['photo']);
        }
    }

    protected function updateProfilePhoto($user, $photo)
    {
        // Delete the old profile photo if it exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Store the new photo
        $filePath = $photo->store('profile_photos', 'public');

        // Update the user's profile photo path
        $user->forceFill([
            'profile_photo_path' => $filePath,
        ])->save();
    }


    /**
     * Update the given verified user's profile information.
    *
     * @param  mixed  $user
     * @param  array  $input
     * @return void
     */
    protected function updateVerifiedUser($user, array $input)
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
