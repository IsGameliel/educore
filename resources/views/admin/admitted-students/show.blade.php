@extends('layouts.dash')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header flex-wrap gap-3">
            <h3 class="page-title">Student Admission Details</h3>
            <a class="btn btn-outline-primary" href="{{ route('admin.admitted-students.index') }}">Back to admitted students</a>
        </div>
        <div class="card mb-4"><div class="card-body d-flex align-items-center gap-3 flex-wrap">
            @if ($application->user)
                <img src="{{ $application->user->profile_photo_url }}" alt="Student profile photo" width="72" height="72" class="rounded-circle" style="object-fit: cover;">
            @endif
            <div>
                <h4 class="mb-2">{{ $application->first_name }} {{ $application->middle_name }} {{ $application->surname }}</h4>
                <p class="text-muted mb-2">{{ $application->application_number }}</p>
                <span class="badge badge-gradient-success">Admission completed</span>
            </div>
        </div></div>
        @php
            $sections = [
                'Personal & Contact Details' => [
                    'Surname' => $application->surname,
                    'First name' => $application->first_name,
                    'Middle name' => $application->middle_name,
                    'Date of birth' => $application->date_of_birth?->format('d M Y'),
                    'Gender' => $application->gender,
                    'Email' => $application->user?->email,
                    'Phone' => $application->phone,
                    'Address' => $application->address,
                    'City' => $application->city,
                    'State' => $application->state,
                    'Country' => $application->country,
                ],
                'Parent / Guardian Details' => [
                    'Name' => $application->parent_name,
                    'Relationship' => $application->parent_relationship,
                    'Phone' => $application->parent_phone,
                    'Email' => $application->parent_email,
                    'Address' => $application->parent_address,
                ],
                'Admission & Student Record' => [
                    'Application number' => $application->application_number,
                    'Admission type' => $types[$application->admission_type] ?? $application->admission_type,
                    'Admission faculty' => $application->department?->faculty?->name,
                    'Admission department' => $application->department?->name,
                    'Entry level' => $application->level,
                    'Entry year' => $application->entry_year,
                    'Admission completed at' => $application->completed_at?->format('d M Y, h:i A'),
                    'Matric number' => $application->user?->matric_number,
                    'Current department' => $application->user?->department?->name,
                    'Current level' => $application->user?->level,
                ],
                'Previous Education' => [
                    'Previous school' => $application->previous_school,
                    'Qualification' => $application->qualification,
                    'Graduation year' => $application->graduation_year,
                    'Academic notes' => $application->academic_notes,
                ],
            ];
        @endphp
        <div class="row">
            @foreach ($sections as $title => $fields)
                <div class="col-lg-6 mb-4"><div class="card h-100"><div class="card-body">
                    <h4 class="card-title">{{ $title }}</h4>
                    <dl class="row mb-0">
                        @foreach ($fields as $label => $value)
                            <dt class="col-sm-5 text-muted mb-2">{{ $label }}</dt>
                            <dd class="col-sm-7 mb-3 text-break" style="white-space: pre-line;">{{ filled($value) ? $value : 'Not provided' }}</dd>
                        @endforeach
                    </dl>
                </div></div></div>
            @endforeach
        </div>
        <div class="card mb-4"><div class="card-body">
            <h4 class="card-title">Olevel Results</h4>
            @if ($application->olevel_results)
                <div class="table-responsive"><table class="table">
                    <thead><tr><th>Subject</th><th>Grade</th></tr></thead>
                    <tbody>
                        @foreach ($application->olevel_results as $result)
                            <tr><td>{{ $result['subject'] ?? '—' }}</td><td>{{ $result['grade'] ?? '—' }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            @else
                <p class="text-muted mb-0">No Olevel results recorded.</p>
            @endif
        </div></div>
        <div class="card"><div class="card-body">
            <h4 class="card-title">Admission Documents</h4>
            <div class="row g-3">
                @foreach ($documents as $key => $document)
                    <div class="col-md-4"><div class="border rounded p-3 h-100">
                        <h5>{{ $document['label'] }}</h5>
                        @if ($document['available'])
                            <p class="text-success small">Available</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('admin.admitted-students.document', [$application, $key]) }}">View<span class="visually-hidden"> {{ $document['label'] }} (opens in a new tab)</span></a>
                                <a class="btn btn-sm btn-light" href="{{ route('admin.admitted-students.document', [$application, $key, 'download' => 1]) }}">Download<span class="visually-hidden"> {{ $document['label'] }}</span></a>
                            </div>
                        @else
                            <p class="text-muted mb-0">{{ $application->{$key.'_path'} ? 'File unavailable' : 'Not uploaded' }}</p>
                        @endif
                    </div></div>
                @endforeach
            </div>
        </div></div>
    </div>
</div>
</div>
@endsection
