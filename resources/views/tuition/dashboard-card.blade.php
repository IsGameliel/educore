<div class="card mb-4"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><h2 class="h4">Tuition & payments</h2><p class="text-muted mb-0">{{ $tuitionSession?->name ?? 'No active academic session' }}</p></div><a href="{{ route('tuition.index') }}" class="btn btn-outline-primary">View tuition & pay</a></div>
    @if($tuitionInvoices->isNotEmpty())
    @foreach($tuitionInvoices as $invoice) @include('tuition.reminder', ['invoice'=>$invoice]) @endforeach
    <div class="row">@foreach(['due'=>'Total tuition', 'paid'=>'Amount paid', 'balance'=>'Outstanding balance'] as $key=>$label)<div class="col-md-4"><p>{{ $label }}</p><h3 class="h4">₦{{ number_format($tuitionInvoices->sum(fn ($invoice) => $invoice->totals()[$key]) / 100, 2) }}</h3></div>@endforeach</div>
    @else<p>Tuition invoices will appear here when fees are published for your enrollment.</p>@endif
    @foreach($tuitionClearances as $semester=>$clearance)<p class="mb-1"><strong>{{ $semester }} semester:</strong> {{ $clearance['message'] }}</p>@endforeach
</div></div></div>
