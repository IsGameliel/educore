<?php

use App\Models\{AcademicSession, CourseRegistration, Courses, Department, Faculty, Result, User};
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\{Spreadsheet, Writer\Xlsx};

it('matches reversed worksheets to selected courses and rejects missing or duplicate matches', function () {
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['name' => 'Food Science', 'faculty_id' => $faculty->id]);
    $session = AcademicSession::create(['name' => '2021/2022', 'start_year' => 2021, 'end_year' => 2022, 'is_active' => true]);
    $admin = User::factory()->create(['usertype' => 'admin']);
    $student = User::factory()->create(['usertype' => 'student', 'matric_number' => 'MUI/SET/21/0004', 'department_id' => $department->id, 'level' => '100']);
    $courses = collect(['CHM 101', 'CHM 107'])->map(fn ($code) => Courses::create(['code' => $code, 'title' => $code, 'credit_unit' => 3, 'semester' => 'First', 'level' => '100', 'department_id' => $department->id, 'academic_session_id' => $session->id]));
    foreach ($courses as $course) {
        CourseRegistration::create(['user_id' => $student->id, 'course_id' => $course->id, 'session' => $session->name, 'semester' => 'First', 'status' => 'registered', 'registration_date' => now()]);
    }
    $makeFile = function ($codes) use ($student) {
        $book = new Spreadsheet();
        foreach ($codes as $i => $code) {
            $sheet = $i === 0 ? $book->getActiveSheet() : $book->createSheet();
            $sheet->setTitle('Sheet '.($i + 1));
            $sheet->fromArray([
                ['University result sheet'], ['School', 'Science'], ['Department', 'Food Science'], ['Level', '100'],
                ['Course code', $code], ['Course title', $code], ['Semester', 'First'],
                ['S/NO', 'MATRIC NO.', 'NAME', 'CA', 'EXAM', 'Total'],
                [1, $student->matric_number, $student->name, 20, $code === 'CHM 107' ? 40 : 50, null],
            ]);
        }
        $path = tempnam(sys_get_temp_dir(), 'result-matching-');
        try { (new Xlsx($book))->save($path); $bytes = file_get_contents($path); }
        finally { unlink($path); $book->disconnectWorksheets(); }
        return UploadedFile::fake()->createWithContent('results.xlsx', $bytes);
    };
    $data = ['department_id' => $department->id, 'session' => $session->name, 'semester' => 'First', 'course_ids' => $courses->pluck('id')->all()];
    $this->actingAs($admin)->post(route('admin.results.storeUpload'), $data + ['file' => $makeFile(['CHM 101', 'CHM 101'])])->assertSessionHasErrors('file');
    expect(Result::count())->toBe(0);
    $this->post(route('admin.results.storeUpload'), $data + ['file' => $makeFile(['CHM 101', 'PHY 101'])])->assertSessionHasErrors('file');
    expect(Result::count())->toBe(0);
    $this->post(route('admin.results.storeUpload'), $data + ['file' => $makeFile(['CHM 107', 'CHM 101'])])->assertRedirect()->assertSessionHas('success', '2 result(s) uploaded and 0 existing result(s) updated.');
    expect(Result::where('course_code', 'CHM 101')->sole()->score)->toEqual(70)
        ->and(Result::where('course_code', 'CHM 107')->sole()->score)->toEqual(60);
});
