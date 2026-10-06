<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PaymentsExport;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        [$query, $filters] = $this->filteredQuery($request);
        $aggregate = (clone $query)->selectRaw("SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) AS collected, SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successful, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status IN ('failed','abandoned','reversed') THEN 1 ELSE 0 END) AS unsuccessful")->first();
        $stats = collect($aggregate->getAttributes())->map(fn ($value) => (int) $value)->all();
        $payments = $query->with('user')->latest()->paginate(20)->withQueryString();
        return view('admin.payments.index', compact('payments', 'stats', 'filters'));
    }

    public function export(Request $request)
    {
        [$query] = $this->filteredQuery($request);
        return Excel::download(new PaymentsExport($query), 'payments-'.today()->format('Y-m-d').'.xlsx');
    }

    private function filteredQuery(Request $request): array
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'purpose' => ['nullable', Rule::in(array_keys(Payment::LABELS))],
            'status' => 'nullable|in:pending,success,failed,abandoned,reversed',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);
        $query = Payment::query()
            ->when($filters['purpose'] ?? null, fn ($q, $value) => $q->where('purpose', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(function ($q) use ($value) {
                $q->where('reference', 'like', "%{$value}%")->orWhere('email', 'like', "%{$value}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$value}%"));
            }));
        return [$query, $filters];
    }
}
