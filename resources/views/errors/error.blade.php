@extends('layouts.err')

@section('content')
        <div class="content">
            <h2>Error {{ $statusCode }}</h2>
            <p>{{ $exception->getMessage() ?: 'An unexpected error occurred.' }}</p>
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
        </div>
@endsection

