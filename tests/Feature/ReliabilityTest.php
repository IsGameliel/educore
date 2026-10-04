<?php

use App\Imports\StudentImport;
use App\Models\{AcademicSession, Department, Faculty, Result, User};
use App\Services\{EnrollmentData, OperationsHealth};
use App\Services\Academic\SessionProgression;
use App\Services\Backups\DatabaseBackups;
use Illuminate\Support\Facades\{DB, Event, Mail};
use Illuminate\Queue\Events\Looping;

beforeEach(function () {
    Mail::fake();
    $faculty = Faculty::create(['name'=>'Science', 'code'=>'SCI']);
    $this->department = Department::create(['name'=>'Computing', 'faculty_id'=>$faculty->id]);
    $this->source = AcademicSession::create(['name'=>'2025/2026','start_year'=>2025,'end_year'=>2026,'is_active'=>true]);
    $this->target = AcademicSession::create(['name'=>'2026/2027','start_year'=>2026,'end_year'=>2027]);
    $this->admin = User::factory()->create(['usertype'=>'admin']);
    $this->student = User::factory()->create(['usertype'=>'student', 'department_id'=>$this->department->id, 'level'=>'100', 'entry_year'=>2025]);
});

function reliabilityResults($test, string $status = 'published', float $points = 5): void
{
    foreach (['First','Second'] as $semester) {
        Result::withoutEvents(fn () => Result::forceCreate([
            'user_id'=>$test->student->id, 'uploaded_by'=>$test->admin->id, 'matric_number'=>'REL001',
            'session'=>$test->source->name, 'semester'=>$semester, 'level'=>'100', 'course_code'=>'REL-'.$semester,
            'course_title'=>'Reliability', 'credit_unit'=>3, 'score'=>$points > 0 ? 80 : 20,
            'grade'=>$points > 0 ? 'A' : 'F', 'grade_point'=>$points, 'department_id'=>$test->department->id,
            'workflow_status'=>$status, 'outcome_status'=>'graded',
        ]));
    }
}

function reliabilityActivation($test, AcademicSession $target, array $ids = []): array
{
    return ['preview_token'=>app(SessionProgression::class)->preview($target)['token'], 'confirmed'=>1, 'student_ids'=>$ids];
}

it('requires activation review and protects both admin reliability pages', function () {
    $this->actingAs($this->student)->get(route('admin.system-health'))->assertForbidden();
    $this->get(route('admin.academic-sessions.review', $this->target))->assertForbidden();
    $this->post(route('admin.academic-sessions.activate', $this->target), [])->assertForbidden();
    $this->actingAs($this->admin)->post(route('admin.academic-sessions.activate', $this->target), [])->assertSessionHasErrors(['preview_token','confirmed']);
    expect($this->source->fresh()->is_active)->toBeTrue();
    $this->get(route('admin.academic-sessions.review', $this->target))->assertOk()->assertSee('No published First semester results');
});

it('promotes only explicitly approved students and records the decision once', function () {
    reliabilityResults($this);
    $this->actingAs($this->admin)->post(route('admin.academic-sessions.activate', $this->target), reliabilityActivation($this, $this->target, [$this->student->id]))->assertSessionHasNoErrors();
    expect($this->student->fresh()->level)->toBe('200')->and(DB::table('student_progressions')->count())->toBe(1);
    $this->assertDatabaseHas('student_progressions', ['approved_by'=>$this->admin->id,'from_level'=>'100','to_level'=>'200']);
    $this->post(route('admin.academic-sessions.activate', $this->source), reliabilityActivation($this, $this->source))->assertSessionHasNoErrors();
    $this->post(route('admin.academic-sessions.activate', $this->target), reliabilityActivation($this, $this->target, [$this->student->id]))->assertSessionHasErrors('student_ids');
    expect($this->student->fresh()->level)->toBe('200')->and(DB::table('student_progressions')->count())->toBe(1);
});

it('does not promote unselected students even when all checks pass', function () {
    reliabilityResults($this);
    $this->actingAs($this->admin)->post(route('admin.academic-sessions.activate', $this->target), reliabilityActivation($this, $this->target))->assertSessionHasNoErrors();
    expect($this->student->fresh()->level)->toBe('100')->and(DB::table('student_progressions')->count())->toBe(0);
});

it('rejects a stale enrollment preview without switching sessions', function () {
    reliabilityResults($this);
    $payload = reliabilityActivation($this, $this->target, [$this->student->id]);
    $this->student->update(['level'=>'200']);
    $this->actingAs($this->admin)->post(route('admin.academic-sessions.activate', $this->target), $payload)->assertSessionHasErrors('preview_token');
    expect($this->source->fresh()->is_active)->toBeTrue()->and(DB::table('student_progressions')->count())->toBe(0);
});

it('blocks promotion with failing results or unpublished records', function ($status, $points) {
    reliabilityResults($this, $status, $points);
    $this->actingAs($this->admin)->post(route('admin.academic-sessions.activate', $this->target), reliabilityActivation($this, $this->target, [$this->student->id]))->assertSessionHasErrors('student_ids');
    expect($this->student->fresh()->level)->toBe('100')->and($this->source->fresh()->is_active)->toBeTrue();
})->with([['published', 0], ['draft', 5]]);

it('prevents year jumps and final level promotions', function () {
    reliabilityResults($this);
    $this->target->update(['name'=>'2027/2028','start_year'=>2027,'end_year'=>2028]);
    expect(app(SessionProgression::class)->preview($this->target)['rows']->first()['eligible'])->toBeFalse();
    $this->target->update(['name'=>'2026/2027','start_year'=>2026,'end_year'=>2027]);
    $this->student->update(['level'=>'500']);
    expect(app(SessionProgression::class)->preview($this->target)['rows']->first()['eligible'])->toBeFalse();
});

it('shows missing enrollment records with correction links without guessing entry years', function () {
    $this->student->update(['entry_year'=>null]);
    expect(EnrollmentData::incompleteQuery($this->source)->count())->toBe(1);
    $this->actingAs($this->admin)->get(route('admin.system-health'))->assertOk()->assertSee($this->student->name)
        ->assertSee('Missing or invalid entry year')->assertSee(route('admin.students.edit', $this->student), false);
    expect($this->student->fresh()->entry_year)->toBeNull();
});

it('requires and persists enrollment years during student imports', function () {
    $base = ['name'=>'Imported Student','email'=>'import@example.com','level'=>'100','department_id'=>$this->department->id];
    $import = new StudentImport;
    $import->collection(collect([$base, array_replace($base, ['email'=>'complete@example.com','entry_year'=>2025])]));
    expect($import->createdCount())->toBe(1)->and($import->failedRows())->toHaveCount(1);
    $this->assertDatabaseHas('users', ['email'=>'complete@example.com','entry_year'=>2025]);
    $this->assertDatabaseMissing('users', ['email'=>'import@example.com']);
});

it('requires entry year in admin create and update requests', function () {
    $base = ['name'=>'Test Student','email'=>'new@example.com','level'=>'100','department_id'=>$this->department->id,'password'=>'Password123!','password_confirmation'=>'Password123!'];
    $this->actingAs($this->admin)->post(route('admin.students.store'), $base)->assertSessionHasErrors('entry_year');
    $this->put(route('admin.students.update', $this->student), array_replace($base, ['email'=>$this->student->email]))->assertSessionHasErrors('entry_year');
    $this->put(route('admin.students.update', $this->student), array_replace($base, ['email'=>$this->student->email,'entry_year'=>2024]))->assertSessionHasNoErrors();
    expect((int) $this->student->fresh()->entry_year)->toBe(2024);
});

it('distinguishes missing stale failed and synchronous service states', function () {
    config(['queue.default'=>'database']);
    expect(collect(OperationsHealth::rows())->keyBy('name')['scheduler']['status'])->toBe('not observed');
    OperationsHealth::record('scheduler', 'success');
    OperationsHealth::record('backup', 'failed', 'Backup failed.');
    $this->travel(6)->minutes();
    $rows = collect(OperationsHealth::rows())->keyBy('name');
    expect($rows['scheduler']['status'])->toBe('overdue')->and($rows['backup']['status'])->toBe('failed');
    config(['queue.default'=>'sync']);
    expect(collect(OperationsHealth::rows())->keyBy('name')['queue']['status'])->toBe('synchronous');
});

it('records an idle queue worker heartbeat', function () {
    config(['queue.default'=>'database']);
    Event::dispatch(new Looping('database', 'other-queue'));
    $this->assertDatabaseMissing('operation_health', ['name'=>'queue']);
    Event::dispatch(new Looping('database', 'default'));
    $this->assertDatabaseHas('operation_health', ['name'=>'queue','status'=>'success']);
});

it('flags graduation candidates instead of promoting them', function () {
    reliabilityResults($this);
    DB::table('results')->where('user_id', $this->student->id)->update(['policy_snapshot'=>json_encode([
        'repeat_rule'=>'all', 'graduation_credits'=>6, 'graduation_cgpa'=>4.0, 'required_courses'=>['REL-First','REL-Second'],
    ])]);
    $row = app(SessionProgression::class)->preview($this->target)['rows']->first();
    expect($row['standing'])->toBe('Graduation candidate')->and($row['eligible'])->toBeFalse();
});

it('keeps scheduled-backup success history unchanged during cleanup-only', function () {
    $connection = ['driver'=>'mysql','database'=>'backup_fixture'];
    config(['database.connections.backup_fixture'=>$connection]);
    OperationsHealth::record('backup', 'failed', 'Earlier backup failed.');
    $manager = Mockery::mock(DatabaseBackups::class);
    $manager->shouldReceive('scheduled')->once()->with($connection, Mockery::type('string'), 120, true)->andReturn(['path'=>null,'deleted'=>0]);
    $this->app->instance(DatabaseBackups::class, $manager);
    $this->artisan('backup:database', ['--connection'=>'backup_fixture', '--cleanup-only'=>true])->assertSuccessful();
    $this->assertDatabaseHas('operation_health', ['name'=>'backup','status'=>'failed','succeeded_at'=>null]);
});

it('records backup command success and failures through the shared service', function () {
    $connection = ['driver'=>'mysql','database'=>'backup_fixture'];
    config(['database.connections.backup_fixture'=>$connection]);
    $manager = Mockery::mock(DatabaseBackups::class);
    $manager->shouldReceive('scheduled')->once()->with($connection, Mockery::type('string'), 120, false)->andReturn(['path'=>'fixture.sql','deleted'=>0]);
    $this->app->instance(DatabaseBackups::class, $manager);
    $this->artisan('backup:database', ['--connection'=>'backup_fixture'])->assertSuccessful();
    $this->assertDatabaseHas('operation_health', ['name'=>'backup','status'=>'success']);
    $manager->shouldReceive('scheduled')->once()->andThrow(new RuntimeException('Simulated failure'));
    $this->artisan('backup:database', ['--connection'=>'backup_fixture'])->assertFailed();
    $this->assertDatabaseHas('operation_health', ['name'=>'backup','status'=>'failed']);
});
