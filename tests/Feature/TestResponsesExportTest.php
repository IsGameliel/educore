<?php

use App\Models\{AcademicSession, Courses, Department, Faculty, Responses, Tests, User};

beforeEach(function () {
    $session = AcademicSession::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Computing', 'faculty_id' => $faculty->id]);
    $this->course = Courses::create(['code' => 'CSC 101', 'title' => 'Computing', 'department_id' => $department->id, 'academic_session_id' => $session->id, 'credit_unit' => 3, 'semester' => 'First', 'level' => '100']);
    $this->lecturer = User::factory()->create(['usertype' => 'lecturer', 'department_id' => $department->id]);
    $this->lecturer->assignedCourses()->attach($this->course->id);
    $this->student = User::factory()->create(['usertype' => 'student', 'name' => '=1+1', 'matric_number' => '000123', 'department_id' => $department->id]);
    $this->testRecord = Tests::create(['name' => 'Quiz', 'subject' => 'Computing', 'duration' => 30, 'status' => 0, 'level' => '100', 'department_id' => $department->id]);
    Responses::create(['test_id' => $this->testRecord->id, 'student_id' => $this->student->id, 'answers' => [], 'score' => 0]);
});

it('exports the lecturers test responses with student identity course details and zero scores', function () {
    $this->actingAs($this->lecturer)->get(route('lecturer.tests.responses', $this->testRecord->id))->assertOk()->assertSee('Export responses (Excel)');
    $response = $this->get(route('lecturer.tests.responses', ['testId' => $this->testRecord->id, 'export' => 1]));
    $response->assertOk()->assertDownload('test-'.$this->testRecord->id.'-responses.xlsx');
    $path = $response->baseResponse->getFile()->getPathname();
    try {
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $book->getActiveSheet();
        expect($sheet->rangeToArray('A1:E1')[0])->toBe(['Name', 'Matric No', 'Course Code', 'Course Title', 'Score'])
            ->and($sheet->getCell('A2')->getValue())->toBe('=1+1')
            ->and($sheet->getCell('A2')->getDataType())->toBe('s')
            ->and($sheet->getCell('B2')->getValue())->toBe('000123')
            ->and($sheet->getCell('C2')->getValue())->toBe('CSC 101')
            ->and($sheet->getCell('D2')->getValue())->toBe('Computing')
            ->and((float) $sheet->getCell('E2')->getValue())->toBe(0.0)
            ->and($sheet->getHighestRow())->toBe(2);
        $book->disconnectWorksheets();
    } finally { @unlink($path); }
});

it('rejects unassigned lecturers and student export requests', function () {
    $url = route('lecturer.tests.responses', ['testId' => $this->testRecord->id, 'export' => 1]);
    $otherLecturer = User::factory()->create(['usertype' => 'lecturer']);
    $this->actingAs($otherLecturer)->get($url)->assertForbidden();
    $this->actingAs($this->student)->get($url)->assertForbidden();
});
