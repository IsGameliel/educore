<?php

use App\Mail\UserAccountCredentials;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('renders student account credentials email with faculty department and level', function () {
    $faculty = new Faculty([
        'name' => 'Science',
    ]);

    $department = new Department([
        'name' => 'Computer Science',
        'faculty_id' => 1,
    ]);
    $department->setRelation('faculty', $faculty);

    $student = new User([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'usertype' => 'student',
        'level' => '300',
        'matric_number' => 'CSC/23/001',
    ]);
    $student->setRelation('department', $department);

    $html = (new UserAccountCredentials($student, 'Secret123!', 'created'))->render();

    expect($html)
        ->toContain('Science')
        ->toContain('Computer Science')
        ->toContain('300')
        ->toContain('Secret123!')
        ->toContain('ada@example.com');
});

it('renders updated staff account email without replacing password when none was provided', function () {
    $staff = new User([
        'name' => 'Registrar User',
        'email' => 'registrar@example.com',
        'usertype' => 'registrar',
    ]);
    $staff->setRelation('department', null);

    $html = (new UserAccountCredentials($staff, null, 'updated'))->render();

    expect($html)
        ->toContain('Registrar')
        ->toContain('Your existing password is unchanged.')
        ->toContain('registrar@example.com');
});
