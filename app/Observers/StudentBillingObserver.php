<?php
namespace App\Observers;

use App\Models\{AcademicSession, User};
use App\Services\{OperationsHealth, TuitionBilling};
use Illuminate\Support\Facades\{DB, Log};

class StudentBillingObserver
{
    public function saved(User $user): void
    {
        if ($user->dashboardRole() !== 'student' || (! $user->wasRecentlyCreated && ! $user->wasChanged(['usertype','department_id','level','entry_year']))) { return; }
        $id = $user->id;
        DB::afterCommit(function () use ($id) {
            try {
                $student = User::find($id);
                $session = AcademicSession::current();
                if ($student && $session) { app(TuitionBilling::class)->ensureInvoices($student, $session); }
            } catch (\Throwable $exception) {
                Log::warning('Enrollment saved; invoice generation deferred.', ['student_id'=>$id, 'type'=>$exception::class]);
                OperationsHealth::record('billing', 'warning', 'An enrollment update needs the scheduled invoice retry.');
            }
        });
    }
}
