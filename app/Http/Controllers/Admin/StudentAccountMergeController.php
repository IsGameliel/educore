<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentAccountMerge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentAccountMergeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $query = User::where('usertype', 'student')->with('department');
        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")->orWhere('matric_number', 'like', "%{$search}%"));
        } else {
            $query->whereIn(DB::raw('LOWER(TRIM(name))'), User::selectRaw('LOWER(TRIM(name))')
                ->where('usertype', 'student')->groupByRaw('LOWER(TRIM(name))')->havingRaw('COUNT(*) > 1'));
        }
        $students = $query->orderBy('name')->orderBy('id')->paginate(50)->withQueryString();
        $history = DB::table('student_account_merges')->join('users as retained', 'retained.id', '=', 'retained_user_id')
            ->join('users as actor', 'actor.id', '=', 'performed_by')
            ->select('student_account_merges.*', 'retained.name as retained_name', 'actor.name as actor_name')
            ->orderByDesc('student_account_merges.id')->limit(10)->get();
        return view('admin.students.merge.index', compact('students', 'history'));
    }

    public function preview(Request $request, StudentAccountMerge $service)
    {
        $data = $this->selection($request);
        $preview = $service->preview($data['accounts'], (int) $data['retained_id']);
        $token = Str::random(48);
        $request->session()->put('student_merge_preview', [
            'token' => $token, 'digest' => $preview['digest'],
            'ids' => $preview['accounts']->modelKeys(), 'retained_id' => (int) $data['retained_id'],
        ]);
        return view('admin.students.merge.preview', compact('preview', 'token'));
    }

    public function store(Request $request, StudentAccountMerge $service)
    {
        $request->validate([
            'preview_token' => ['required', 'string'], 'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'confirm_identity' => ['accepted'], 'profile' => ['required', 'array'],
            'profile.*' => ['required', 'integer'],
        ]);
        $saved = $request->session()->get('student_merge_preview');
        if (!$saved || !hash_equals($saved['token'], $request->input('preview_token'))) {
            return redirect()->route('admin.students.merge.index')->withErrors(['merge' => 'Your preview expired. Select the accounts and preview again.']);
        }
        $service->merge($saved['ids'], $saved['retained_id'], $request->input('profile'),
            $request->input('reason'), $request->user()->id, $saved['digest']);
        $request->session()->forget('student_merge_preview');
        return redirect()->route('admin.students.merge.index')->with('success', 'Student accounts merged successfully. The duplicate accounts are archived and can no longer sign in.');
    }

    private function selection(Request $request): array
    {
        return $request->validate([
            'accounts' => ['required', 'array', 'min:2', 'max:10'],
            'accounts.*' => ['required', 'integer', 'distinct'], 'retained_id' => ['required', 'integer'],
        ]);
    }
}
