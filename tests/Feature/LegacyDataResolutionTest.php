<?php

use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use App\Services\Tenancy\LegacyDataResolution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->resolutionRoot = storage_path('framework/testing/resolution-'.Str::uuid());
    File::ensureDirectoryExists($this->resolutionRoot.'/private');
    File::ensureDirectoryExists($this->resolutionRoot.'/public');
    config(['filesystems.disks.local.root' => $this->resolutionRoot.'/private', 'filesystems.disks.public.root' => $this->resolutionRoot.'/public']);
    Schema::table('users', fn ($t) => $t->dropUnique('users_matric_number_unique'));
    $this->actor = User::factory()->create(['usertype' => 'admin']);
    $f = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $this->dept = Department::create(['name' => 'Computing', 'faculty_id' => $f->id]);
    $this->first = User::factory()->create(['usertype' => 'student', 'name' => 'Ada Student', 'department_id' => $this->dept->id, 'entry_year' => '2023', 'matric_number' => 'SAME', 'profile_photo_path' => 'profile_photos/absent.jpg']);
    $this->second = User::factory()->create(['usertype' => 'student', 'name' => 'Student Ada', 'department_id' => $this->dept->id, 'entry_year' => '2023', 'matric_number' => 'SAME']);
    $this->archived = User::factory()->create(['usertype' => 'student', 'name' => 'Ada Student', 'matric_number' => 'SAME']);
    DB::table('users')->where('id', $this->archived->id)->update(['merged_into_id' => $this->first->id]);
    DB::table('teams')->insert(['user_id' => 999999, 'name' => 'Orphan personal team', 'personal_team' => true]);
    $schedule = DB::table('class_schedules')->insertGetId(['department_id' => $this->dept->id, 'level' => '100', 'semester' => 'Second', 'subject' => '999999', 'lecturer_id' => $this->actor->id, 'day' => 'Monday', 'start_time' => '09:00', 'end_time' => '10:00', 'room' => 'A']);
    $session = DB::table('attendance_sessions')->insertGetId(['class_schedule_id' => $schedule, 'attendance_date' => '2026-05-29', 'taken_by' => $this->actor->id]);
    DB::table('attendance_records')->insert(['attendance_session_id' => $session, 'student_id' => $this->second->id, 'status' => 'present']);
    $this->resolution = app(LegacyDataResolution::class);
});
afterEach(function () {
    File::deleteDirectory($this->resolutionRoot);
});
it('resolves verified legacy duplicates and archives invalid schedules attendance and file references', function () {
    $password = $this->first->password;
    $plan = $this->resolution->plan();
    $archive = $this->resolutionRoot.'/resolution.json';
    expect(count($plan['merges']))->toBe(1)->and(count($plan['missing_document_references']))->toBe(1);
    $result = $this->resolution->apply($plan, $archive, $this->actor->id);
    expect(DB::table('teams')->where('user_id', 999999)->exists())->toBeFalse()->and(DB::table('class_schedules')->count())->toBe(0)->and(DB::table('attendance_records')->count())->toBe(0);
    expect(DB::table('users')->whereNotNull('matric_number')->where('matric_number', 'SAME')->count())->toBe(1);
    expect(DB::table('users')->where('id', $this->first->id)->value('password'))->toBe($password)->and(DB::table('users')->where('id', $this->first->id)->value('profile_photo_path'))->toBeNull();
    $saved = json_decode(File::get($archive), true);
    expect($saved['plan']['archived_rows']['attendance_records'])->toHaveCount(1)->and($saved['plan']['missing_document_references'][0]['path'])->toBe('profile_photos/absent.jpg');
    expect($result['merged_groups'])->toBe(1)->and(DB::table('activity_logs')->where('action', 'legacy_data_resolved')->count())->toBe(1);
    $again = $this->resolution->plan();
    expect($again['merges'])->toBeEmpty()->and($again['missing_document_references'])->toBeEmpty();
});
it('refuses changed plans and different identities without changing source records', function () {
    $plan = $this->resolution->plan();
    DB::table('users')->where('id', $this->second->id)->update(['name' => 'Different person']);
    expect(fn () => $this->resolution->apply($plan, $this->resolutionRoot.'/denied.json', $this->actor->id))->toThrow(RuntimeException::class);
    expect(DB::table('teams')->where('user_id', 999999)->exists())->toBeTrue()->and(DB::table('class_schedules')->count())->toBe(1);
});
it('rolls back an unauthorized resolution and retains original records', function () {
    $plan = $this->resolution->plan();
    expect(fn () => $this->resolution->apply($plan, $this->resolutionRoot.'/denied.json', $this->first->id))->toThrow(RuntimeException::class);
    expect(DB::table('class_schedules')->count())->toBe(1)->and(DB::table('users')->where('matric_number', 'SAME')->count())->toBe(3);
});


it('refuses to reconcile a shared platform database',function(){
    Schema::table('users',fn($table)=>$table->string('tenant_id')->nullable());
    expect(fn()=>$this->resolution->plan())->toThrow(RuntimeException::class,'shared platform');
    expect(DB::table('class_schedules')->count())->toBe(1);
});
