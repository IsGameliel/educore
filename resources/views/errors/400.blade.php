@extends('layouts.err')

@section('content')
        <div class="content">
            <h2>400 Bad Request</h2>
            <p>{{ $exception->getMessage() ?: 'The request could not be processed due to invalid input.' }}</p>
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
        </div>
@endsection
