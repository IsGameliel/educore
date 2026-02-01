@extends('layouts.err')

@section('content')
        <div class="content">
            <h2>403 Not Found</h2>
            <p>Unauthorized</p>
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
        </div>
@endsection
