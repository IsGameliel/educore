<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Services\{EnrollmentData, OperationsHealth};
use Illuminate\Support\Facades\DB;

class SystemHealthController extends Controller
{
    public function index()
    {
        $session = AcademicSession::current();
        $students = EnrollmentData::incompleteQuery($session)->with('department')->orderBy('name')->paginate(25);
        $queue = ['connection' => config('queue.default'), 'pending' => null, 'failed' => null, 'oldest' => null];
        try {
            $config = config('queue.connections.'.$queue['connection']);
            if (($config['driver'] ?? '') === 'database') {
                $jobs = DB::connection($config['connection'] ?? null)->table($config['table'])->where('queue', $config['queue']);
                $queue['pending'] = (clone $jobs)->count();
                $queue['oldest'] = (clone $jobs)->min('created_at');
            }
            $failed = config('queue.failed');
            if (in_array($failed['driver'] ?? '', ['database', 'database-uuids'])) {
                $queue['failed'] = DB::connection($failed['database'] ?? null)->table($failed['table'])->count();
            }
        } catch (\Throwable $exception) { $queue['error'] = 'Queue storage could not be inspected.'; }
        return view('admin.system-health', ['rows' => OperationsHealth::rows(), 'students' => $students, 'session' => $session, 'queue' => $queue]);
    }
}
