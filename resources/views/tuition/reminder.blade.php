@php($notice = \App\Services\TuitionReminders::notice($invoice))
@if($notice)
<div class="alert {{ $notice['kind'] === 'overdue' ? 'alert-warning' : 'alert-info' }}">
<strong>{{ $notice['label'] }}</strong>: ₦{{ number_format($notice['amount']/100, 2) }} remaining for {{ $notice['date'] }}.
<a href="{{ route('tuition.show', $invoice) }}">View invoice {{ $invoice->number }}</a>
</div>
@endif
