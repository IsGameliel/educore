<?php

use App\Models\User;
use App\Services\StudentAccountMerge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->admin = User::factory()->create(['usertype' => 'admin']);
    $this->retained = User::factory()->create(['usertype' => 'student', 'name' => 'ABHULIMEN TESTIMONY EJEHI', 'level' => '100']);
    $this->duplicate = User::factory()->create(['usertype' => 'student', 'name' => 'ABHULIMEN TESTIMONY EJEHI', 'level' => '200', 'matric_number' => 'MERGE-123']);
    $this->ids = [$this->retained->id, $this->duplicate->id];
    $this->service = app(StudentAccountMerge::class);
    $this->withoutVite();
});

it('suggests duplicate names and supports email and matric searches', function () {
    $this->actingAs($this->admin)->get(route('admin.students.merge.index'))->assertOk()
        ->assertSee($this->retained->email)->assertSee($this->duplicate->email);
    $this->get(route('admin.students.merge.index', ['search' => 'MERGE-123']))->assertOk()->assertSee($this->duplicate->email);
    $this->get(route('admin.students.merge.preview', ['accounts' => $this->ids, 'retained_id' => $this->retained->id]))
        ->assertOk()->assertSee('Preview account merge')->assertSee('Confirm merge');
});

it('moves records, preserves financial references, archives accounts and revokes access', function () {
    DB::table('dashboard_todos')->insert(['user_id' => $this->duplicate->id, 'title' => 'Keep this task', 'completed' => false, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('payments')->insert([
        'user_id' => $this->duplicate->id, 'payable_type' => 'fixture', 'payable_id' => 1,
        'purpose' => 'admission', 'reference' => 'PRESERVE-PAYMENT', 'email' => $this->duplicate->email,
        'amount' => 150000, 'currency' => 'NGN', 'status' => 'paid', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $token = $this->duplicate->createToken('old-login');
    DB::table('sessions')->insert(['id' => 'duplicate-session', 'user_id' => $this->duplicate->id, 'payload' => '', 'last_activity' => time()]);
    $password = $this->retained->password;
    $preview = $this->service->preview($this->ids, $this->retained->id);
    $this->service->merge($this->ids, $this->retained->id, ['matric_number' => $this->duplicate->id, 'level' => $this->duplicate->id], 'Verified student identity and admission documents.', $this->admin->id, $preview['digest']);
    $this->assertDatabaseHas('dashboard_todos', ['user_id' => $this->retained->id, 'title' => 'Keep this task']);
    $this->assertDatabaseHas('payments', ['user_id' => $this->retained->id, 'reference' => 'PRESERVE-PAYMENT', 'amount' => 150000, 'email' => $this->duplicate->email]);
    $this->assertDatabaseHas('users', ['id' => $this->duplicate->id, 'merged_into_id' => $this->retained->id, 'matric_number' => null]);
    expect(User::find($this->duplicate->id))->toBeNull();
    expect($this->retained->fresh()->password)->toBe($password);
    expect($this->retained->fresh()->matric_number)->toBe('MERGE-123');
    $this->assertDatabaseMissing('sessions', ['id' => 'duplicate-session']);
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    $audit = DB::table('student_account_merges')->first();
    expect($audit->performed_by)->toBe($this->admin->id);
    expect($audit->account_snapshots)->not->toContain($password);
});

it('requires a server-issued preview and identity confirmation for dashboard merges', function () {
    $this->actingAs($this->admin)->get(route('admin.students.merge.preview', ['accounts' => $this->ids, 'retained_id' => $this->retained->id]))->assertOk();
    $saved = session('student_merge_preview');
    $profile = array_fill_keys(StudentAccountMerge::PROFILE_FIELDS, $this->retained->id);
    $this->post(route('admin.students.merge.store'), ['preview_token' => $saved['token'], 'profile' => $profile, 'reason' => 'Identity verified with the student.'])
        ->assertSessionHasErrors('confirm_identity');
    $this->post(route('admin.students.merge.store'), ['preview_token' => $saved['token'], 'profile' => $profile, 'reason' => 'Identity verified with the student.', 'confirm_identity' => '1'])
        ->assertRedirect(route('admin.students.merge.index'))->assertSessionHas('success');
    $this->assertDatabaseCount('student_account_merges', 1);
    $this->post(route('admin.students.merge.store'), ['preview_token' => $saved['token'], 'profile' => $profile, 'reason' => 'Identity verified with the student.', 'confirm_identity' => '1'])
        ->assertSessionHasErrors('merge');
    $this->assertDatabaseCount('student_account_merges', 1);
});

it('blocks conflicting credit limits without changing any account', function () {
    foreach ([$this->retained->id => 24, $this->duplicate->id => 30] as $id => $limit) {
        DB::table('student_credit_limits')->insert(['user_id' => $id, 'session' => '2026/2027', 'semester' => 'First', 'credit_limit' => $limit, 'created_at' => now(), 'updated_at' => now()]);
    }
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['conflicts'])->toHaveCount(1);
    expect(fn () => $this->service->merge($this->ids, $this->retained->id, [], 'Verified identity before merging.', $this->admin->id, $preview['digest']))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('student_credit_limits', 2);
    $this->assertDatabaseCount('student_account_merges', 0);
    expect($this->duplicate->fresh()->merged_into_id)->toBeNull();
});

it('consolidates identical limits and retains their audit archive', function () {
    foreach ($this->ids as $id) {
        DB::table('student_credit_limits')->insert(['user_id' => $id, 'session' => '2026/2027', 'semester' => 'First', 'credit_limit' => 24, 'created_at' => now(), 'updated_at' => now()]);
    }
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['conflicts'])->toBeEmpty();
    $this->service->merge($this->ids, $this->retained->id, [], 'Verified identity before merging.', $this->admin->id, $preview['digest']);
    $this->assertDatabaseCount('student_credit_limits', 1);
    expect(json_decode(DB::table('student_account_merges')->value('record_changes'), true)['deduplicated'])->toHaveCount(1);
});

it('rejects stale previews and nonstudent or previously merged accounts', function () {
    $preview = $this->service->preview($this->ids, $this->retained->id);
    $this->duplicate->update(['level' => '300']);
    expect(fn () => $this->service->merge($this->ids, $this->retained->id, [], 'Verified identity before merging.', $this->admin->id, $preview['digest']))->toThrow(ValidationException::class);
    expect(fn () => $this->service->preview([$this->retained->id, $this->admin->id], $this->retained->id))->toThrow(ValidationException::class);
    expect(fn () => $this->service->preview($this->ids, $this->admin->id))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('student_account_merges', 0);
});

it('denies nonadmins all merge endpoints and the service', function () {
    $this->actingAs($this->retained)->get(route('admin.students.merge.index'))->assertForbidden();
    $this->get(route('admin.students.merge.preview', ['accounts' => $this->ids, 'retained_id' => $this->retained->id]))->assertForbidden();
    $this->post(route('admin.students.merge.store'))->assertForbidden();
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect(fn () => $this->service->merge($this->ids, $this->retained->id, [], 'Verified identity before merging.', $this->retained->id, $preview['digest']))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('prevents archived accounts from logging in with their old password', function () {
    $this->duplicate->forceFill(['password' => Hash::make('known-password')])->save();
    $preview = $this->service->preview($this->ids, $this->retained->id);
    $this->service->merge($this->ids, $this->retained->id, [], 'Verified identity before merging.', $this->admin->id, $preview['digest']);
    $this->post('/login', ['email' => $this->duplicate->email, 'password' => 'known-password'])->assertSessionHasErrors();
    $this->assertGuest();
});

it('preserves published result IDs, scores, registration links and document paths', function () {
    $faculty = \App\Models\Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = \App\Models\Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);
    $resultId = DB::table('results')->insertGetId([
        'user_id' => $this->duplicate->id, 'department_id' => $department->id,
        'matric_number' => 'MERGE-123', 'session' => '2026/2027', 'semester' => 'First', 'level' => '200',
        'course_code' => 'CSC201', 'course_title' => 'Algorithms', 'credit_unit' => 3,
        'ca_score' => 25, 'exam_score' => 50, 'score' => 75, 'grade' => 'A', 'grade_point' => 5,
        'workflow_status' => 'published', 'attempt_type' => 'regular', 'version' => 2,
        'transcript_path' => 'transcripts/existing.pdf', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $before = (array) DB::table('results')->find($resultId);
    $preview = $this->service->preview($this->ids, $this->retained->id);
    $this->service->merge($this->ids, $this->retained->id, [], 'Student identity verified against admission.', $this->admin->id, $preview['digest']);
    $after = (array) DB::table('results')->find($resultId);
    $before['user_id'] = $this->retained->id;
    expect($after)->toBe($before);
});

it('blocks overlapping results even without a database unique constraint', function () {
    $faculty = \App\Models\Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = \App\Models\Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);
    foreach ($this->ids as $id) {
        DB::table('results')->insert([
            'user_id' => $id, 'department_id' => $department->id, 'matric_number' => 'MERGE-123',
            'session' => '2026/2027', 'semester' => 'First', 'level' => '200',
            'course_code' => 'CSC201', 'course_title' => 'Algorithms', 'credit_unit' => 3, 'score' => 75,
            'workflow_status' => 'published', 'attempt_type' => 'regular', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['conflicts'][0]['table'])->toBe('results');
    $this->actingAs($this->admin)->get(route('admin.students.merge.preview', ['accounts' => $this->ids, 'retained_id' => $this->retained->id]))
        ->assertOk()->assertSee('Records need review before merging')->assertSee('disabled', false);
});

it('rolls back all transfers if writing the merge audit fails', function () {
    DB::table('dashboard_todos')->insert(['user_id' => $this->duplicate->id, 'title' => 'Preserve on failure', 'completed' => false]);
    DB::unprepared("CREATE TRIGGER reject_merge_audit BEFORE INSERT ON student_account_merges BEGIN SELECT RAISE(ABORT, 'Simulated audit failure'); END");
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect(fn () => $this->service->merge($this->ids, $this->retained->id, ['matric_number' => $this->duplicate->id], 'Student identity verified against admission.', $this->admin->id, $preview['digest']))
        ->toThrow(\Illuminate\Database\QueryException::class);
    $this->assertDatabaseHas('dashboard_todos', ['user_id' => $this->duplicate->id]);
    $this->assertDatabaseHas('users', ['id' => $this->duplicate->id, 'merged_into_id' => null, 'matric_number' => 'MERGE-123']);
    $this->assertDatabaseHas('users', ['id' => $this->retained->id, 'matric_number' => null]);
    $this->assertDatabaseCount('student_account_merges', 0);
});

it('blocks annual and semester double billing while preserving cancelled invoice and payment history', function () {
    $faculty = \App\Models\Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = \App\Models\Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);
    $session = \App\Models\AcademicSession::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => false]);
    $invoices = [];
    foreach ([$this->retained->id => 'Annual', $this->duplicate->id => 'First'] as $id => $period) {
        $schedule = \App\Models\TuitionSchedule::create([
            'academic_session_id' => $session->id, 'department_id' => $department->id, 'level' => '100',
            'category' => 'new', 'period' => $period, 'items' => [['label' => 'Tuition', 'amount' => 100000]],
            'amount' => 100000, 'first_percent' => 100, 'due_date' => '2026-11-01', 'status' => 'published', 'created_by' => $this->admin->id,
        ]);
        $invoices[$id] = \App\Models\TuitionInvoice::create([
            'number' => 'MERGE-INV-'.$id, 'user_id' => $id, 'academic_session_id' => $session->id,
            'tuition_schedule_id' => $schedule->id, 'department_id' => $department->id,
            'student_name' => 'ABHULIMEN TESTIMONY EJEHI', 'department_name' => 'Computer Science', 'session_name' => '2026/2027',
            'level' => '100', 'category' => 'new', 'period' => $period, 'items' => $schedule->items,
            'amount' => 100000, 'first_percent' => 100, 'due_date' => '2026-11-01',
        ]);
    }
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['conflicts'])->not->toBeEmpty();
    expect(fn () => $this->service->merge($this->ids, $this->retained->id, [], 'Student identity verified against admission.', $this->admin->id, $preview['digest']))->toThrow(ValidationException::class);
    $invoice = $invoices[$this->duplicate->id];
    $invoice->update(['active_slot' => null, 'cancelled_at' => now()]);
    $charge = \App\Models\TuitionCharge::create(['tuition_invoice_id' => $invoice->id, 'amount' => 100000]);
    $payment = \App\Models\Payment::create([
        'user_id' => $this->duplicate->id, 'tuition_invoice_id' => $invoice->id,
        'payable_type' => $charge->getMorphClass(), 'payable_id' => $charge->id,
        'purpose' => 'tuition', 'reference' => 'LINKED-TUITION-PAYMENT', 'email' => $this->duplicate->email,
        'amount' => 100000, 'currency' => 'NGN', 'status' => 'success',
    ]);
    $before = (array) DB::table('tuition_invoices')->find($invoice->id);
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['conflicts'])->toBeEmpty();
    $this->service->merge($this->ids, $this->retained->id, [], 'Student identity verified against admission.', $this->admin->id, $preview['digest']);
    $before['user_id'] = $this->retained->id;
    expect((array) DB::table('tuition_invoices')->find($invoice->id))->toBe($before);
    expect($payment->fresh()->user_id)->toBe($this->retained->id);
    expect($payment->fresh()->tuition_invoice_id)->toBe($invoice->id);
    expect($payment->fresh()->payable_id)->toBe($charge->id);
    expect($charge->fresh()->tuition_invoice_id)->toBe($invoice->id);
    $this->assertDatabaseCount('tuition_invoices', 2);
});

function matchingMergeRegistrations($test, array $overrides = []): array
{
    $courseId = DB::table('courses')->insertGetId(['code' => 'MERGE201', 'title' => 'Merge test course', 'credit_unit' => 3, 'semester' => 'Second', 'level' => '200']);
    $ids = [];
    foreach ($test->ids as $userId) {
        $ids[] = DB::table('course_registrations')->insertGetId(array_replace([
            'user_id' => $userId, 'course_id' => $courseId, 'status' => 'pending', 'session' => null,
            'semester' => 'Second', 'created_at' => now(), 'updated_at' => now(),
        ], $userId === $test->duplicate->id ? $overrides : []));
    }
    return $ids;
}

it('consolidates matching pending registrations with a missing session and previews retained IDs', function () {
    [$keep, $remove] = matchingMergeRegistrations($this);
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['conflicts'])->toBeEmpty();
    expect($preview['duplicates'][0]['table'])->toBe('course_registrations');
    $this->actingAs($this->admin)->get(route('admin.students.merge.preview', ['accounts' => $this->ids, 'retained_id' => $this->retained->id]))
        ->assertOk()->assertSee('Duplicates ready to consolidate')->assertSee('keep record #'.$keep);
    $this->service->merge($this->ids, $this->retained->id, [], 'Verified duplicate student registrations.', $this->admin->id, $preview['digest']);
    $this->assertDatabaseCount('course_registrations', 1);
    $this->assertDatabaseHas('course_registrations', ['id' => $keep, 'user_id' => $this->retained->id]);
    $audit = json_decode(DB::table('student_account_merges')->value('record_changes'), true);
    expect($audit['deduplicated'][0]['archived_records'][0]['id'])->toBe($remove);
});

it('retargets published and deleted result history before consolidating registrations', function () {
    [$keep, $remove] = matchingMergeRegistrations($this, []);
    DB::table('course_registrations')->whereIn('id', [$keep, $remove])->update(['status' => 'approved']);
    $faculty = \App\Models\Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = \App\Models\Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $resultIds = [];
    foreach ([null, now()->toDateTimeString()] as $deletedAt) {
        $resultIds[] = DB::table('results')->insertGetId([
            'user_id' => $this->duplicate->id, 'course_registration_id' => $remove, 'department_id' => $department->id,
            'matric_number' => 'MERGE-123', 'session' => '2026/2027', 'semester' => 'Second', 'level' => '200',
            'course_code' => 'MERGE201', 'course_title' => 'Merge test course', 'credit_unit' => 3,
            'score' => 75, 'workflow_status' => 'published', 'version' => 2, 'deleted_at' => $deletedAt,
        ]);
    }
    $before = DB::table('results')->whereIn('id', $resultIds)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['conflicts'])->toBeEmpty();
    $this->service->merge($this->ids, $this->retained->id, [], 'Verified duplicate student registrations.', $this->admin->id, $preview['digest']);
    foreach ($before as $row) {
        $row['user_id'] = $this->retained->id;
        $row['course_registration_id'] = $keep;
        expect((array) DB::table('results')->find($row['id']))->toBe($row);
    }
    $audit = json_decode(DB::table('student_account_merges')->value('record_changes'), true);
    expect($audit['reference_updates'])->toHaveCount(2);
    expect($audit['reference_updates'][0]['from'])->toBe($remove);
    expect($audit['reference_updates'][0]['to'])->toBe($keep);
});

it('keeps blocking registrations with differing status or metadata', function (array $overrides) {
    matchingMergeRegistrations($this, $overrides);
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['conflicts'])->toHaveCount(1);
    expect($preview['duplicates'])->toBeEmpty();
    expect(fn () => $this->service->merge($this->ids, $this->retained->id, [], 'Verified duplicate student registrations.', $this->admin->id, $preview['digest']))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('course_registrations', 2);
})->with([
    [['status' => 'approved']],
]);

it('rolls back registration consolidation when the audit cannot be saved', function () {
    [$keep, $remove] = matchingMergeRegistrations($this);
    DB::unprepared("CREATE TRIGGER reject_registration_merge BEFORE INSERT ON student_account_merges BEGIN SELECT RAISE(ABORT, 'Simulated failure'); END");
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect(fn () => $this->service->merge($this->ids, $this->retained->id, [], 'Verified duplicate student registrations.', $this->admin->id, $preview['digest']))->toThrow(\Illuminate\Database\QueryException::class);
    $this->assertDatabaseHas('course_registrations', ['id' => $keep, 'user_id' => $this->retained->id]);
    $this->assertDatabaseHas('course_registrations', ['id' => $remove, 'user_id' => $this->duplicate->id]);
});

it('scans schema metadata once per preview regardless of the number of duplicate registrations', function () {
    [$keep, $remove] = matchingMergeRegistrations($this);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $this->service->preview($this->ids, $this->retained->id);
    $metadataCount = fn () => collect(DB::getQueryLog())->filter(fn ($query) => preg_match('/pragma|sqlite_master|information_schema/i', $query['query']))->count();
    $firstCount = $metadataCount();
    $courseId = DB::table('course_registrations')->where('id', $keep)->value('course_id');
    foreach (range(1, 9) as $session) {
        foreach ($this->ids as $id) {
            DB::table('course_registrations')->insert(['user_id' => $id, 'course_id' => $courseId, 'status' => 'pending', 'semester' => 'Second', 'session' => '2026/'.str_pad((string) $session, 4, '0', STR_PAD_LEFT)]);
        }
    }
    DB::flushQueryLog();
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['duplicates'])->toHaveCount(10);
    expect($firstCount)->toBeGreaterThan(0);
    expect($metadataCount())->toBe($firstCount);
    DB::disableQueryLog();
});

it('refreshes metadata on the next preview and blocks new unsupported registration references', function () {
    [$keep, $remove] = matchingMergeRegistrations($this);
    expect($this->service->preview($this->ids, $this->retained->id)['conflicts'])->toBeEmpty();
    \Illuminate\Support\Facades\Schema::create('registration_notes', function ($table) {
        $table->id();
        $table->foreignId('course_registration_id')->constrained('course_registrations');
    });
    try {
        $preview = $this->service->preview($this->ids, $this->retained->id);
        expect($preview['duplicates'])->toBeEmpty();
        expect($preview['conflicts'])->toHaveCount(1);
    } finally {
        \Illuminate\Support\Facades\Schema::dropIfExists('registration_notes');
    }
});

it('consolidates matching enrollments with different actors and dates while preserving provenance', function () {
    [$keep, $remove] = matchingMergeRegistrations($this, ['acted_by' => $this->duplicate->id, 'registration_date' => '2026-07-06']);
    DB::table('course_registrations')->where('id', $keep)->update(['acted_by' => $this->retained->id, 'registration_date' => '2026-06-01']);
    $original = (array) DB::table('course_registrations')->find($remove);
    $keptOriginal = (array) DB::table('course_registrations')->find($keep);
    $preview = $this->service->preview($this->ids, $this->retained->id);
    expect($preview['conflicts'])->toBeEmpty();
    expect($preview['duplicates'])->toHaveCount(1);
    $this->service->merge($this->ids, $this->retained->id, [], 'Verified identical enrollment across duplicate accounts.', $this->admin->id, $preview['digest']);
    expect((array) DB::table('course_registrations')->find($keep))->toBe($keptOriginal);
    $audit = json_decode(DB::table('student_account_merges')->value('record_changes'), true);
    expect($audit['deduplicated'][0]['archived_records'][0])->toBe($original);
    $this->assertDatabaseCount('course_registrations', 1);
});
