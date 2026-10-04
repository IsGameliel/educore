@extends('finance.layout')
@section('heading', 'Tuition reminder history')
@section('finance-content')
<p>Dashboard notices appear automatically. Email reminders run daily at 08:00 ({{ config('app.timezone') }}): within 7 days before a deadline, on the due date, then weekly while overdue. Amounts and invoice status are checked again before sending.</p>
<p>Email reminders: <strong>{{ config('tuition.email_reminders') ? 'Enabled' : 'Disabled' }}</strong>. Queued messages need a running worker. A “sent” entry means the mail transport accepted the message; it does not confirm inbox delivery.</p>
<div class="card card-body table-responsive"><table class="table"><thead><tr><th>Student / invoice</th><th>Reminder</th><th>Status</th><th>Queued</th><th>Sent</th></tr></thead><tbody>
@forelse($reminders as $reminder)<tr><td>{{ $reminder->invoice->student_name }}<br><a href="{{ route('finance.invoices.show', $reminder->invoice) }}">{{ $reminder->invoice->number }}</a></td><td>{{ $reminder->label() }}</td><td>{{ ucfirst($reminder->status) }}</td><td>{{ $reminder->queued_at }}</td><td>{{ $reminder->sent_at }}</td></tr>@empty<tr><td colspan="5">No email reminders have been queued yet.</td></tr>@endforelse
</tbody></table>{{ $reminders->links() }}</div></div>
@endsection
