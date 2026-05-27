@extends('layouts.dash')

@section('content')
    <div class="container">
        <h2>My Results</h2>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Session</th>
                    <th>Semester</th>
                    <th>Level</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Group results by session + semester + level
                    $groupedResults = $results->groupBy(fn($item) => $item->session.'-'.$item->semester.'-'.$item->level);
                @endphp

                @forelse ($groupedResults as $group)
                    @php $first = $group->first(); @endphp
                    <tr>
                        <td>{{ $first->session }}</td>
                        <td>{{ $first->semester }}</td>
                        <td>{{ $first->level }}</td>
                        <td>
                            <a href="{{ route('student.results.show', [$first->user_id, urlencode($first->session), $first->semester]) }}" 
                               class="btn btn-sm btn-primary">
                               View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No results found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
