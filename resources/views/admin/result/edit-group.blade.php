@extends('layouts.dash')

@section('content')
@php($routePrefix = auth()->user()->usertype === 'lecturer' ? 'lecturer' : 'admin')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-home"></i>
                </span> Edit Results Group
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Edit Results for {{ $session }} {{ $semester }} <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Edit Group Form -->
        <div class="card">
            <div class="card-body">
                <div class="container">
                    <h2>Edit Results for {{ $students->find($user_id)->name ?? 'Student' }} ({{ $session }} {{ $semester }})</h2>
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route($routePrefix.'.results.updateGroup.bySemester', [$user_id, $semester, 'session' => $session, 'department_id' => $departmentId]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="department_id" value="{{ $departmentId }}">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Credit Unit</th>
                                        <th>CA</th>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Grade</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($results as $result)
                                    <tr>
                                        <td>
                                            <input type="text" name="results[{{ $result->id }}][course_code]" class="form-control" value="{{ $result->course_code }}" required>
                                        </td>
                                        <td>
                                            <input type="text" name="results[{{ $result->id }}][course_title]" class="form-control" value="{{ $result->course_title }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="results[{{ $result->id }}][credit_unit]" class="form-control" value="{{ $result->credit_unit }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="results[{{ $result->id }}][ca_score]" class="form-control score-part" data-result-id="{{ $result->id }}" data-type="ca" value="{{ old("results.{$result->id}.ca_score", $result->ca_score) }}" step="0.01" min="0" max="100">
                                        </td>
                                        <td>
                                            <input type="number" name="results[{{ $result->id }}][exam_score]" class="form-control score-part" data-result-id="{{ $result->id }}" data-type="exam" value="{{ old("results.{$result->id}.exam_score", $result->exam_score) }}" step="0.01" min="0" max="100">
                                        </td>
                                        <td>
                                            <input type="number" name="results[{{ $result->id }}][score]" class="form-control score-field" data-result-id="{{ $result->id }}" value="{{ old("results.{$result->id}.score", $result->score) }}" step="0.01" min="0" max="100">
                                        </td>
                                        <td>
                                            <input type="text" name="results[{{ $result->id }}][grade]" class="form-control" value="{{ $result->grade }}" readonly>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteResult('{{ $result->id }}', '{{ route($routePrefix.'.results.destroy', $result->id) }}', '{{ $user_id }}', '{{ addslashes($session) }}', '{{ $semester }}', '{{ $departmentId }}')">Delete</button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Update Results</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<script>
    async function deleteResult(id, url, userId, session, semester, departmentId) {
        if (!confirm('Are you sure you want to delete this result?')) return;

        const token = '{{ csrf_token() }}';

        try {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ user_id: userId, session: session, semester: semester, department_id: departmentId })
            });

            if (res.ok) {
                // reload the edit-group page to reflect changes
                location.reload();
            } else {
                const text = await res.text();
                alert('Failed to delete result: ' + text);
            }
        } catch (err) {
            alert('Error deleting result: ' + err.message);
        }
    }

    function updateGroupScore(resultId) {
        const caField = document.querySelector(`.score-part[data-result-id="${resultId}"][data-type="ca"]`);
        const examField = document.querySelector(`.score-part[data-result-id="${resultId}"][data-type="exam"]`);
        const scoreField = document.querySelector(`.score-field[data-result-id="${resultId}"]`);
        const hasCa = caField.value !== '';
        const hasExam = examField.value !== '';

        if (hasCa || hasExam) {
            const total = (parseFloat(caField.value || 0) + parseFloat(examField.value || 0)).toFixed(2);
            scoreField.value = total;
            scoreField.readOnly = true;
        } else {
            scoreField.readOnly = false;
        }
    }

    document.querySelectorAll('.score-part').forEach((input) => {
        input.addEventListener('input', function () {
            updateGroupScore(this.dataset.resultId);
        });

        updateGroupScore(input.dataset.resultId);
    });
</script>
@endsection
