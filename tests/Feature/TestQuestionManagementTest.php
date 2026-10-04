<?php

use App\Models\Tests;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Department;

beforeEach(function () {
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->testRecord = Tests::create(['name' => 'Quiz', 'subject' => 'Computing', 'duration' => 30, 'status' => 0, 'level' => '100', 'department_id' => $department->id]);
    $this->actingAs(User::factory()->create(['usertype' => 'admin']));
    $this->payload = ['question_text' => 'Which is correct?', 'options' => ['One', 'Two', 'Three', 'Four'], 'correct_option' => '3', 'marks' => 2];
});

it('saves every answer option and displays its human readable number', function () {
    $url = route('admin.tests.questions', $this->testRecord);
    foreach (range(0, 3) as $index) {
        $this->from($url)->post(route('admin.tests.questions.store', $this->testRecord), array_replace($this->payload, ['correct_option' => (string) $index]))
            ->assertRedirect($url)->assertSessionHasNoErrors()->assertSessionHas('success');
        $question = $this->testRecord->questions()->latest('id')->firstOrFail();
        expect((int) $question->correct_option)->toBe($index)->and($question->options[$index])->toBe($this->payload['options'][$index]);
        $this->get($url)->assertOk()->assertSee('Correct Answer: Option '.($index + 1));
    }
});

it('shows validation errors and retains question input without saving invalid answers', function () {
    $url = route('admin.tests.questions', $this->testRecord);
    $this->from($url)->post(route('admin.tests.questions.store', $this->testRecord), array_replace($this->payload, ['correct_option' => '4']))
        ->assertSessionHasErrors('correct_option');
    $this->get($url)->assertOk()->assertSee('Please correct the following:')->assertSee('Which is correct?')->assertSee('value="Four"', false);
    $this->assertDatabaseCount('questions', 0);
    $this->post(route('admin.tests.questions.store', $this->testRecord), array_replace($this->payload, ['options' => null]))->assertSessionHasErrors('options');
});

it('edits only the selected question and saves the fourth answer correctly', function () {
    $first = $this->testRecord->questions()->create(array_replace($this->payload, ['question_text' => 'First question']));
    $second = $this->testRecord->questions()->create(array_replace($this->payload, ['question_text' => 'Second question']));
    $this->get(route('admin.tests.questions.edit', [$this->testRecord, $first]))->assertOk()->assertSee('First question')->assertDontSee('Second question');
    $this->put(route('admin.tests.questions.update', [$this->testRecord, $first]), $this->payload)->assertSessionHasNoErrors()->assertRedirect();
    expect((int) $first->fresh()->correct_option)->toBe(3)->and($second->fresh()->question_text)->toBe('Second question');
});

it('lets an admin delete a test with its questions and responses', function () {
    $question = $this->testRecord->questions()->create($this->payload);
    $response = \App\Models\Responses::create(['test_id' => $this->testRecord->id, 'student_id' => User::factory()->create()->id, 'answers' => [], 'score' => 0]);
    $other = Tests::create(array_replace($this->testRecord->only(['name', 'subject', 'duration', 'status', 'level', 'department_id']), ['name' => 'Keep this test']));
    $this->get(route('admin.tests.index'))->assertOk()->assertSee('Delete Test');
    $this->delete(route('admin.tests.destroy', $this->testRecord))->assertRedirect(route('admin.tests.index'))->assertSessionHas('success');
    $this->assertDatabaseMissing('tests', ['id' => $this->testRecord->id]);
    $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    $this->assertDatabaseMissing('responses', ['id' => $response->id]);
    $this->assertDatabaseHas('tests', ['id' => $other->id]);
});

it('allows only an assigned lecturer to delete their course test', function () {
    $lecturer = User::factory()->create(['usertype' => 'lecturer']);
    $this->actingAs($lecturer)->delete(route('lecturer.tests.destroy', $this->testRecord))->assertForbidden();
    $course = \App\Models\Courses::create(['code' => 'CSC101', 'title' => $this->testRecord->subject, 'department_id' => $this->testRecord->department_id, 'level' => '100', 'semester' => 'First', 'credit_unit' => 3]);
    $lecturer->assignedCourses()->attach($course->id);
    $this->get(route('lecturer.tests.index'))->assertOk()->assertSee('Delete Test');
    $this->delete(route('lecturer.tests.destroy', $this->testRecord))->assertRedirect(route('lecturer.tests.index'));
    $this->assertDatabaseMissing('tests', ['id' => $this->testRecord->id]);
});

it('does not allow students to delete tests', function () {
    $this->actingAs(User::factory()->create(['usertype' => 'student']));
    $this->delete(route('admin.tests.destroy', $this->testRecord))->assertForbidden();
    $this->delete(route('lecturer.tests.destroy', $this->testRecord))->assertForbidden();
    $this->assertDatabaseHas('tests', ['id' => $this->testRecord->id]);
});
