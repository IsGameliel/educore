<?php
namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\{FinancialApproval, PaymentSettlement};
use App\Services\FinancialApprovals;
use Illuminate\Http\Request;

class FinancialControlController extends Controller
{
    public function index()
    {
        return view('finance.controls', [
            'approvals'=>FinancialApproval::with(['invoice','requester','reviewer'])->latest()->paginate(20, ['*'], 'approvals_page'),
            'settlements'=>PaymentSettlement::latest('checked_at')->paginate(20, ['*'], 'settlements_page'),
            'pending'=>FinancialApproval::where('status','pending')->count(),
            'exceptions'=>PaymentSettlement::where('unmatched_count','>',0)->count(),
            'latePayments'=>\App\Models\Payment::with('tuitionInvoice')->where('status','success')
                ->whereHas('tuitionInvoice', fn ($query) => $query->whereNotNull('cancelled_at'))->latest('paid_at')->limit(50)->get(),
        ]);
    }
    public function review(Request $request, FinancialApproval $approval, FinancialApprovals $service)
    {
        $data = $request->validate(['decision'=>'required|in:approved,rejected', 'reason'=>'required|string|min:10|max:2000']);
        $service->review($approval, $request->user(), $data['decision'], $data['reason']);
        return back()->with('success', 'Financial decision recorded.');
    }
}
