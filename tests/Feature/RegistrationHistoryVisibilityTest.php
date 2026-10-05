<?php

use App\Models\{AcademicSession, CourseRegistration, User};
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->create(['usertype' => 'admin']);
    $this->student = User::factory()->create(['usertype' => 'student']);
    AcademicSession::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
    foreach ([null => 'legacy', '2025/2026' => 'history'] as $session => $code) {
        $course = DB::table('courses')->insertGetId(['code' => $code, 'title' => $code.' course', 'credit_unit' => 3, 'semester' => 'First', 'level' => '100']);
        DB::table('course_registrations')->insert(['user_id' => $this->student->id, 'course_id' => $course, 'semester' => 'First', 'session' => $session ?: null, 'status' => 'registered']);
    }
});

it('shows all registration history by default to admins including missing sessions', function () {
    $this->actingAs($this->admin)->get(route('admin.course-registrations.show', $this->student))
        ->assertOk()->assertSee('legacy course')->assertSee('history course')->assertSee('Session not assigned')
        ->assertSee('2 registration records across all sessions')->assertDontSee('Save credit load');
});

it('keeps explicit session filtering and provides links to hidden history', function () {
    $this->actingAs($this->admin)->get(route('admin.course-registrations.show', ['student' => $this->student->id, 'session' => '2026/2027']))
        ->assertOk()->assertDontSee('legacy course')->assertDontSee('history course')
        ->assertSee('2 registration records across all sessions')->assertSee('Session not assigned');
    $this->get(route('admin.course-registrations.show', ['student' => $this->student->id, 'session' => 'unassigned']))
        ->assertOk()->assertSee('legacy course')->assertDontSee('history course');
});

it('lets students discover and view missing-session and historical registrations', function () {
    $this->actingAs($this->student)->get(route('student.courses.registered', ['semester' => 'First']))
        ->assertOk()->assertSee('2 registration records across all sessions')->assertSee('Session not assigned');
    $this->get(route('student.courses.registered', ['semester' => 'First', 'session' => 'all']))
        ->assertOk()->assertSee('legacy course')->assertSee('history course');
    $this->get(route('student.courses.registered', ['semester' => 'First', 'session' => 'unassigned']))
        ->assertOk()->assertSee('legacy course')->assertDontSee('history course');
});

it('keeps other students and other semesters outside the selected history', function () {
    $other = User::factory()->create(['usertype' => 'student']);
    $course = DB::table('courses')->insertGetId(['code' => 'PRIVATE', 'title' => 'Private course', 'credit_unit' => 3, 'semester' => 'First', 'level' => '100']);
    DB::table('course_registrations')->insert(['user_id' => $other->id, 'course_id' => $course, 'semester' => 'First', 'session' => null, 'status' => 'registered']);
    $this->actingAs($this->student)->get(route('student.courses.registered', ['semester' => 'First', 'session' => 'all']))
        ->assertOk()->assertDontSee('Private course');
    $this->get(route('student.courses.registered', ['semester' => 'Second', 'session' => 'all']))
        ->assertOk()->assertDontSee('legacy course')->assertDontSee('history course');
    expect(CourseRegistration::where('user_id', $this->student->id)->historySession('unassigned')->count())->toBe(1);
});
