<?php

use Illuminate\Support\Facades\Route;
use App\Models\Department;
use App\Http\Controllers\{
    AdminController, StudentController, LecturerController, VcController, RegistrarController,
    BursarController, HomeController, CourseRegistrationController, CourseController,
    FacultyController, DepartmentController, ClassScheduleController, StudentScheduleController,
    CourseMaterialController, TestController, StudentManagementController, StaffManagementController,
    CustomProfileController, ResultController
};

// Public Routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', function () {
    $departments = Department::orderBy('name')->get(['id', 'name']);
    return view('auth.register', compact('departments'));
})->middleware(['guest'])->name('register');


// Authenticated Routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('dashboard');

    // -------------------------
    // STUDENT ROUTES
    // -------------------------
    Route::prefix('student')->name('student.')->group(function () {
        Route::prefix('courses')->name('courses.')->group(function () {
            Route::get('/registration', [CourseRegistrationController::class, 'showRegistrationForm'])->name('registration');
            Route::post('/register', [CourseRegistrationController::class, 'registerForCourses'])->name('register');
            Route::get('/by-level', [CourseRegistrationController::class, 'getCoursesByLevel'])->name('byLevel');
            Route::get('/{semester}', [CourseRegistrationController::class, 'getRegisteredCourses'])->name('registered');
            Route::post('/withdraw', [CourseRegistrationController::class, 'withdrawFromCourse'])->name('withdraw');
            Route::post('/queue', [CourseRegistrationController::class, 'addCourseToQueue'])->name('queue');
            Route::get('/download/pdf', [CourseRegistrationController::class, 'downloadCoursesPDF'])->name('download.pdf');
            Route::get('/download/excel', [CourseRegistrationController::class, 'downloadCoursesExcel'])->name('download.excel');

        });

        Route::get('/user/profile', [CustomProfileController::class, 'show'])->name('profile.show');
        Route::get('schedule', [StudentScheduleController::class, 'index'])->name('schedule');
        Route::get('/course-materials', [StudentController::class, 'CourseMaterial'])->name('course-materials');

        Route::prefix('tests')->name('tests.')->middleware('prevent.retake')->group(function () {
            Route::get('/', [TestController::class, 'index'])->name('index');
            Route::get('/{testId}/{questionIndex?}', [TestController::class, 'startTest'])->name('start');
            Route::post('/{testId}/submit', [TestController::class, 'submitTest'])->name('submit');
            Route::post('/{testId}/{questionIndex?}', [TestController::class, 'storeAnswer'])->name('storeAnswer');
        });

        Route::prefix('results')->name('results.')->group(function () {
            Route::get('/', [ResultController::class, 'index'])->name('index');
            Route::get('/{userId}/{session}/{semester}', [ResultController::class, 'show'])
                ->where('session', '.*')
                ->name('show');

            // ✅ Student transcript route
            Route::get('/{userId}/{session}/{semester}/transcript', [ResultController::class, 'generateTranscriptForSemester'])
                ->where('session', '.*')
                ->name('transcript');
        });
    });

    // -------------------------
    // ADMIN ROUTES
    // -------------------------
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/faculty/import', [FacultyController::class, 'ShowImportForm'])->name('faculties.import.form');
        Route::post('/faculty/import', [FacultyController::class, 'import'])->name('faculties.import');
        Route::get('/departments/import', [DepartmentController::class, 'showImportForm'])->name('departments.import.form');
        Route::post('/departments/import', [DepartmentController::class, 'import'])->name('departments.import');
        Route::get('/courses/import', [CourseController::class, 'showImportForm'])->name('courses.import.form');
        Route::post('/courses/import', [CourseController::class, 'import'])->name('courses.import');
        Route::resource('courses', CourseController::class);
        Route::get('courses/{course}/prerequisites', [CourseController::class, 'showPrerequisites'])->name('courses.prerequisites');
        Route::post('courses/{course}/prerequisites', [CourseController::class, 'assignPrerequisites'])->name('courses.assignPrerequisites');
        Route::resources([
            'faculties' => FacultyController::class,
            'departments' => DepartmentController::class,
            'class-schedules' => ClassScheduleController::class,
        ]);

        Route::prefix('course-materials')->name('course-materials.')->group(function () {
            Route::get('/', [CourseMaterialController::class, 'index'])->name('index');
            Route::get('/create', [CourseMaterialController::class, 'create'])->name('create');
            Route::post('/', [CourseMaterialController::class, 'store'])->name('store');
            Route::get('/{id}', [CourseMaterialController::class, 'show']);
            Route::get('/{id}/download', [CourseMaterialController::class, 'download'])->name('download');
            Route::delete('/{id}', [CourseMaterialController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/edit', [CourseMaterialController::class, 'edit'])->name('edit');
            Route::put('/{id}', [CourseMaterialController::class, 'update'])->name('update');
        });

        Route::prefix('tests')->name('tests.')->group(function () {
            Route::get('/', [TestController::class, 'adminIndex'])->name('index');
            Route::get('/create', [TestController::class, 'create'])->name('create');
            Route::post('/', [TestController::class, 'store'])->name('store');
            Route::get('/{testId}/edit', [TestController::class, 'edit'])->name('edit');
            Route::put('/{testId}', [TestController::class, 'update'])->name('update');
            Route::get('/{testId}/questions', [TestController::class, 'manageQuestions'])->name('questions');
            Route::post('/{testId}/questions', [TestController::class, 'storeQuestions'])->name('questions.store');
            Route::get('/{testId}/questions/{questionId}/edit', [TestController::class, 'editQuestion'])->name('questions.edit');
            Route::put('/{testId}/questions/{questionId}', [TestController::class, 'updateQuestion'])->name('questions.update');
            Route::get('/{testId}/responses', [TestController::class, 'viewResponses'])->name('responses');
            Route::delete('/{testId}/questions/{questionId}', [TestController::class, 'deleteQuestion'])->name('questions.delete');
        });

        Route::resources([
            '/students' => StudentManagementController::class,
            '/staffs' => StaffManagementController::class,
        ]);

        Route::prefix('results')->name('results.')->group(function () {
            Route::get('/', [ResultController::class, 'index'])->name('index');
            Route::get('/{userId}/{session}/{semester}', [ResultController::class, 'show'])->name('show');
            Route::get('/export', [ResultController::class, 'export'])->name('export');

            Route::get('/edit-group/{user_id}/{session}/{semester}', [ResultController::class, 'editGroup'])->name('editGroup');
            Route::put('/update-group/{user_id}/{session}/{semester}', [ResultController::class, 'updateGroup'])->name('updateGroup');

            Route::middleware('usertype:admin,lecturer')->group(function () {
                Route::get('/create', [ResultController::class, 'create'])->name('create');
                Route::get('/get-students/{department_id}', [App\Http\Controllers\ResultController::class, 'getStudentsByDepartment']);
                Route::post('/', [ResultController::class, 'store'])->name('store');
                Route::get('/{result}/edit', [ResultController::class, 'edit'])->name('edit');
                Route::put('/{result}', [ResultController::class, 'update'])->name('update');
                Route::get('/upload', [ResultController::class, 'upload'])->name('upload');
                Route::post('/upload', [ResultController::class, 'storeUpload'])->name('storeUpload');

                // ✅ Single student transcript
                Route::post('/{userId}/{session}/{semester}/transcript', [ResultController::class, 'generateTranscriptForSemester'])
                    ->where('session', '.*')
                    ->name('transcript.generate');

                // ✅ Bulk transcripts
                Route::post('/{session}/{semester}/transcripts', [ResultController::class, 'generateTranscriptsForAll'])
                    ->where('session', '.*')
                    ->name('transcripts.bulk');
            });
        });
    });
});
