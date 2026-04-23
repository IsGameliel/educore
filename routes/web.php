<?php

use Illuminate\Support\Facades\Route;
use App\Models\Department;
use App\Http\Controllers\Admin\AdminCourseRegistrationController;
use App\Http\Controllers\{
    AdminController, StudentController, LecturerController, VcController, RegistrarController,
    BursarController, HomeController, CourseRegistrationController, CourseController,
    FacultyController, DepartmentController, ClassScheduleController, StudentScheduleController,
    CourseMaterialController, TestController, StudentManagementController, StaffManagementController,
    CustomProfileController, ResultController, AcademicSessionController, DashboardWidgetController
};

// Public Routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/pricing', function () {
    return view('pricing');
});

Route::get('/faq', function () {
    return view('faq');
});

Route::get('/resources', function () {
    return view('resources');
});

Route::get('/support', function () {
    return view('support');
});

Route::get('/request-demo', function () {
    return view('request-demo');
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
    Route::get('/documents/transcripts/{filename}', [ResultController::class, 'viewStoredTranscript'])
        ->where('filename', '.*')
        ->name('documents.transcripts.show');

    Route::get('/home', [HomeController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard/widgets')->name('dashboard.widgets.')->group(function () {
        Route::post('/todos', [DashboardWidgetController::class, 'storeTodo'])->name('todos.store');
        Route::patch('/todos/{todo}', [DashboardWidgetController::class, 'updateTodo'])->name('todos.update');
        Route::delete('/todos/{todo}', [DashboardWidgetController::class, 'destroyTodo'])->name('todos.destroy');
        Route::delete('/todos/completed/clear', [DashboardWidgetController::class, 'clearCompletedTodos'])->name('todos.clearCompleted');
        Route::post('/projects', [DashboardWidgetController::class, 'storeProject'])->name('projects.store');
        Route::patch('/projects/{project}', [DashboardWidgetController::class, 'updateProject'])->name('projects.update');
        Route::delete('/projects/{project}', [DashboardWidgetController::class, 'destroyProject'])->name('projects.destroy');
    });

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
                ->where(['userId' => '[0-9]+', 'session' => '.*'])
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
    Route::prefix('admin')->name('admin.')->middleware('usertype:admin')->group(function () {
        Route::get('/faculty/import', [FacultyController::class, 'ShowImportForm'])->name('faculties.import.form');
        Route::post('/faculty/import', [FacultyController::class, 'import'])->name('faculties.import');
        Route::get('/departments/import', [DepartmentController::class, 'showImportForm'])->name('departments.import.form');
        Route::post('/departments/import', [DepartmentController::class, 'import'])->name('departments.import');

        // pass mark configuration page
        Route::get('/departments/pass-marks', [DepartmentController::class, 'showPassMarks'])
            ->name('departments.passmarks');
        Route::post('/departments/pass-marks', [DepartmentController::class, 'updatePassMarks'])
            ->name('departments.passmarks.update');
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

        Route::get('/course-registrations', [AdminCourseRegistrationController::class, 'index'])
            ->name('course-registrations.index');

        Route::get('/course-registrations/{student}', [AdminCourseRegistrationController::class, 'show'])
            ->name('course-registrations.show');

        Route::get('/course-registrations/{student}/edit', [AdminCourseRegistrationController::class, 'edit'])
            ->name('course-registrations.edit');

        Route::put('/course-registrations/{student}', [AdminCourseRegistrationController::class, 'update'])
            ->name('course-registrations.update');

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

        Route::get('/students/import', [StudentManagementController::class, 'showImportForm'])
            ->name('students.import.form');
        Route::post('/students/import', [StudentManagementController::class, 'import'])
            ->name('students.import');

        Route::resources([
            '/students' => StudentManagementController::class,
            '/staffs' => StaffManagementController::class,
        ]);

        Route::prefix('academic-sessions')->name('academic-sessions.')->group(function () {
            Route::post('/', [AcademicSessionController::class, 'store'])->name('store');
            Route::put('/{academicSession}', [AcademicSessionController::class, 'update'])->name('update');
        });

        Route::prefix('results')->name('results.')->group(function () {
            Route::get('/', [ResultController::class, 'index'])->name('index');
            // put specific editing endpoints before the general show route
            Route::get('/edit-group/{user_id}/{session}/{semester}', [ResultController::class, 'editGroup'])
                ->where('session', '.*')
                ->name('editGroup');
            Route::put('/update-group/{user_id}/{session}/{semester}', [ResultController::class, 'updateGroup'])
                ->where('session', '.*')
                ->name('updateGroup');
            Route::delete('/group/{user_id}/{session}/{semester}', [ResultController::class, 'destroyGroup'])
                ->where('session', '.*')
                ->name('destroyGroup');

            Route::get('/{userId}/{session}/{semester}', [ResultController::class, 'show'])
                ->where(['userId' => '[0-9]+', 'session' => '.*'])
                ->name('show');
            Route::get('/export', [ResultController::class, 'export'])->name('export');
            Route::get('/template/download', [ResultController::class, 'downloadTemplate'])->name('template.download');

            Route::middleware('usertype:admin')->group(function () {
                Route::get('/create', [ResultController::class, 'create'])->name('create');
                Route::get('/get-students/{department_id}', [App\Http\Controllers\ResultController::class, 'getStudentsByDepartment']);
                Route::post('/migrate-department-results/{userId}', [ResultController::class, 'migrateDepartmentResults'])
                    ->name('migrateDepartmentResults');
                Route::post('/', [ResultController::class, 'store'])->name('store');
                Route::get('/{result}/edit', [ResultController::class, 'edit'])->name('edit');
                Route::put('/{result}', [ResultController::class, 'update'])->name('update');
                Route::delete('/{result}', [ResultController::class, 'destroy'])->name('destroy');
                Route::get('/upload', [ResultController::class, 'upload'])->name('upload');
                Route::post('/upload', [ResultController::class, 'storeUpload'])->name('storeUpload');

                // ✅ Single student transcript
                Route::post('/{userId}/{session}/{semester}/transcript', [ResultController::class, 'generateTranscriptForSemester'])
                    ->where('session', '.*')
                    ->name('transcript.generate');
                Route::post('/{userId}/full-transcript', [ResultController::class, 'generateFullTranscriptForStudent'])
                    ->name('transcript.full');

                // ✅ Bulk transcripts
                Route::post('/{session}/{semester}/transcripts', [ResultController::class, 'generateTranscriptsForAll'])
                    ->where('session', '.*')
                    ->name('transcripts.bulk');
            });
        });
    });

    Route::prefix('lecturer')->name('lecturer.')->middleware('usertype:lecturer')->group(function () {
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

        Route::prefix('results')->name('results.')->group(function () {
            Route::get('/', [ResultController::class, 'index'])->name('index');
            Route::get('/create', [ResultController::class, 'create'])->name('create');
            Route::post('/', [ResultController::class, 'store'])->name('store');
            Route::get('/upload', [ResultController::class, 'upload'])->name('upload');
            Route::post('/upload', [ResultController::class, 'storeUpload'])->name('storeUpload');
            Route::get('/template/download', [ResultController::class, 'downloadTemplate'])->name('template.download');
            Route::get('/get-students/{department_id}', [ResultController::class, 'getStudentsByDepartment'])->name('students');
            Route::get('/edit-group/{user_id}/{session}/{semester}', [ResultController::class, 'editGroup'])
                ->where('session', '.*')
                ->name('editGroup');
            Route::put('/update-group/{user_id}/{session}/{semester}', [ResultController::class, 'updateGroup'])
                ->where('session', '.*')
                ->name('updateGroup');
            Route::delete('/group/{user_id}/{session}/{semester}', [ResultController::class, 'destroyGroup'])
                ->where('session', '.*')
                ->name('destroyGroup');
            Route::get('/{result}/edit', [ResultController::class, 'edit'])->name('edit');
            Route::put('/{result}', [ResultController::class, 'update'])->name('update');
            Route::delete('/{result}', [ResultController::class, 'destroy'])->name('destroy');
            Route::get('/{userId}/{session}/{semester}', [ResultController::class, 'show'])
                ->where(['userId' => '[0-9]+', 'session' => '.*'])
                ->name('show');
        });
    });
});
