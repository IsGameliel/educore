@extends('platform.layout')
@section('title', 'Audit Log | Educore')
@section('content')
<h1>Audit Log</h1><p>Platform actions recorded from this release onward. Secrets, passwords and recovery tokens are excluded. Times use {{ config('app.timezone') }}.</p>
<form method="get" class="card grid"><label>Event<select name="event"><option value="">All events</option>@foreach($eventNames as $event)<option @selected(request('event') === $event)>{{ $event }}</option>@endforeach</select></label>
<label>Administrator<select name="actor"><option value="">All administrators</option>@foreach($actors as $actor)<option value="{{ $actor->id }}" @selected((string) request('actor') === (string) $actor->id)>{{ $actor->name }}</option>@endforeach</select></label>
<label>Subject ID<input name="subject" value="{{ request('subject') }}"></label><label>From<input type="date" name="from" value="{{ request('from') }}"></label><label>To<input type="date" name="to" value="{{ request('to') }}"></label><div class="actions"><button>Filter events</button><a href="{{ route('platform.audit') }}">Clear</a></div></form>
<div class="card table-wrap"><table><thead><tr><th>Time / actor</th><th>Event / subject</th><th>Details</th></tr></thead><tbody>@forelse($events as $event)<tr><td>{{ $event->created_at }}<br>{{ $event->actor_name ?? 'Unauthenticated / system' }}<br>{{ $event->ip_address }}</td><td>{{ $event->event }}<br>{{ $event->subject }}</td><td><details><summary>View details</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ json_encode(json_decode($event->details), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details></td></tr>@empty<tr><td colspan="3">No matching events.</td></tr>@endforelse</tbody></table></div>{{ $events->links() }}
@endsection
