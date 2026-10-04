<?php

namespace App\Http\Controllers;

use App\Models\{ActivityLog, ResultAppeal, TranscriptDocument, TranscriptRequest};
use App\Services\StudentServices;
use Illuminate\Http\Request;

class StudentServicesController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();
        abort_unless($student->dashboardRole() === 'student',403);
        $onboarding = StudentServices::onboarding($student);
        $graduation = StudentServices::graduation($student);
        $appeals = ResultAppeal::with('payment')->where('user_id',$student->id)->latest()->paginate(10,['*'],'appeals_page');
        $transcripts = TranscriptRequest::with('payment')->where('user_id',$student->id)->latest()->paginate(10,['*'],'transcripts_page');
        $documents = TranscriptDocument::where('user_id',$student->id)->where('official',true)->latest()->paginate(10,['*'],'documents_page');
        $events = ActivityLog::where('target_user_id',$student->id)->whereIn('action',['student_service_appeal','student_service_transcript','student_service_transcript_document'])->latest('id')->paginate(20,['*'],'updates_page');
        foreach ([$appeals,$transcripts,$documents,$events] as $paginator) { $paginator->withQueryString(); }
        return view('student.services',compact('onboarding','graduation','appeals','transcripts','documents','events'));
    }
}
