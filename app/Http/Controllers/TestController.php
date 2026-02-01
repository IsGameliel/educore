<?php

namespace App\Http\Controllers;

use App\Models\{
    Tests, Questions, Responses, Department, Courses
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function index()
    {
        $student = auth()->user();
        $tests = Tests::where('level', $student->level)
                  ->where('department_id', $student->department_id)
                  ->where('status', true)
                  ->get();
        return view('student.test.index', compact('tests'));
    }

    // Display a single question for the test (GET)
    // In startTest
public function startTest($testId, $questionIndex = 0)
{
    Log::info('startTest called', ['testId' => $testId, 'questionIndex' => $questionIndex]);

    $test = Tests::with('questions')->findOrFail($testId);

    // Check if already submitted
    $existing = Responses::where('test_id', $testId)
        ->where('student_id', auth()->id())
        ->first();

    if ($existing) {
        $score = $existing->score;
        return view('student.test.result', compact('test', 'score'))
            ->with('total_marks', $test->questions->sum('marks'));
    }

    // Store start time in session if not set
    $startTimeSessionKey = "test_{$testId}_start_time";
    if (!session($startTimeSessionKey)) {
        $startTime = now()->timestamp * 1000; // Milliseconds
        session([$startTimeSessionKey => $startTime]);
        Log::info('Test start time set', ['testId' => $testId, 'start_time' => $startTime]);
    }

    // Get or set randomized question order
    $questionSessionKey = "test_{$testId}_question_order";
    $questionOrder = session($questionSessionKey);

    if (!$questionOrder) {
        $questions = $test->questions->pluck('id')->toArray();
        shuffle($questions);
        session([$questionSessionKey => $questions]);
        $questionOrder = $questions;
        Log::info('Randomized question order set', ['testId' => $testId, 'questionOrder' => $questionOrder]);
    }

    // Validate question index
    if (!is_numeric($questionIndex) || $questionIndex < 0 || $questionIndex >= count($questionOrder)) {
        Log::info('Redirecting to confirmation page', ['testId' => $testId]);
        return view('student.test.confirm_submission', compact('test'));
    }

    // Get the question for the current index
    $questionId = $questionOrder[$questionIndex];
    $question = $test->questions->where('id', $questionId)->first();

    // Get or set randomized option order
    $optionSessionKey = "test_{$testId}_question_{$questionId}_option_order";
    $optionOrder = session($optionSessionKey);

    if (!$optionOrder) {
        $optionOrder = array_keys($question->options);
        shuffle($optionOrder);
        session([$optionSessionKey => $optionOrder]);
        Log::info('Randomized option order set', [
            'testId' => $testId,
            'questionId' => $questionId,
            'optionOrder' => $optionOrder
        ]);
    }

    // Create shuffled options array
    $shuffledOptions = [];
    foreach ($optionOrder as $key) {
        $shuffledOptions[$key] = $question->options[$key];
    }

    $question->options = $shuffledOptions;

    return view('student.test.start', compact('test', 'question', 'questionIndex'));
}

// In storeAnswer
public function storeAnswer(Request $request, $testId, $questionIndex = 0)
{
    Log::info('storeAnswer called', [
        'testId' => $testId,
        'questionIndex' => $questionIndex,
        'request' => $request->all()
    ]);

    $test = Tests::with('questions')->findOrFail($testId);

    // Prevent retake
    $existing = Responses::where('test_id', $testId)
        ->where('student_id', auth()->id())
        ->first();
    if ($existing) {
        Log::info('Test already submitted.', ['testId' => $testId]);
        return response()->json([
            'success' => false,
            'message' => 'You have already taken this test.',
            'nextUrl' => route('student.tests.result', $testId)
        ]);
    }

    // Validate timer
    $startTime = session("test_{$testId}_start_time");
    if ($startTime) {
        $endTime = $startTime + ($test->duration * 60 * 1000);
        if (now()->timestamp * 1000 > $endTime) {
            Log::warning('Answer submission after time expired', ['testId' => $testId]);
            return response()->json([
                'success' => false,
                'message' => 'Test time has expired.',
                'nextUrl' => route('student.tests.submit', $testId)
            ]);
        }
    } else {
        Log::warning('No start time found, setting fallback', ['testId' => $testId]);
        session(["test_{$testId}_start_time" => now()->timestamp * 1000]);
    }

    // Get question order from session
    $sessionKey = "test_{$testId}_question_order";
    $questionOrder = session($sessionKey, []);
    Log::info('Question order retrieved', ['questionOrder' => $questionOrder]);

    // Validate question index
    if (!is_numeric($questionIndex) || $questionIndex < 0 || $questionIndex >= count($questionOrder)) {
        Log::warning('Invalid question index', ['questionIndex' => $questionIndex]);
        if ($questionIndex === 'submit') {
            Log::info('Redirecting to submitTest', ['testId' => $testId]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid submission attempt.',
                'nextUrl' => route('student.tests.submit', $testId)
            ]);
        }
        $nextIndex = count($questionOrder);
        return response()->json([
            'success' => true,
            'message' => 'No more questions.',
            'nextUrl' => route('student.tests.start', [$testId, $nextIndex])
        ]);
    }

    // Get the question for the current index
    $questionId = $questionOrder[$questionIndex];
    $question = $test->questions->where('id', $questionId)->first();
    if (!$question) {
        Log::error('Question not found', ['questionId' => $questionId, 'testId' => $testId]);
        return response()->json(['success' => false, 'message' => 'Question not found.']);
    }

    // Get option order from session
    $optionSessionKey = "test_{$testId}_question_{$questionId}_option_order";
    $optionOrder = session($optionSessionKey, array_keys($question->options));
    Log::info('Option order retrieved', ['questionId' => $questionId, 'optionOrder' => $optionOrder]);

    // Retrieve or initialize session answers
    $answerSessionKey = "test_{$testId}_answers";
    $answers = session($answerSessionKey, []);

    // Get submitted answer
    $submittedAnswer = $request->input("answers.{$question->id}");
    Log::info('Submitted Answer:', [
        'question_id' => $question->id,
        'submitted_answer' => $submittedAnswer,
        'options' => $question->options,
        'correct_option' => $question->correct_option
    ]);

    if ($submittedAnswer === null) {
        Log::warning('No answer selected');
        return response()->json(['success' => false, 'message' => 'Please select an answer before proceeding.']);
    }

    // Validate submitted answer against option order
    $validKeys = $optionOrder;
    Log::info('Validating answer', [
        'question_id' => $question->id,
        'submitted_answer' => $submittedAnswer,
        'valid_keys' => $validKeys,
        'options' => $question->options
    ]);
    if (!in_array((string)$submittedAnswer, array_map('strval', $validKeys), true)) {
        Log::warning('Invalid answer submitted', [
            'submitted_answer' => $submittedAnswer,
            'valid_keys' => $validKeys,
            'question_id' => $question->id
        ]);
        return response()->json(['success' => false, 'message' => 'Invalid answer selected.']);
    }

    // Update session answers
    $answers[$question->id] = $submittedAnswer;
    session([$answerSessionKey => $answers]);
    Log::info('Answers updated in session', ['answers' => $answers]);

    // Check if there are more questions
    $nextIndex = (int)$questionIndex + 1;
    if ($nextIndex < count($questionOrder)) {
        $nextUrl = route('student.tests.start', [$testId, $nextIndex]);
        return response()->json(['success' => true, 'nextUrl' => $nextUrl]);
    }

    // Redirect to confirmation page
    Log::info('Test ready for submission', ['testId' => $testId]);
    $submitUrl = route('student.tests.start', [$testId, $nextIndex]);
    return response()->json(['success' => true, 'nextUrl' => $submitUrl]);
}

    // Handle final submission of the test
    public function submitTest(Request $request, $testId)
{
    Log::info('submitTest called', ['testId' => $testId, 'request' => $request->all()]);

    try {
        $test = Tests::with('questions')->findOrFail($testId);

        // Check if already submitted
        $existing = Responses::where('test_id', $testId)
            ->where('student_id', auth()->id())
            ->first();

        if ($existing) {
            Log::info('Test already submitted', ['testId' => $testId, 'studentId' => auth()->id()]);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test already submitted.',
                    'nextUrl' => route('student.tests.result', $testId)
                ]);
            }
            $score = $existing->score;
            return view('student.test.result', compact('test', 'score'))
                ->with('total_marks', $test->questions->sum('marks'));
        }

        // Validate timer
        $startTimeSessionKey = "test_{$testId}_start_time";
        $startTime = session($startTimeSessionKey);
        if ($startTime) {
            $endTime = $startTime + ($test->duration * 60 * 1000);
            if (now()->timestamp * 1000 > $endTime) {
                Log::warning('Test submission after time expired', ['testId' => $testId]);
                // Allow submission but log for audit
            }
        } else {
            Log::warning('No start time found, setting fallback', ['testId' => $testId]);
            session([$startTimeSessionKey => now()->timestamp * 1000]);
        }

        // Retrieve answers from session
        $answerSessionKey = "test_{$testId}_answers";
        $answers = session($answerSessionKey, []);
        Log::info('Session answers retrieved', ['answers' => $answers]);

        // If no answers, return error
        if (empty($answers)) {
            Log::error('No answers found for submission', ['testId' => $testId]);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No answers found for submission.'
                ], 400);
            }
            return redirect()->route('student.tests.index')->with('error', 'No answers found for submission.');
        }

        // Calculate score
        $score = 0;
        foreach ($test->questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            $correctOption = $question->correct_option;

            Log::info('Comparing answer', [
                'question_id' => $question->id,
                'submitted_answer' => $submittedAnswer,
                'submitted_type' => gettype($submittedAnswer),
                'correct_option' => $correctOption,
                'correct_type' => gettype($correctOption),
                'options' => $question->options,
                'marks' => $question->marks
            ]);

            if ((string)$submittedAnswer === (string)$correctOption) {
                $score += $question->marks;
                Log::info('Correct answer', ['question_id' => $question->id, 'marks_added' => $question->marks]);
            } else {
                Log::warning('Incorrect answer', ['question_id' => $question->id]);
            }
        }

        Log::info('Score calculated', ['score' => $score, 'total_marks' => $test->questions->sum('marks')]);

        // Save result
        Responses::create([
            'test_id' => $testId,
            'student_id' => auth()->id(),
            'answers' => json_encode($answers),
            'score' => $score,
        ]);

        // Clear session
        $questionSessionKey = "test_{$testId}_question_order";
        $answerSessionKey = "test_{$testId}_answers";
        $startTimeSessionKey = "test_{$testId}_start_time";
        $sessionKeys = [$questionSessionKey, $answerSessionKey, $startTimeSessionKey];
        foreach ($test->questions as $question) {
            $optionSessionKey = "test_{$testId}_question_{$question->id}_option_order";
            $sessionKeys[] = $optionSessionKey;
        }
        Log::info('Clearing session data', ['sessionKeys' => $sessionKeys]);
        session()->forget($sessionKeys);

        // Flash testId to clear sessionStorage
        $request->session()->flash('clearTestStorage', $testId);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Test submitted successfully.',
                'nextUrl' => route('student.tests.submit', $testId)
            ]);
        }

        return view('student.test.result', compact('test', 'score'))
            ->with('total_marks', $test->questions->sum('marks'));
    } catch (\Exception $e) {
        Log::error('Error in submitTest', ['testId' => $testId, 'error' => $e->getMessage()]);
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Server error during submission.'
            ], 500);
        }
        return redirect()->route('student.tests.index')->with('error', 'An error occurred during submission.');
    }
}

    // Admin: Create Test
    public function adminIndex()
    {
        $tests = Tests::all();
        return view('admin.tests.index', compact('tests'));
    }

    public function create()
    {
        $departments = Department::all();
        $courses = Courses::all();
        return view('admin.tests.create', compact('departments', 'courses'));
    }

    public function store(Request $request)
    {
        Tests::create($request->validate([
            'name' => 'required|string',
            'subject' => 'required|string',
            'duration' => 'required|integer',
            'level' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|boolean',
        ]));

        return redirect()->route('admin.tests.index')->with('success', 'Test created successfully');
    }

    // Edit Test
    public function edit($id)
    {
        $test = Tests::findOrFail($id);
        $departments = Department::all();
        return view('admin.tests.edit', compact('test', 'departments'));
    }

    // Update Test
    public function update(Request $request, $id)
    {
        $test = Tests::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string',
            'subject' => 'required|string',
            'duration' => 'required|integer',
            'level' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|boolean',
        ]);

        $test->update($validatedData);

        return redirect()->route('admin.tests.index')->with('success', 'Test updated successfully');
    }

    public function manageQuestions($testId)
    {
        $test = Tests::with('questions')->findOrFail($testId);
        return view('admin.tests.questions', compact('test'));
    }

    public function storeQuestions(Request $request, $testId)
    {
        $test = Tests::findOrFail($testId);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array|min:2',
            'correct_option' => 'required|in:' . implode(',', array_keys($request->options)),
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

    // Edit Question
    public function editQuestion($testId, $questionId)
    {
        $test = Tests::findOrFail($testId);
        $question = $test->questions()->findOrFail($questionId);

        return view('admin.tests.edit-question', compact('test', 'question'));
    }

    // Update Question
    public function updateQuestion(Request $request, $testId, $questionId)
    {
        $test = Tests::findOrFail($testId);
        $question = $test->questions()->findOrFail($questionId);

        $validatedData = $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array',
            'correct_option' => 'required|string|in:' . implode(',', array_keys($request->options)),
            'marks' => 'required|integer',
        ]);

        $question->update($validatedData);

        return redirect()->route('admin.tests.questions', $testId)->with('success', 'Question updated successfully');
    }

    public function viewResponses($testId)
    {
        $test = Tests::findOrFail($testId);
        $responses = Responses::where('test_id', $testId)->with('student')->get();

        return view('admin.tests.responses', compact('test', 'responses'));
    }
}
