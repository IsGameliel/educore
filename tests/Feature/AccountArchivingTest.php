<?php

use App\Actions\Jetstream\DeleteUser;
use App\Models\ActivityLog;
use App\Models\CourseRegistration;
use App\Models\Courses;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\Result;
use App\Models\Team;
use App\Models\User;
use App\Services\StudentAccountMerge;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->student = User::factory()->create(['usertype' => 'student', 'name' => 'Archived Student', 'level' => '100', 'department_id' => $department->id]);
    $course = Courses::create(['code' => 'CSC101', 'title' => 'Computing', 'credit_unit' => 3, 'department_id' => $department->id, 'semester' => 'First', 'level' => '100']);
    $this->registration = CourseRegistration::create(['user_id' => $this->student->id, 'course_id' => $course->id, 'semester' => 'First', 'session' => '2026/2027']);
    $this->resultRecord = Result::create(['user_id' => $this->student->id, 'department_id' => $department->id, 'matric_number' => 'MAT001', 'session' => '2026/2027', 'semester' => 'First', 'level' => '100', 'course_code' => 'CSC101', 'course_title' => 'Computing', 'credit_unit' => 3, 'score' => 70]);
    $this->payment = Payment::create(['user_id' => $this->student->id, 'payable_type' => CourseRegistration::class, 'payable_id' => $this->registration->id, 'purpose' => 'late_registration', 'reference' => 'ARCHIVE-TEST', 'email' => $this->student->email, 'amount' => 500000]);
    $this->oldToken = $this->student->createToken('old access')->plainTextToken;
});

it('deactivates students without cascading academic records or failing on payment restrictions', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $this->actingAs($admin)->delete(route('admin.students.destroy', $this->student))->assertRedirect();
    expect(User::find($this->student->id))->toBeNull()
        ->and(User::withTrashed()->findOrFail($this->student->id)->trashed())->toBeTrue()
        ->and($this->registration->fresh())->not->toBeNull()
        ->and($this->resultRecord->fresh())->not->toBeNull()
        ->and($this->payment->fresh())->not->toBeNull();
    expect($this->registration->fresh()->student->name)->toBe('Archived Student')
        ->and($this->payment->fresh()->user->name)->toBe('Archived Student')
        ->and(DB::table('personal_access_tokens')->where('tokenable_id', $this->student->id)->count())->toBe(0);
    $log = ActivityLog::where('action', 'account_deactivated')->sole();
    expect($log->actor_id)->toBe($admin->id)->and($log->target_user_id)->toBe($this->student->id);
});

it('blocks password sign-in and existing sessions after self-deactivation', function () {
    $this->post('/login', ['email' => $this->student->email, 'password' => 'password'])->assertRedirect();
    $this->assertAuthenticated();
    app(DeleteUser::class)->delete($this->student);
    auth()->forgetGuards();
    $this->get('/home')->assertRedirect('/login');
    $this->assertGuest();
    $this->post('/login', ['email' => $this->student->email, 'password' => 'password'])->assertSessionHasErrors();
    $this->assertGuest();
    $this->withHeader('Authorization', 'Bearer '.$this->oldToken)->getJson('/api/user')->assertUnauthorized();
    expect($this->payment->fresh()->user->id)->toBe($this->student->id);
});

it('rejects archived accounts in merge previews', function () {
    $other = User::factory()->create(['usertype' => 'student']);
    $this->student->delete();
    expect(fn () => app(StudentAccountMerge::class)->preview([$this->student->id, $other->id], $other->id))
        ->toThrow(ValidationException::class);
});

it('retains shared teams and their owner identity after deactivation', function () {
    $team = Team::forceCreate(['user_id' => $this->student->id, 'name' => 'Shared team', 'personal_team' => false]);
    $member = User::factory()->create();
    $team->users()->attach($member, ['role' => 'editor']);
    app(DeleteUser::class)->delete($this->student);
    expect($team->fresh()->owner->id)->toBe($this->student->id)
        ->and($team->fresh()->users->contains('id', $member->id))->toBeTrue();
});
