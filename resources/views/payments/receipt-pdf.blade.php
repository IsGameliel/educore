<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>{{ $payment->receiptNumber() }}</title>
<style>body{font-family:DejaVu Sans,sans-serif;color:#18253b;font-size:12px;line-height:1.6}h1{font-size:25px;color:#194fb2}table{border-collapse:collapse;width:100%;margin-top:20px}td,th{padding:10px;border-bottom:1px solid #ddd;text-align:left}th{width:35%}.amount{font-size:24px;font-weight:bold}.note{margin-top:30px;color:#586477}</style></head><body>
<h1>{{ config('app.name', 'Euvion') }}</h1><h2>Payment receipt</h2><p>{{ $payment->receiptNumber() }}</p>
@if($payment->gateway_deduction || $payment->dispute_open || $payment->gateway_reversed)<p>This acknowledges the original payment. Verified refunds/reversals: NGN {{ number_format($payment->gateway_deduction / 100, 2) }}. {{ $payment->dispute_open ? 'A dispute is under review.' : '' }} Check the tuition invoice for current clearance.</p>@endif
<p class="amount">NGN {{ number_format($payment->amount/100,2) }}</p>
<table>
<tr><th>Student</th><td>{{ $payment->tuitionInvoice?->student_name ?? $payment->user->name }}</td></tr>
<tr><th>Email</th><td>{{ $payment->email }}</td></tr>
<tr><th>Payment for</th><td>{{ \App\Models\Payment::LABELS[$payment->purpose] }}</td></tr>
@if($payment->tuitionInvoice)<tr><th>Invoice</th><td>{{ $payment->tuitionInvoice->number }}</td></tr><tr><th>Academic session</th><td>{{ $payment->tuitionInvoice->session_name }} / {{ $payment->tuitionInvoice->period }}</td></tr><tr><th>Enrollment</th><td>{{ $payment->tuitionInvoice->department_name }} / {{ $payment->tuitionInvoice->level }} level</td></tr>@endif
<tr><th>Payment reference</th><td>{{ $payment->reference }}</td></tr>
<tr><th>Paystack transaction</th><td>{{ $payment->gateway_id }}</td></tr>
<tr><th>Payment date</th><td>{{ $payment->paid_at?->format('d M Y H:i') }}</td></tr>
<tr><th>Channel</th><td>{{ ucfirst($payment->channel ?? 'Paystack') }}</td></tr>
<tr><th>Status</th><td>Verified successful payment</td></tr>
</table>
<p class="note">This receipt acknowledges this payment only. Current outstanding balances, adjustments and registration clearance are available in your student portal.</p>
</body></html>
