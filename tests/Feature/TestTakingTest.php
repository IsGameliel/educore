<?php

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Responses;
use App\Models\TestAttempt;
use App\Models\Tests;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $this->department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->student = User::factory()->create(['usertype' => 'student', 'level' => '100', 'department_id' => $this->department->id]);
    $this->quiz = Tests::create(['name' => 'Quiz', 'subject' => 'Computing', 'duration' => 2, 'status' => true, 'level' => '100', 'department_id' => $this->department->id]);
    foreach (range(1, 2) as $number) {
        $this->quiz->questions()->create(['question_text' => 'Question '.$number, 'options' => ['Wrong', 'Right'], 'correct_option' => 1, 'marks' => 3]);
    }
    $this->actingAs($this->student);
});

it('checks role cohort and publication on every test-taking endpoint', function ($changes) {
    if ($changes === 'inactive') {
        $this->quiz->update(['status' => false]);
    } else {
        $this->student->update($changes);
    }
    $this->get(route('student.tests.start', $this->quiz))->assertForbidden();
    $this->postJson(route('student.tests.storeAnswer', [$this->quiz, 0]))->assertForbidden();
    $this->postJson(route('student.tests.submit', $this->quiz))->assertForbidden();
    expect(TestAttempt::count())->toBe(0)->and(Responses::count())->toBe(0);
})->with(['staff' => [['usertype' => 'lecturer']], 'applicant' => [['usertype' => 'applicant']], 'level' => [['level' => '200']], 'department' => [['department_id' => null]], 'inactive' => ['inactive']]);

it('requires a started attempt and validates answers against its question', function () {
    $this->postJson(route('student.tests.storeAnswer', [$this->quiz, 0]))->assertStatus(409);
    $this->postJson(route('student.tests.submit', $this->quiz))->assertStatus(409);
    $this->get(route('student.tests.start', $this->quiz))->assertOk();
    $question = TestAttempt::sole()->questions[0];
    $this->postJson(route('student.tests.storeAnswer', [$this->quiz, 0]), ['answers' => [$question['id'] => 99]])->assertUnprocessable();
    $this->postJson(route('student.tests.storeAnswer', [$this->quiz, 0]), ['answers' => [$question['id'] => ['1']]])->assertUnprocessable();
    $this->postJson(route('student.tests.storeAnswer', [$this->quiz, 99]))->assertNotFound();
    expect(TestAttempt::sole()->answers)->toBe([]);
});

it('resumes saved answers and the original deadline across sessions and test edits', function () {
    $this->get(route('student.tests.start', $this->quiz))->assertOk();
    $attempt = TestAttempt::sole();
    $question = $attempt->questions[0];
    $this->postJson(route('student.tests.storeAnswer', [$this->quiz, 0]), ['answers' => [$question['id'] => 1]])->assertOk();
    session()->flush();
    $this->quiz->update(['duration' => 120]);
    $this->get(route('student.tests.start', $this->quiz))->assertOk()->assertSee('checked', false);
    expect(TestAttempt::sole()->expires_at->equalTo($attempt->expires_at))->toBeTrue()
        ->and(TestAttempt::sole()->questions)->toBe($attempt->questions)
        ->and(TestAttempt::sole()->answers[$question['id']])->toBe(1);
});

it('scores the original snapshot and handles repeated submissions without duplicate scores', function () {
    $this->get(route('student.tests.start', $this->quiz))->assertOk();
    $question = TestAttempt::sole()->questions[0];
    $this->postJson(route('student.tests.storeAnswer', [$this->quiz, 0]), ['answers' => [$question['id'] => 1]])->assertOk();
    $this->quiz->questions()->update(['correct_option' => 0, 'marks' => 99]);
    $url = route('student.tests.result', $this->quiz);
    $this->postJson(route('student.tests.submit', $this->quiz))->assertOk()->assertJsonPath('nextUrl', $url);
    $this->postJson(route('student.tests.submit', $this->quiz))->assertOk()->assertJsonPath('nextUrl', $url);
    $this->post(route('student.tests.submit', $this->quiz))->assertRedirect($url);
    $this->get($url)->assertOk()->assertSee('3 / 6');
    expect(Responses::count())->toBe(1)->and(Responses::sole()->score)->toBe(3)
        ->and(Responses::sole()->answers)->toBe([$question['id'] => 1]);
    expect(fn () => DB::table('responses')->insert(['test_id' => $this->quiz->id, 'student_id' => $this->student->id, 'answers' => '[]', 'score' => 999]))
        ->toThrow(QueryException::class);
});

it('finalizes saved answers at expiry and does not accept a late answer', function () {
    $this->get(route('student.tests.start', $this->quiz))->assertOk();
    $attempt = TestAttempt::sole();
    $first = $attempt->questions[0];
    $second = $attempt->questions[1];
    $this->postJson(route('student.tests.storeAnswer', [$this->quiz, 0]), ['answers' => [$first['id'] => 1]])->assertOk();
    $this->travelTo($attempt->expires_at);
    $this->postJson(route('student.tests.storeAnswer', [$this->quiz, 1]), ['answers' => [$second['id'] => 1]])
        ->assertOk()->assertJsonPath('nextUrl', route('student.tests.result', $this->quiz));
    expect(Responses::sole()->score)->toBe(3)->and(Responses::sole()->answers)->toBe([$first['id'] => 1]);
});

it('records a zero score when an unanswered attempt expires', function () {
    $this->get(route('student.tests.start', $this->quiz))->assertOk();
    $this->travelTo(TestAttempt::sole()->expires_at);
    $this->get(route('student.tests.start', $this->quiz))->assertRedirect(route('student.tests.result', $this->quiz));
    expect(Responses::sole()->score)->toBe(0)->and(TestAttempt::sole()->submitted_at)->not->toBeNull();
});

it('serves results only to their student and keeps them accessible after the test closes', function () {
    $this->get(route('student.tests.start', $this->quiz))->assertOk();
    $this->post(route('student.tests.submit', $this->quiz))->assertRedirect();
    $this->quiz->update(['status' => false]);
    $this->get(route('student.tests.result', $this->quiz))->assertOk();
    $this->get(route('student.tests.index'))->assertOk()->assertSee('View Result');
    $this->actingAs(User::factory()->create(['usertype' => 'student']));
    $this->get(route('student.tests.result', $this->quiz))->assertNotFound();
    $this->actingAs(User::factory()->create(['usertype' => 'admin']));
    $this->get(route('student.tests.result', $this->quiz))->assertForbidden();
});

it('rejects empty tests rather than starting an unusable attempt', function () {
    $this->quiz->questions()->delete();
    $this->get(route('student.tests.start', $this->quiz))->assertStatus(409);
    expect(TestAttempt::count())->toBe(0);
});
