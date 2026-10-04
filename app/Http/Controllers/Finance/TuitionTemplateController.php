<?php
namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\{AcademicSession, Department, TuitionSchedule, TuitionTemplate};
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TuitionTemplateController extends Controller
{
    public function index()
    {
        return view('finance.templates', ['templates'=>TuitionTemplate::latest()->get(), 'schedules'=>TuitionSchedule::with(['academicSession','department'])->latest()->get(),
            'sessions'=>AcademicSession::orderByDesc('start_year')->get(), 'departments'=>Department::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->dashboardRole(), ['admin','bursar']), 403);
        $data = $request->validate(['name'=>'required|string|max:120','schedule_id'=>'required|exists:tuition_schedules,id']);
        $schedule = TuitionSchedule::findOrFail($data['schedule_id']);
        $template = TuitionTemplate::create($schedule->only(['period','items','amount','first_percent']) + ['name'=>$data['name'],'created_by'=>$request->user()->id]);
        ActivityLogger::log($request->user(), 'tuition_template_created', 'Saved reusable tuition fee template.', ['subject'=>$template]);
        return back()->with('success', 'Template saved. Reuse it to create drafts with new cohorts and deadlines.');
    }

    public function apply(Request $request, TuitionTemplate $template)
    {
        abort_unless(in_array($request->user()->dashboardRole(), ['admin','bursar']), 403);
        $data = $request->validate(['academic_session_id'=>'required|exists:academic_sessions,id',
            'department_ids'=>'required|array|min:1|max:100','department_ids.*'=>'required|integer|distinct|exists:departments,id',
            'levels'=>'required|array|min:1|max:6','levels.*'=>'required|distinct|in:100,200,300,400,500,600',
            'category'=>'required|in:new,returning', 'due_date'=>'required|date_format:Y-m-d',
            'second_due_date'=>[$template->period === 'Annual' && $template->first_percent < 100 ? 'required' : 'exclude', 'date_format:Y-m-d','after:due_date']]);
        $created = DB::transaction(function () use ($request, $template, $data) {
            AcademicSession::whereKey($data['academic_session_id'])->lockForUpdate()->firstOrFail();
            $template = TuitionTemplate::lockForUpdate()->findOrFail($template->id);
            $created = 0;
            foreach ($data['department_ids'] as $departmentId) {
                foreach ($data['levels'] as $level) {
                    $schedule = TuitionSchedule::firstOrCreate(['academic_session_id'=>$data['academic_session_id'],'department_id'=>$departmentId,
                        'level'=>$level,'category'=>$data['category'],'period'=>$template->period], $template->only(['items','amount','first_percent']) + [
                            'due_date'=>$data['due_date'],'second_due_date'=>$data['second_due_date'] ?? null,'status'=>'draft','created_by'=>$request->user()->id]);
                    if ($schedule->wasRecentlyCreated) { $created++; }
                }
            }
            ActivityLogger::log($request->user(), 'tuition_template_applied', 'Created draft tuition schedules from template.', [
                'subject'=>$template,'properties'=>$data + ['created'=>$created]]);
            return $created;
        }, 3);
        return redirect()->route('finance.schedules')->with('success', $created.' draft schedule(s) created. Existing schedules were skipped. Review and publish each draft to issue invoices.');
    }

    public function destroy(Request $request, TuitionTemplate $template)
    {
        abort_unless(in_array($request->user()->dashboardRole(), ['admin','bursar']), 403);
        ActivityLogger::log($request->user(), 'tuition_template_deleted', 'Deleted reusable template.', ['subject'=>$template,'properties'=>$template->toArray()]);
        $template->delete();
        return back()->with('success', 'Template deleted. Existing schedules and invoices were preserved.');
    }
}
