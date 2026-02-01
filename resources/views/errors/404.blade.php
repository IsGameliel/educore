@extends('layouts.err')

@section('content')
        <div class="content">
            <h2>404 Not Found</h2>
            <p>{{ $exception->getMessage() ?: 'The requested resource was not found.' }}</p>
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
        </div>
@endsection
