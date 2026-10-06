<?php

use App\Models\User;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;

test('current profile information is available', function () {
    $this->actingAs($user = User::factory()->create());

    $component = Livewire::test(UpdateProfileInformationForm::class);

    expect($component->state['name'])->toEqual($user->name);
    expect($component->state['email'])->toEqual($user->email);
});

test('profile information can be updated', function () {
    $this->actingAs($user = User::factory()->create());

    Livewire::test(UpdateProfileInformationForm::class)
        ->set('state', ['name' => 'Test Name', 'email' => 'test@example.com'])
        ->call('updateProfileInformation');

    expect($user->fresh())
        ->name->toEqual('Test Name')
        ->email->toEqual('test@example.com');
});

test('students cannot change entry year through profile requests', function ($entryYear, $changeEmail) {
    $this->actingAs($user = User::factory()->create(['usertype' => 'student', 'entry_year' => $entryYear]));
    $email = $changeEmail ? 'updated-student@example.com' : $user->email;

    $this->put(route('user-profile-information.update'), [
        'name' => 'Updated Student', 'email' => $email, 'entry_year' => 2001, 'usertype' => 'admin',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->entry_year)->toBe($entryYear)
        ->and($user->fresh()->name)->toBe($user->name)
        ->and($user->fresh()->email)->toBe($email)
        ->and($user->fresh()->usertype)->toBe('student');
})->with([[2025, false], [2025, true], [null, false]]);

test('student profile shows a read only entry year and ignores forged livewire state', function () {
    $this->actingAs($user = User::factory()->create(['usertype' => 'student', 'entry_year' => 2025]));

    Livewire::test(\App\Livewire\Profile\UpdateProfileInformationForm::class)
        ->assertSee('Contact administration to correct your entry year.')
        ->assertDontSee('wire:model="state.entry_year"', false)
        ->assertDontSee('wire:model="state.name"', false)
        ->assertDontSee('wire:model="state.department_id"', false)
        ->assertDontSee('wire:model="state.level"', false)
        ->assertSee('Request a change of name')
        ->set('state.entry_year', 2001)
        ->set('state.name', 'Updated Student')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->fresh()->entry_year)->toBe(2025)->and($user->fresh()->name)->toBe($user->name);
});
