<div class="card mb-4"><div class="card-body">
    @if($totals['reversal_review'])<div class="alert alert-warning">Paystack reports a reversal. Its exact amount is being verified; checkout and clearance are on hold pending bursary review.</div>@endif
    @if($totals['refund_review'])<div class="alert alert-warning">This balance is provisional: a historical refund must be matched to its Paystack record by the bursary. Further checkout is paused to prevent a duplicate payment.</div>@endif
    @if($totals['dispute_open'])<div class="alert alert-warning">A payment dispute is under review. Registration clearance is on hold unless an approved exemption applies. Contact the bursary before paying the disputed amount again.</div>@endif
    <h2 class="h4">{{ $invoice->number }}</h2>
    <p>{{ $invoice->student_name }} · {{ $invoice->department_name }} · {{ $invoice->level }} level · {{ ucfirst($invoice->category) }} student</p>
    <p><strong>{{ $invoice->session_name }} / {{ $invoice->period }}</strong> · Due {{ $invoice->due_date->format('d M Y') }}
    @if($invoice->second_due_date) · Remaining balance due {{ $invoice->second_due_date->format('d M Y') }}@endif</p>
    <div class="table-responsive"><table class="table"><thead><tr><th>Fee breakdown</th><th>Amount</th></tr></thead><tbody>
        @foreach($invoice->items as $item)<tr><td>{{ $item['label'] }}</td><td>₦{{ number_format($item['amount'] / 100, 2) }}</td></tr>@endforeach
        <tr><th>Scholarships / waivers</th><td>− ₦{{ number_format($totals['credits'] / 100, 2) }}</td></tr>
        <tr><th>Adjusted tuition</th><td>₦{{ number_format($totals['due'] / 100, 2) }}</td></tr>
        <tr><th>Net payments received</th><td>₦{{ number_format($totals['paid'] / 100, 2) }}</td></tr>
        <tr><th>Outstanding balance</th><td><strong>₦{{ number_format($totals['balance'] / 100, 2) }}</strong></td></tr>
    </tbody></table></div>
    @if($totals['refunds'])<p>Refunds / reversals: ₦{{ number_format($totals['refunds'] / 100, 2) }}.</p>@endif
    @if($totals['overpayment'])<div class="alert alert-info">Credit balance: ₦{{ number_format($totals['overpayment'] / 100, 2) }}. Contact the bursary to arrange a refund.</div>@endif
    @if($invoice->cancelled_at)<p>Cancelled invoice: the original fee breakdown is retained for reference. No payment is due on this invoice.</p>
    @elseif($invoice->period === 'Annual' && $invoice->first_percent < 100)<p>Installment policy: {{ $invoice->first_percent }}% of adjusted tuition for First semester; 100% cumulatively for Second semester.</p>@else<p>Full payment is required for the applicable semester's registration.</p>@endif
</div></div>
