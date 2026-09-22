@extends('layouts.dash')
@section('content')
<div class="container-fluid" style="margin-top:90px;padding:24px">
    <h2>@yield('heading', 'Academic records')</h2>
    <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Academic records">
        <a class="btn btn-outline-primary" href="{{ route('academic.index') }}">Results</a>
        @if(in_array(auth()->user()->dashboardRole(), ['admin', 'exam_officer', 'student']))
            <a class="btn btn-outline-primary" href="{{ route('academic.appeals') }}">Appeals</a>
            <a class="btn btn-outline-primary" href="{{ route('academic.transcripts') }}">Transcripts</a>
        @endif
        @if(\App\Services\Academic\ResultAccess::manager(auth()->user()))
            <a class="btn btn-outline-primary" href="{{ route('academic.policies') }}">Grading policies</a>
            <a class="btn btn-outline-primary" href="{{ route('academic.reports') }}">Academic reports</a>
        @endif
    </nav>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('academic-content')
</div>
@endsection
