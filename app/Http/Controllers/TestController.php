<?php

namespace App\Http\Controllers;

use App\Exports\TestResponsesExport;
use App\Models\Courses;
use App\Models\Responses;
use App\Models\TestAttempt;
use App\Models\Tests;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel;

class TestController extends Controller
{
    public function index()
    {
        $student = auth()->user();
        $tests = Tests::with(['responses' => fn ($query) => $query->where('student_id', $student->id)])
            ->where(fn ($query) => $query->where(fn ($available) => $available->where('level', $student->level)
                ->where('department_id', $student->department_id)->where('status', true))
                ->orWhereHas('responses', fn ($responses) => $responses->where('student_id', $student->id)))
            ->get();

        return view('student.test.index', compact('tests'));
    }

    private function eligibleTest($testId): Tests
    {
        $student = User::lockForUpdate()->findOrFail(auth()->id());
        abort_unless($student->dashboardRole() === 'student', 403);
        $test = Tests::with('questions')->findOrFail($testId);
        abort_unless($student->department_id !== null && $student->level !== null
            && $test->status && (string) $test->level === (string) $student->level
            && (int) $test->department_id === (int) $student->department_id, 403, 'This test is not available to you.');

        return $test;
    }

    private function savedResponse($testId): ?Responses
    {
        return Responses::where('test_id', $testId)->where('student_id', auth()->id())->first();
    }

    private function resultUrl($testId): string
    {
        return route('student.tests.result', $testId);
    }

    public function startTest($testId, $questionIndex = 0)
    {
        return DB::transaction(function () use ($testId, $questionIndex) {
            $test = $this->eligibleTest($testId);
            if ($this->savedResponse($testId)) {
                return redirect($this->resultUrl($testId));
            }
            $attempt = TestAttempt::where('test_id', $testId)->where('student_id', auth()->id())->first();
            if (! $attempt) {
                abort_if($test->questions->isEmpty() || $test->duration < 1, 409, 'This test is not ready to start.');
                $questions = $test->questions->shuffle()->map(function ($question) {
                    $keys = collect(array_keys($question->options))->shuffle();
                    $options = [];
                    foreach ($keys as $key) {
                        $options[$key] = $question->options[$key];
                    }

                    return ['id' => $question->id, 'question_text' => $question->question_text,
                        'options' => $options, 'correct_option' => $question->correct_option, 'marks' => $question->marks];
                })->values()->all();
                $attempt = TestAttempt::create(['test_id' => $testId, 'student_id' => auth()->id(),
                    'started_at' => now(), 'expires_at' => now()->addMinutes($test->duration),
                    'questions' => $questions, 'answers' => []]);
            }
            if (now()->greaterThanOrEqualTo($attempt->expires_at)) {
                $this->finishAttempt($attempt);

                return redirect($this->resultUrl($testId));
            }
            abort_unless(ctype_digit((string) $questionIndex), 404);
            $questionIndex = (int) $questionIndex;
            $expiresAt = $attempt->expires_at->getTimestampMs();
            $questionCount = count($attempt->questions);
            if ($questionIndex >= $questionCount) {
                return view('student.test.confirm_submission', compact('test', 'expiresAt'));
            }
            $snapshot = $attempt->questions[$questionIndex];
            // Never pass the answer key to the student view.
            unset($snapshot['correct_option']);
            $question = (object) $snapshot;
            $selectedAnswer = $attempt->answers[$question->id] ?? null;

            return view('student.test.start', compact('test', 'question', 'questionIndex', 'expiresAt', 'questionCount', 'selectedAnswer'));
        }, 3);
    }

    public function storeAnswer(Request $request, $testId, $questionIndex = 0)
    {
        return DB::transaction(function () use ($request, $testId, $questionIndex) {
            $this->eligibleTest($testId);
            if ($this->savedResponse($testId)) {
                return response()->json(['success' => true, 'nextUrl' => $this->resultUrl($testId)]);
            }
            $attempt = TestAttempt::where('test_id', $testId)->where('student_id', auth()->id())->lockForUpdate()->first();
            abort_unless($attempt, 409, 'Start this test before saving answers.');
            if (now()->greaterThanOrEqualTo($attempt->expires_at)) {
                $this->finishAttempt($attempt);

                return response()->json(['success' => true, 'nextUrl' => $this->resultUrl($testId)]);
            }
            abort_unless(ctype_digit((string) $questionIndex) && isset($attempt->questions[(int) $questionIndex]), 404);
            $question = $attempt->questions[(int) $questionIndex];
            $validated = $request->validate(['answers.'.$question['id'] => ['required', 'integer',
                Rule::in(array_keys($question['options']))]]);
            $answers = $attempt->answers;
            $answers[$question['id']] = (int) $validated['answers'][$question['id']];
            $attempt->update(['answers' => $answers]);

            return response()->json(['success' => true, 'nextUrl' => route('student.tests.start', [$testId, (int) $questionIndex + 1])]);
        }, 3);
    }

    public function submitTest(Request $request, $testId)
    {
        return DB::transaction(function () use ($request, $testId) {
            $this->eligibleTest($testId);
            if (! $this->savedResponse($testId)) {
                $attempt = TestAttempt::where('test_id', $testId)->where('student_id', auth()->id())->lockForUpdate()->first();
                abort_unless($attempt, 409, 'Start this test before submitting it.');
                $this->finishAttempt($attempt);
            }

            return $request->expectsJson()
                ? response()->json(['success' => true, 'nextUrl' => $this->resultUrl($testId)])
                : redirect($this->resultUrl($testId));
        }, 3);
    }

    private function finishAttempt(TestAttempt $attempt): void
    {
        if ($attempt->submitted_at) {
            return;
        }
        $score = 0;
        foreach ($attempt->questions as $question) {
            $answer = $attempt->answers[$question['id']] ?? null;
            if ($answer !== null && (string) $answer === (string) $question['correct_option']) {
                $score += $question['marks'];
            }
        }
        Responses::firstOrCreate(['test_id' => $attempt->test_id, 'student_id' => $attempt->student_id],
            ['answers' => $attempt->answers, 'score' => $score]);
        $attempt->update(['submitted_at' => now()]);
    }

    public function studentResult($testId)
    {
        $test = Tests::findOrFail($testId);
        $response = Responses::where('test_id', $testId)->where('student_id', auth()->id())->firstOrFail();
        $attempt = TestAttempt::where('test_id', $testId)->where('student_id', auth()->id())->first();
        $score = $response->score;
        $total_marks = $attempt ? collect($attempt->questions)->sum('marks') : $test->questions->sum('marks');

        return view('student.test.result', compact('test', 'score', 'total_marks'));
    }

    // Admin / Lecturer test management
    public function adminIndex()
    {
        $tests = $this->manageableTestsQuery(Auth::user())->get();

        return view('admin.tests.index', compact('tests'));
    }

    public function create()
    {
        $courses = $this->manageableCourses(Auth::user());
        $departments = $courses->pluck('department')->filter()->unique('id')->sortBy('name')->values();

        return view('admin.tests.create', compact('departments', 'courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'subject' => 'required|string',
            'duration' => 'required|integer|min:1|max:1440',
            'level' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|boolean',
        ]);

        $this->ensureCanManageTestPayload($validated['subject'], (int) $validated['department_id'], (string) $validated['level']);

        $test = Tests::create($validated);

        if ($test->status) {
            $this->logTestActivity($test, 'test_published', "Published test: {$test->name} for {$test->level} Level");
        }

        return redirect()->route($this->testRouteName('index'))->with('success', 'Test created successfully');
    }

    public function edit($id)
    {
        $test = Tests::findOrFail($id);
        $this->ensureCanManageTest($test);
        $courses = $this->manageableCourses(Auth::user());
        $departments = $courses->pluck('department')->filter()->unique('id')->sortBy('name')->values();

        return view('admin.tests.edit', compact('test', 'departments', 'courses'));
    }

    public function update(Request $request, $id)
    {
        $test = Tests::findOrFail($id);
        $this->ensureCanManageTest($test);

        $validatedData = $request->validate([
            'name' => 'required|string',
            'subject' => 'required|string',
            'duration' => 'required|integer|min:1|max:1440',
            'level' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|boolean',
        ]);

        $this->ensureCanManageTestPayload($validatedData['subject'], (int) $validatedData['department_id'], (string) $validatedData['level']);

        $wasActive = (bool) $test->status;
        $test->update($validatedData);
        $test->refresh();

        if ($test->status) {
            $action = $wasActive ? 'test_updated' : 'test_published';
            $description = $wasActive
                ? "Updated test: {$test->name} for {$test->level} Level"
                : "Published test: {$test->name} for {$test->level} Level";

            $this->logTestActivity($test, $action, $description);
        }

        return redirect()->route($this->testRouteName('index'))->with('success', 'Test updated successfully');
    }

    public function destroy($testId)
    {
        $test = Tests::findOrFail($testId);
        $this->ensureCanManageTest($test);
        $test->delete();

        return redirect()->route($this->testRouteName('index'))
            ->with('success', 'Test deleted successfully. Its questions and student responses were also deleted.');
    }

    public function manageQuestions($testId)
    {
        $test = Tests::with('questions')->findOrFail($testId);
        $this->ensureCanManageTest($test);

        return view('admin.tests.questions', compact('test'));
    }

    public function storeQuestions(Request $request, $testId)
    {
        $test = Tests::findOrFail($testId);
        $this->ensureCanManageTest($test);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array|list|min:2',
            'options.*' => 'required|string',
            'correct_option' => ['required', 'integer', Rule::in(array_keys((array) $request->input('options', [])))],
            'marks' => 'required|integer|min:1',
        ]);

        $test->questions()->create([
            'question_text' => $validated['question_text'],
            'options' => $validated['options'],
            'correct_option' => $validated['correct_option'],
            'marks' => $validated['marks'],
        ]);

        return back()->with('success', 'Question added successfully');
    }

    public function editQuestion($testId, $questionId)
    {
        $test = Tests::findOrFail($testId);
        $this->ensureCanManageTest($test);
        $question = $test->questions()->findOrFail($questionId);

        return view('admin.tests.edit-question', compact('test', 'question'));
    }

    public function updateQuestion(Request $request, $testId, $questionId)
    {
        $test = Tests::findOrFail($testId);
        $this->ensureCanManageTest($test);
        $question = $test->questions()->findOrFail($questionId);

        $validatedData = $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array|list|min:2',
            'options.*' => 'required|string',
            'correct_option' => ['required', 'integer', Rule::in(array_keys((array) $request->input('options', [])))],
            'marks' => 'required|integer|min:1',
        ]);

        $question->update($validatedData);

        return redirect()->route($this->testRouteName('questions'), $testId)->with('success', 'Question updated successfully');
    }

    public function deleteQuestion($testId, $questionId)
    {
        $test = Tests::findOrFail($testId);
        $this->ensureCanManageTest($test);
        $question = $test->questions()->findOrFail($questionId);
        $question->delete();

        return redirect()->route($this->testRouteName('questions'), $testId)->with('success', 'Question deleted successfully');
    }

    public function viewResponses(Request $request, $testId)
    {
        $test = Tests::findOrFail($testId);
        $this->ensureCanManageTest($test);
        $responses = Responses::where('test_id', $testId)->with('student')->get();

        if ($request->boolean('export')) {
            $courseCodes = $this->manageableCourses(Auth::user())->where('title', $test->subject)
                ->where('department_id', $test->department_id)->where('level', $test->level)
                ->pluck('code')->unique()->sort()->join('; ');

            return \Maatwebsite\Excel\Facades\Excel::download(
                new TestResponsesExport($responses, $courseCodes, $test->subject),
                'test-'.$test->id.'-responses.xlsx', Excel::XLSX,
                ['Cache-Control' => 'private, no-store'],
            );
        }

        return view('admin.tests.responses', compact('test', 'responses'));
    }

    protected function manageableCourses($user)
    {
        if ($user->usertype === 'lecturer') {
            return $user->assignedCourses()->with('department')->orderBy('code')->get();
        }

        return Courses::with('department')->orderBy('code')->get();
    }

    protected function manageableTestsQuery($user)
    {
        $query = Tests::query();

        if ($user->usertype !== 'lecturer') {
            return $query;
        }

        $courses = $this->manageableCourses($user);

        if ($courses->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($testQuery) use ($courses) {
            foreach ($courses as $course) {
                $testQuery->orWhere(function ($matchQuery) use ($course) {
                    $matchQuery->where('subject', $course->title)
                        ->where('department_id', $course->department_id)
                        ->where('level', (string) $course->level);
                });
            }
        });
    }

    protected function ensureCanManageTest(Tests $test): void
    {
        if (Auth::user()->usertype !== 'lecturer') {
            return;
        }

        $allowed = Auth::user()->assignedCourses()
            ->where('title', $test->subject)
            ->where('department_id', $test->department_id)
            ->where('level', $test->level)
            ->exists();

        if (! $allowed) {
            abort(403, 'You are not assigned to this course.');
        }
    }

    protected function ensureCanManageTestPayload(string $subject, int $departmentId, string $level): void
    {
        if (Auth::user()->usertype !== 'lecturer') {
            return;
        }

        $allowed = Auth::user()->assignedCourses()
            ->where('title', $subject)
            ->where('department_id', $departmentId)
            ->where('level', $level)
            ->exists();

        if (! $allowed) {
            abort(403, 'You are not assigned to this course.');
        }
    }

    protected function testRouteName(string $name): string
    {
        return (Auth::user()?->usertype === 'lecturer' ? 'lecturer' : 'admin').'.tests.'.$name;
    }

    protected function logTestActivity(Tests $test, string $action, string $description): void
    {
        ActivityLogger::log(
            Auth::user(),
            $action,
            $description,
            [
                'subject' => $test,
                'department_id' => $test->department_id,
                'properties' => [
                    'test_id' => $test->id,
                    'test_name' => $test->name,
                    'subject' => $test->subject,
                    'duration' => $test->duration,
                    'level' => (string) $test->level,
                ],
            ]
        );
    }
}
