<?php

use Illuminate\Support\Facades\Route;
use App\Models\Department;
use App\Http\Controllers\Admin\AdminCourseRegistrationController;
use App\Http\Controllers\{
    AdminController, StudentController, LecturerController, VcController, RegistrarController,
    BursarController, HomeController, CourseRegistrationController, CourseController,
    FacultyController, DepartmentController, ClassScheduleController, StudentScheduleController,
    CourseMaterialController, TestController, StudentManagementController, StaffManagementController,
    CustomProfileController, ResultController, AcademicSessionController, DashboardWidgetController,
    AttendanceController, AdmissionController
};

use App\Http\Controllers\AcademicPortalController;

Route::get('/verify-transcript/{code}', [AcademicPortalController::class, 'verify'])->whereUuid('code')->middleware('throttle:30,1')->name('academic.verify');

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


// Email verification must be accessible before the verified middleware.
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [\App\Http\Controllers\EmailOtpController::class, 'show'])->name('verification.notice');
    Route::post('/email/verify', [\App\Http\Controllers\EmailOtpController::class, 'verify'])
        ->middleware('throttle:5,1')->name('verification.otp');
    Route::post('/email/verification-notification', [\App\Http\Controllers\EmailOtpController::class, 'resend'])
        ->middleware('throttle:3,1')->name('verification.send');
    // Retire the link-based endpoint: email verification now requires a code.
    Route::get('/email/verify/{id}/{hash}', fn () => abort(404))->name('verification.verify');
});

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

    Route::prefix('academic')->name('academic.')->group(function () {
        Route::get('/', [AcademicPortalController::class, 'index'])->name('index');
        Route::get('/policies', [AcademicPortalController::class, 'policies'])->name('policies');
        Route::post('/policies', [AcademicPortalController::class, 'storePolicy'])->name('policies.store');
        Route::get('/reports', [AcademicPortalController::class, 'reports'])->name('reports');
        Route::get('/appeals', [AcademicPortalController::class, 'appeals'])->name('appeals');
        Route::post('/appeals', [AcademicPortalController::class, 'storeAppeal'])->middleware('throttle:10,1')->name('appeals.store');
        Route::post('/appeals/{appeal}/resolve', [AcademicPortalController::class, 'resolveAppeal'])->name('appeals.resolve');
        Route::get('/appeals/{appeal}/evidence', [AcademicPortalController::class, 'evidence'])->name('appeals.evidence');
        Route::get('/transcripts', [AcademicPortalController::class, 'transcripts'])->name('transcripts');
        Route::post('/transcripts', [AcademicPortalController::class, 'requestTranscript'])->middleware('throttle:5,1')->name('transcripts.request');
        Route::post('/transcripts/{transcriptRequest}/decide', [AcademicPortalController::class, 'decideTranscript'])->name('transcripts.decide');
        Route::post('/documents/{document}/revoke', [AcademicPortalController::class, 'revoke'])->name('transcripts.revoke');
        Route::post('/batch', [AcademicPortalController::class, 'batch'])->name('batch');
        Route::post('/corrections/{correction}/decide', [AcademicPortalController::class, 'decideCorrection'])->name('correction.decide');
        Route::middleware('usertype:admin,exam_officer')->group(function () {
            Route::get('/entry', [ResultController::class, 'create'])->name('entry');
            Route::get('/upload', [ResultController::class, 'upload'])->name('upload');
            Route::post('/results', [ResultController::class, 'store'])->name('results.store');
            Route::post('/results/upload', [ResultController::class, 'storeUpload'])->name('results.storeUpload');
            Route::get('/results/template', [ResultController::class, 'downloadTemplate'])->name('results.template.download');
            Route::get('/results/get-students/{department_id}', [ResultController::class, 'getStudentsByDepartment'])->name('results.students');
        });
        Route::get('/result/{result}', [AcademicPortalController::class, 'show'])->name('show');
        Route::post('/result/{result}/resit', [AcademicPortalController::class, 'resit'])->name('resit');
        Route::put('/result/{result}', [AcademicPortalController::class, 'update'])->name('update');
        Route::post('/result/{result}/transition', [AcademicPortalController::class, 'transition'])->name('transition');
        Route::post('/result/{result}/correction', [AcademicPortalController::class, 'correction'])->name('correction');
    });

    Route::get('/admission', [AdmissionController::class, 'create'])->name('admissions.create');
    Route::post('/admission', [AdmissionController::class, 'store'])->name('admissions.store');
    Route::get('/attendance/scan/{token}', [AttendanceController::class, 'registerByScan'])
        ->name('attendance.scan.register');

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
            Route::get('/{userId}/{semester}', [ResultController::class, 'show'])
                ->where(['userId' => '[0-9]+', 'semester' => 'First|Second'])
                ->name('show.bySemester');
            Route::get('/{userId}/{session}/{semester}', [ResultController::class, 'show'])
                ->where(['userId' => '[0-9]+', 'session' => '.*'])
                ->name('show');

            // ✅ Student transcript route
            Route::post('/{userId}/{semester}/transcript', [ResultController::class, 'generateTranscriptForSemester'])
                ->where(['userId' => '[0-9]+', 'semester' => 'First|Second'])
                ->name('transcript.bySemester');
            Route::post('/{userId}/{session}/{semester}/transcript', [ResultController::class, 'generateTranscriptForSemester'])
                ->where('session', '.*')
                ->name('transcript');
        });
    });

    // -------------------------
    // ADMIN ROUTES
    // -------------------------
    Route::prefix('admin')->name('admin.')->middleware('usertype:admin')->group(function () {
        Route::prefix('backups')->name('backups.')->controller(\App\Http\Controllers\Admin\BackupController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->middleware('throttle:3,1')->name('store');
            Route::get('/{backup}/download', 'download')->where('backup', '[a-f0-9]{64}')->name('download');
            Route::get('/{backup}/restore', 'confirm')->where('backup', '[a-f0-9]{64}')->name('confirm');
            Route::post('/{backup}/restore', 'restore')->where('backup', '[a-f0-9]{64}')->middleware('throttle:3,1')->name('restore');
        });

        Route::get('/admitted-students', [App\Http\Controllers\AdmittedStudentController::class, 'index'])->name('admitted-students.index');
        Route::get('/admitted-students/{application}', [App\Http\Controllers\AdmittedStudentController::class, 'show'])->name('admitted-students.show');
        Route::get('/admitted-students/{application}/documents/{document}', [App\Http\Controllers\AdmittedStudentController::class, 'document'])->name('admitted-students.document');
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
        
        // Class schedules routes - define custom routes before resource
        Route::get('/class-schedules/courses/filtered', [ClassScheduleController::class, 'getFilteredCourses'])
            ->name('class-schedules.courses.filtered');
        
        Route::resources([
            'faculties' => FacultyController::class,
            'departments' => DepartmentController::class,
            'class-schedules' => ClassScheduleController::class,
        ]);
        Route::post('attendance/scan-code', [AttendanceController::class, 'createScanSession'])
            ->name('attendance.scan-code.store');
        Route::post('attendance/{attendance}/scan-code', [AttendanceController::class, 'sendScanCode'])
            ->name('attendance.scan-code.send');
        Route::resource('attendance', AttendanceController::class)
            ->parameters(['attendance' => 'attendance']);

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
            Route::post('/{academicSession}/activate', [AcademicSessionController::class, 'activate'])->name('activate');
        });

        Route::prefix('results')->name('results.')->group(function () {
            Route::get('/', [ResultController::class, 'index'])->name('index');
            // put specific editing endpoints before the general show route
            Route::get('/edit-group/{user_id}/{semester}', [ResultController::class, 'editGroup'])
                ->where('semester', 'First|Second')
                ->name('editGroup.bySemester');
            Route::get('/edit-group/{user_id}/{session}/{semester}', [ResultController::class, 'editGroup'])
                ->where('session', '.*')
                ->name('editGroup');
            Route::put('/update-group/{user_id}/{semester}', [ResultController::class, 'updateGroup'])
                ->where('semester', 'First|Second')
                ->name('updateGroup.bySemester');
            Route::put('/update-group/{user_id}/{session}/{semester}', [ResultController::class, 'updateGroup'])
                ->where('session', '.*')
                ->name('updateGroup');
            Route::delete('/group/{user_id}/{semester}', [ResultController::class, 'destroyGroup'])
                ->where('semester', 'First|Second')
                ->name('destroyGroup.bySemester');
            Route::delete('/group/{user_id}/{session}/{semester}', [ResultController::class, 'destroyGroup'])
                ->where('session', '.*')
                ->name('destroyGroup');

            Route::get('/{userId}/{semester}', [ResultController::class, 'show'])
                ->where(['userId' => '[0-9]+', 'semester' => 'First|Second'])
                ->name('show.bySemester');
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
                Route::post('/{userId}/{semester}/transcript', [ResultController::class, 'generateTranscriptForSemester'])
                    ->where(['userId' => '[0-9]+', 'semester' => 'First|Second'])
                    ->name('transcript.generate.bySemester');
                Route::post('/{userId}/{session}/{semester}/transcript', [ResultController::class, 'generateTranscriptForSemester'])
                    ->where('session', '.*')
                    ->name('transcript.generate');
                Route::post('/{userId}/full-transcript', [ResultController::class, 'generateFullTranscriptForStudent'])
                    ->name('transcript.full');

                // ✅ Bulk transcripts
                Route::post('/{semester}/transcripts', [ResultController::class, 'generateTranscriptsForAll'])
                    ->where('semester', 'First|Second')
                    ->name('transcripts.bulk.bySemester');
                Route::post('/{session}/{semester}/transcripts', [ResultController::class, 'generateTranscriptsForAll'])
                    ->where('session', '.*')
                    ->name('transcripts.bulk');
            });
        });
    });

    Route::prefix('lecturer')->name('lecturer.')->middleware('usertype:lecturer')->group(function () {
        Route::post('attendance/scan-code', [AttendanceController::class, 'createScanSession'])
            ->name('attendance.scan-code.store');
        Route::post('attendance/{attendance}/scan-code', [AttendanceController::class, 'sendScanCode'])
            ->name('attendance.scan-code.send');
        Route::resource('attendance', AttendanceController::class)
            ->parameters(['attendance' => 'attendance']);

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
            Route::get('/edit-group/{user_id}/{semester}', [ResultController::class, 'editGroup'])
                ->where('semester', 'First|Second')
                ->name('editGroup.bySemester');
            Route::get('/edit-group/{user_id}/{session}/{semester}', [ResultController::class, 'editGroup'])
                ->where('session', '.*')
                ->name('editGroup');
            Route::put('/update-group/{user_id}/{semester}', [ResultController::class, 'updateGroup'])
                ->where('semester', 'First|Second')
                ->name('updateGroup.bySemester');
            Route::put('/update-group/{user_id}/{session}/{semester}', [ResultController::class, 'updateGroup'])
                ->where('session', '.*')
                ->name('updateGroup');
            Route::delete('/group/{user_id}/{semester}', [ResultController::class, 'destroyGroup'])
                ->where('semester', 'First|Second')
                ->name('destroyGroup.bySemester');
            Route::delete('/group/{user_id}/{session}/{semester}', [ResultController::class, 'destroyGroup'])
                ->where('session', '.*')
                ->name('destroyGroup');
            Route::get('/{result}/edit', [ResultController::class, 'edit'])->name('edit');
            Route::put('/{result}', [ResultController::class, 'update'])->name('update');
            Route::delete('/{result}', [ResultController::class, 'destroy'])->name('destroy');
            Route::get('/{userId}/{semester}', [ResultController::class, 'show'])
                ->where(['userId' => '[0-9]+', 'semester' => 'First|Second'])
                ->name('show.bySemester');
            Route::get('/{userId}/{session}/{semester}', [ResultController::class, 'show'])
                ->where(['userId' => '[0-9]+', 'session' => '.*'])
                ->name('show');
        });
    });
});
