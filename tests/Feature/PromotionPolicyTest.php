<?php

use App\Models\{AcademicSession, Department, Faculty, PromotionPolicy, User};
use App\Services\Academic\SessionProgression;
use Illuminate\Support\Facades\DB;

function promotionPolicyFixture(int $failures): array
{
    $source = AcademicSession::create(['name' => '2024/2025', 'start_year' => 2024, 'end_year' => 2025, 'is_active' => true]);
    $target = AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => false]);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $student = User::factory()->create(['usertype' => 'student', 'department_id' => $department->id, 'level' => '100', 'entry_year' => 2024]);
    foreach (range(0, $failures + 1) as $i) {
        DB::table('results')->insert([
            'user_id' => $student->id, 'department_id' => $department->id, 'matric_number' => 'TEST001',
            'session' => $i >= 2 ? '2023/2024' : $source->name, 'semester' => $i === 1 ? 'Second' : 'First',
            'level' => '100', 'course_code' => 'CSC'.(100 + $i), 'course_title' => 'Course '.$i,
            'credit_unit' => 3, 'score' => $i >= 2 ? 20 : 60, 'grade' => $i >= 2 ? 'F' : 'B',
            'grade_point' => $i >= 2 ? 0 : 4, 'workflow_status' => 'published', 'outcome_status' => 'graded',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    return [$student, $target];
}

it('allows admins to view and save the policy', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $this->actingAs($admin)->get(route('admin.promotion-policy.edit'))->assertOk()->assertSee('Maximum allowed carryovers');
    $this->put(route('admin.promotion-policy.update'), ['max_carryovers' => 2])->assertRedirect(route('admin.promotion-policy.edit'));
    expect(PromotionPolicy::current()->max_carryovers)->toBe(2);
    expect(PromotionPolicy::current()->updated_by)->toBe($admin->id);
})->group('promotion-policy');

it('rejects invalid limits', function ($value) {
    $this->actingAs(User::factory()->create(['usertype' => 'admin']))
        ->put(route('admin.promotion-policy.update'), ['max_carryovers' => $value])->assertSessionHasErrors('max_carryovers');
    expect(PromotionPolicy::current()->max_carryovers)->toBe(0);
})->with([-1, 1.5, 'invalid', 1001]);

it('denies students access to promotion settings', function () {
    $this->actingAs(User::factory()->create(['usertype' => 'student']))
        ->get(route('admin.promotion-policy.edit'))->assertForbidden();
    $this->put(route('admin.promotion-policy.update'), ['max_carryovers' => 2])->assertForbidden();
});

it('enforces the inclusive carryover limit during actual promotion', function (int $failures, int $limit, bool $eligible) {
    [$student, $target] = promotionPolicyFixture($failures);
    PromotionPolicy::current()->update(['max_carryovers' => $limit]);
    $preview = app(SessionProgression::class)->preview($target);
    expect($preview['rows']->first()['carryovers'])->toBe($failures);
    expect($preview['rows']->first()['eligible'])->toBe($eligible);
    $response = $this->actingAs(User::factory()->create(['usertype' => 'admin']))
        ->post(route('admin.academic-sessions.activate', $target), [
            'preview_token' => $preview['token'], 'confirmed' => 1, 'student_ids' => [$student->id],
        ]);
    if ($eligible) {
        $response->assertSessionHasNoErrors();
        expect($student->fresh()->level)->toBe('200');
        $this->assertDatabaseHas('student_progressions', ['user_id' => $student->id, 'to_session_id' => $target->id]);
    } else {
        $response->assertSessionHasErrors('student_ids');
        expect($student->fresh()->level)->toBe('100');
        expect($target->fresh()->is_active)->toBeFalse();
    }
})->with([[0, 0, true], [1, 0, false], [2, 2, true], [3, 2, false]]);

it('rejects an activation preview after the policy changes', function () {
    [$student, $target] = promotionPolicyFixture(0);
    $preview = app(SessionProgression::class)->preview($target);
    PromotionPolicy::current()->update(['max_carryovers' => 2]);
    $this->actingAs(User::factory()->create(['usertype' => 'admin']))
        ->post(route('admin.academic-sessions.activate', $target), [
            'preview_token' => $preview['token'], 'confirmed' => 1, 'student_ids' => [$student->id],
        ])->assertSessionHasErrors('preview_token');
    expect($student->fresh()->level)->toBe('100');
});

it('renders the activation review with carryover counts', function () {
    [$student, $target] = promotionPolicyFixture(1);
    PromotionPolicy::current()->update(['max_carryovers' => 2]);
    $this->actingAs(User::factory()->create(['usertype' => 'admin']))
        ->get(route('admin.academic-sessions.review', $target))
        ->assertOk()
        ->assertSee('Carryovers: 1 / 2 allowed')
        ->assertSee('Available for your approval');
});

it('renders the activation review when there are no students', function () {
    $target = AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => false]);
    $this->actingAs(User::factory()->create(['usertype' => 'admin']))
        ->get(route('admin.academic-sessions.review', $target))
        ->assertOk()->assertSee('No students.');
});

it('does not count a failed course after it has been passed', function () {
    [$student, $target] = promotionPolicyFixture(1);
    $pass = (array) DB::table('results')->where('user_id', $student->id)->where('course_code', 'CSC102')->first();
    unset($pass['id']);
    DB::table('results')->insert(array_merge($pass, [
        'session' => '2024/2025', 'score' => 60, 'grade' => 'B', 'grade_point' => 4,
    ]));
    $row = app(SessionProgression::class)->preview($target)['rows']->first();
    expect($row['carryovers'])->toBe(0)->and($row['eligible'])->toBeTrue();
});

it('still blocks unresolved or unpublished results when carryovers are allowed', function (string $field, string $value) {
    [$student, $target] = promotionPolicyFixture(1);
    PromotionPolicy::current()->update(['max_carryovers' => 2]);
    DB::table('results')->where('user_id', $student->id)->where('course_code', 'CSC102')->update([$field => $value, 'session' => '2024/2025']);
    expect(app(SessionProgression::class)->preview($target)['rows']->first()['eligible'])->toBeFalse();
})->with([['outcome_status', 'withheld'], ['workflow_status', 'draft']]);
