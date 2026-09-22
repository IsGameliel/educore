<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\GradingPolicy;
use App\Models\Result;
use App\Models\ResultAppeal;
use App\Models\ResultCorrection;
use App\Models\ResultRevision;
use App\Models\TranscriptDocument;
use App\Models\TranscriptRequest;
use App\Models\User;
use App\Services\Academic\AcademicStanding;
use App\Services\Academic\Grading;
use App\Services\Academic\ResultAccess;
use App\Services\Academic\ResultWorkflow;
use App\Services\Academic\Transcripts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademicPortalController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        abort_unless(in_array($actor->dashboardRole(), ['admin', 'exam_officer', 'lecturer', 'student'], true), 403);
        $manager = ResultAccess::manager($actor);
        $query = ResultAccess::scope(Result::with(['user', 'department']), $actor);
        foreach (['department_id', 'session', 'semester', 'workflow_status', 'course_code'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        $results = $query->orderByDesc('session')->orderBy('course_code')->paginate(30)->withQueryString();

        return view('academic.index', compact('results', 'manager') + $this->options());
    }

    private function options(): array
    {
        return ['departments' => Department::orderBy('name')->get(), 'sessions' => AcademicSession::orderByDesc('start_year')->pluck('name')];
    }

    public function show(Request $request, Result $result)
    {
        ResultAccess::view($request->user(), $result);
        $student = $request->user()->dashboardRole() === 'student';
        $originalExam = $result->resitOf;
        $attempts = $originalExam
            ? collect([$originalExam])->merge($originalExam->resitAttempts)
            : collect([$result])->merge($result->resitAttempts);

        return view('academic.result', [
            'attempts' => $attempts, 'originalExam' => $originalExam,
            'result' => $result, 'manager' => ResultAccess::manager($request->user()), 'student' => $student,
            'revisions' => $student ? collect() : ResultRevision::where('result_id', $result->id)->orderByDesc('id')->get(),
            'corrections' => $student ? collect() : ResultCorrection::where('result_id', $result->id)->orderByDesc('id')->get(),
            'problems' => $student ? [] : ResultWorkflow::problems($result),
        ]);
    }

    public function resit(Request $request, Result $result)
    {
        abort_unless(ResultAccess::manager($request->user()), 403, 'Only admin or exam officers can authorize a resit.');
        $request->validate(['reason' => 'required|string|min:5|max:2000']);
        $attempt = DB::transaction(function () use ($request, $result) {
            User::whereKey($result->user_id)->lockForUpdate()->firstOrFail();
            $result = Result::lockForUpdate()->findOrFail($result->id);
            if ($result->workflow_status !== 'published' || $result->outcome_status !== 'graded'
                || $result->grade_point === null || $result->grade_point > 0 || $result->attempt_type === 'resit') {
                throw ValidationException::withMessages(['resit' => 'Authorize a resit from the published failed original exam result.']);
            }
            $attempt = new Result($result->only(['user_id', 'matric_number', 'department_id', 'session', 'semester', 'level', 'course_code', 'course_title', 'credit_unit']) + [
                'uploaded_by' => $request->user()->id, 'attempt_type' => 'resit', 'outcome_status' => 'not_submitted',
            ]);
            $attempt->course_registration_id = $result->course_registration_id;
            \App\Services\Academic\ResultRegistration::attach($attempt);
            $attempt->resit_of_result_id = $result->id;
            $attempt->resit_authorized_by = $request->user()->id;
            $attempt->resit_authorized_at = now();
            $attempt->save();
            ResultWorkflow::record($attempt, null, 'resit_authorized', $request->input('reason'), null, $request->user()->id);

            return $attempt;
        });

        return redirect()->route('academic.show', $attempt)->with('success', 'Resit authorized. Enter the new score on this separate attempt; the original result is preserved.');
    }

    public function transition(Request $request, Result $result)
    {
        $data = $request->validate(['action' => 'required|in:submit,review,approve,publish,return', 'reason' => 'required|string|min:5|max:2000']);
        ResultWorkflow::transition($result, $request->user(), $data['action'], $data['reason']);

        return back()->with('success', 'Workflow updated.');
    }

    public function batch(Request $request)
    {
        $data = $request->validate(['result_ids' => 'required|array|min:1|max:200', 'result_ids.*' => 'required|integer|distinct', 'action' => 'required|in:submit,review,approve,publish,return', 'reason' => 'required|string|min:5|max:2000']);
        DB::transaction(function () use ($data, $request) {
            $studentIds = Result::whereIn('id', $data['result_ids'])->orderBy('user_id')->pluck('user_id')->unique();
            User::whereIn('id', $studentIds)->orderBy('id')->lockForUpdate()->get();
            foreach (collect($data['result_ids'])->sort()->values() as $id) {
                ResultWorkflow::transition(Result::findOrFail($id), $request->user(), $data['action'], $data['reason']);
            }
        });

        return back()->with('success', 'Selected results updated.');
    }

    private function marks(Request $request): array
    {
        return $request->validate([
            'outcome_status' => 'required|in:graded,absent,incomplete,withheld,deferred,withdrawn,not_submitted',
            'attempt_type' => 'required|in:regular,repeat,resit', 'credit_unit' => 'required|integer|min:1|max:30',
            'ca_score' => 'nullable|numeric|min:0|max:100', 'exam_score' => 'nullable|numeric|min:0|max:100',
            'score' => 'nullable|numeric|min:0|max:100',
        ]);
    }

    public function update(Request $request, Result $result)
    {
        ResultAccess::edit($request->user(), $result);
        $data = $this->marks($request);
        $request->validate(['reason' => 'required|string|min:5|max:2000']);
        if ($request->boolean('adopt_policy')) {
            abort_unless(ResultAccess::manager($request->user()), 403);
            $result->policy_snapshot = Grading::policy($result->department_id, $result->session);
            $result->grading_policy_id = $result->policy_snapshot['id'] ?? null;
        }
        \App\Services\Academic\ResultRegistration::attach($result);
        $result->fill($data)->save();

        return back()->with('success', 'Draft saved with a complete revision record.');
    }

    public function correction(Request $request, Result $result)
    {
        $data = $this->marks($request);
        $reason = $request->validate(['reason' => 'required|string|min:5|max:2000'])['reason'];
        ResultWorkflow::requestCorrection($result, $request->user(), $data, $reason, $request->boolean('adopt_policy'));

        return back()->with('success', 'Correction submitted for approval.');
    }

    public function decideCorrection(Request $request, ResultCorrection $correction)
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'reason' => 'required|string|min:5|max:2000']);
        ResultWorkflow::decideCorrection($correction, $request->user(), $data['decision'], $data['reason']);

        return back()->with('success', $data['decision'] === 'approved'
            ? 'Correction approved. Publish the corrected result to make it visible to the student.'
            : 'Correction rejected. The existing result and scores remain unchanged.');
    }

    public function policies(Request $request)
    {
        abort_unless(ResultAccess::manager($request->user()), 403);

        return view('academic.policies', ['policies' => GradingPolicy::orderByDesc('id')->paginate(20), 'defaults' => GradingPolicy::defaults()] + $this->options());
    }

    public function storePolicy(Request $request)
    {
        abort_unless(ResultAccess::manager($request->user()), 403);
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id', 'session' => ['required', Rule::exists('academic_sessions', 'name')],
            'ca_max' => 'required|integer|min:0|max:100', 'exam_max' => 'required|integer|min:0|max:100',
            'pass_mark' => 'required|integer|min:1|max:100', 'repeat_rule' => 'required|in:all,latest,highest',
            'graduation_credits' => 'nullable|integer|min:1|max:1000', 'graduation_cgpa' => 'nullable|numeric|min:0|max:5',
            'bands' => 'required|array|min:2|max:20', 'bands.*.min' => 'required|numeric|min:0|max:100|distinct', 'bands.*.grade' => 'required|string|max:5|distinct', 'bands.*.point' => 'required|numeric|min:0|max:5', 'required_courses_text' => 'nullable|string|max:10000',
        ]);
        if ($data['ca_max'] + $data['exam_max'] !== 100) {
            throw ValidationException::withMessages(['ca_max' => 'CA and exam maximums must total 100.']);
        }
        $bands = $data['bands'];
        validator(['bands' => $bands], ['bands' => 'required|array|min:2|max:20', 'bands.*.min' => 'required|numeric|min:0|max:100|distinct', 'bands.*.grade' => 'required|string|max:5|distinct', 'bands.*.point' => 'required|numeric|min:0|max:5'])->validate();
        $bands = collect($bands)->sortByDesc('min')->values()->all();
        if (collect($bands)->min('min') != 0) {
            throw ValidationException::withMessages(['bands' => 'Include a grade band starting at zero.']);
        }
        $data['bands'] = $bands;
        $data['required_courses'] = collect(preg_split('/[\s,]+/', strtoupper($data['required_courses_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))->unique()->values()->all();
        unset($data['required_courses_text']);
        DB::transaction(function () use ($data, $request) {
            Department::whereKey($data['department_id'])->lockForUpdate()->firstOrFail();
            $version = 1 + (int) GradingPolicy::where('department_id', $data['department_id'])->where('session', $data['session'])->max('version');
            GradingPolicy::create($data + ['version' => $version, 'created_by' => $request->user()->id]);
        });

        return back()->with('success', 'Policy version created. Existing results retain their policy until a draft explicitly adopts this version.');
    }

    public function appeals(Request $request)
    {
        $actor = $request->user();
        $manager = ResultAccess::manager($actor);
        abort_unless($manager || $actor->dashboardRole() === 'student', 403);
        $appeals = ResultAppeal::when(! $manager, fn ($q) => $q->where('user_id', $actor->id))->latest()->paginate(20);

        return view('academic.appeals', compact('appeals', 'manager') + $this->options());
    }

    public function storeAppeal(Request $request)
    {
        abort_unless($request->user()->dashboardRole() === 'student', 403);
        $data = $request->validate(['session' => ['required', Rule::exists('academic_sessions', 'name')], 'semester' => 'required|in:First,Second', 'course_code' => 'required|string|max:100', 'message' => 'required|string|min:10|max:5000', 'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $data['user_id'] = $request->user()->id;
        $data['department_id'] = $request->user()->department_id;
        abort_unless($data['department_id'], 422, 'A department is required.');
        unset($data['evidence']);
        if ($request->hasFile('evidence')) {
            $data['evidence_path'] = $request->file('evidence')->store('appeal-evidence', 'local');
        }
        ResultAppeal::create($data);

        return back()->with('success', 'Appeal submitted. You can track the response here.');
    }

    public function resolveAppeal(Request $request, ResultAppeal $appeal)
    {
        abort_unless(ResultAccess::manager($request->user()), 403);
        $data = $request->validate(['status' => 'required|in:in_review,resolved,rejected', 'response' => 'required|string|min:5|max:5000']);
        DB::transaction(function () use ($appeal, $data, $request) {
            $appeal = ResultAppeal::lockForUpdate()->findOrFail($appeal->id);
            if (in_array($appeal->status, ['resolved', 'rejected'], true)) {
                throw ValidationException::withMessages(['appeal' => 'This appeal is already closed.']);
            }
            $appeal->update($data + ['resolved_by' => $request->user()->id]);
        });

        return back()->with('success', 'Appeal response recorded. Grade changes must use the correction workflow.');
    }

    public function evidence(Request $request, ResultAppeal $appeal)
    {
        abort_unless(ResultAccess::manager($request->user()) || $request->user()->id === $appeal->user_id, 403);
        abort_unless($appeal->evidence_path && Storage::disk('local')->exists($appeal->evidence_path), 404);

        return Storage::disk('local')->download($appeal->evidence_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function transcripts(Request $request)
    {
        $actor = $request->user();
        $manager = ResultAccess::manager($actor);
        abort_unless($manager || $actor->dashboardRole() === 'student', 403);
        $requests = TranscriptRequest::when(! $manager, fn ($q) => $q->where('user_id', $actor->id))->latest()->paginate(20);
        $documents = TranscriptDocument::when(! $manager, fn ($q) => $q->where('user_id', $actor->id))->latest()->limit(50)->get();

        return view('academic.transcripts', compact('requests', 'documents', 'manager') + $this->options());
    }

    public function requestTranscript(Request $request)
    {
        $actor = $request->user();
        $manager = ResultAccess::manager($actor);
        abort_unless($manager || $actor->dashboardRole() === 'student', 403);
        $data = $request->validate(['user_id' => $manager ? 'required|exists:users,id' : 'nullable', 'purpose' => 'required|string|min:5|max:1000']);
        $student = $manager ? User::where('usertype', 'student')->findOrFail($data['user_id']) : $actor;
        TranscriptRequest::create(['user_id' => $student->id, 'department_id' => $student->department_id, 'purpose' => $data['purpose']]);

        return back()->with('success', 'Official transcript request submitted.');
    }

    public function decideTranscript(Request $request, TranscriptRequest $transcriptRequest)
    {
        abort_unless(ResultAccess::manager($request->user()), 403);
        $data = $request->validate(['decision' => 'required|in:issue,reject', 'reason' => 'required|string|min:5|max:2000']);
        DB::transaction(function () use ($transcriptRequest, $request, $data) {
            $transcriptRequest = TranscriptRequest::lockForUpdate()->findOrFail($transcriptRequest->id);
            if ($transcriptRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'This request has already been decided.']);
            }
            if ($data['decision'] === 'issue') {
                Transcripts::issue(User::findOrFail($transcriptRequest->user_id), $transcriptRequest->department_id, $request->user(), $transcriptRequest->session, $transcriptRequest->semester, $transcriptRequest);
                $transcriptRequest->refresh()->update(['decision_reason' => $data['reason']]);
            } else {
                $transcriptRequest->update(['status' => 'rejected', 'decided_by' => $request->user()->id, 'decision_reason' => $data['reason']]);
            }
        });

        return back()->with('success', 'Transcript decision recorded.');
    }

    public function revoke(Request $request, TranscriptDocument $document)
    {
        abort_unless(ResultAccess::manager($request->user()), 403);
        $reason = $request->validate(['reason' => 'required|string|min:5|max:2000'])['reason'];
        $document->update(['status' => 'revoked', 'revoked_by' => $request->user()->id, 'revocation_reason' => $reason]);

        return back()->with('success', 'Transcript revoked.');
    }

    public function verify(string $code)
    {
        $document = TranscriptDocument::where('verification_code', $code)->where('official', true)->firstOrFail();
        // Public verification discloses no student identity or academic marks.
        $current = Result::withoutGlobalScope('student_visibility')->whereIn('id', array_keys($document->result_versions))->where('workflow_status', 'published')->pluck('version', 'id')->all();
        $valid = $document->status === 'valid' && $current == $document->result_versions;

        return response()->view('academic.verify', compact('document', 'valid'))->header('Cache-Control', 'no-store');
    }

    public function reports(Request $request)
    {
        abort_unless(ResultAccess::manager($request->user()), 403);
        $data = $request->validate(['department_id' => 'nullable|exists:departments,id', 'session' => ['nullable', Rule::exists('academic_sessions', 'name')], 'semester' => 'nullable|in:First,Second']);
        $rows = collect();
        $distribution = collect();
        $problems = [];
        $counts = collect();
        $broadsheet = collect();
        $performance = null;
        if (! empty($data['department_id']) && ! empty($data['session'])) {
            $departmentId = (int) $data['department_id'];
            $session = $data['session'];
            $results = Result::with('user')->where('department_id', $departmentId)->where('session', $session)
                ->when($data['semester'] ?? null, fn ($q, $semester) => $q->where('semester', $semester))->get();
            $counts = $results->countBy('workflow_status');
            $published = $results->where('workflow_status', 'published');
            $graded = $published->where('outcome_status', 'graded');
            $passedCount = $graded->filter(fn ($r) => $r->grade_point > 0)->count();
            $performance = ['graded' => $graded->count(), 'passed' => $passedCount, 'failed' => $graded->count() - $passedCount, 'pass_rate' => $graded->isEmpty() ? null : round(100 * $passedCount / $graded->count(), 1)];
            $distribution = $published->map(fn ($r) => $r->outcome_status === 'graded' ? $r->grade : $r->outcome_status)->countBy();
            $broadsheet = $published->groupBy('user_id');
            $rows = User::where('usertype', 'student')->where('department_id', $departmentId)->orderBy('name')->get()
                ->map(fn ($student) => ['student' => $student, 'report' => AcademicStanding::report($student, $departmentId, $session, $data['semester'] ?? null)]);
            foreach ($results->unique(fn ($r) => $r->course_code.'-'.$r->semester) as $result) {
                foreach (ResultWorkflow::problems($result) as $problem) {
                    $problems[] = $result->course_code.' ('.$result->semester.'): '.$problem;
                }
            }
            $registeredCourses = CourseRegistration::with('course')->where('session', $session)->whereIn('status', ['registered', 'approved', 'completed'])
                ->whereHas('course', fn ($q) => $q->where('department_id', $departmentId))->get();
            foreach ($registeredCourses as $registration) {
                if (! empty($data['semester']) && $registration->semester !== $data['semester']) {
                    continue;
                }
                if (! $results->contains(fn ($r) => $r->user_id === $registration->user_id && $r->course_code === $registration->course->code && $r->semester === $registration->semester)) {
                    $problems[] = $registration->course->code.': missing result for student #'.$registration->user_id;
                }
            }
        }
        if ($request->boolean('export') && $rows->isNotEmpty()) {
            return response()->streamDownload(function () use ($rows) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Student', 'Matric number', 'CGPA', 'Credits earned', 'Failed courses', 'Standing', 'Graduation']);
                foreach ($rows as $row) {
                    $r = $row['report'];
                    $cells = [$row['student']->name, $row['student']->matric_number, $r['cgpa'], $r['earnedCredits'], $r['failedThisSession'], $r['standing'], ! $r['configured'] ? 'Requirements not configured' : ($r['eligible'] ? 'Eligible' : 'Not eligible')];
                    fputcsv($file, array_map(fn ($v) => preg_match('/^[=+@\-\t\r]/', (string) $v) ? "'".$v : $v, $cells));
                }
                fclose($file);
            }, 'academic-standing.csv', ['Content-Type' => 'text/csv']);
        }

        return view('academic.reports', compact('rows', 'distribution', 'problems', 'counts', 'broadsheet', 'performance') + $this->options());
    }
}
