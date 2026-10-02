@extends('layouts.dash')

@section('content')
@php($routePrefix = auth()->user()->usertype === 'lecturer' ? 'lecturer' : 'admin')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Edit Questions
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Manage Questions <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Edit Questions Form -->
            <div class="card">
                <div class="card-body">
                    <h3>Edit Questions for: {{ $test->name }}</h3>
                    @include('admin.tests.question-feedback')
                    <ul class="list-group">
                            <li class="list-group-item mb-4">
                                <form action="{{ route($routePrefix.'.tests.questions.update', [$test->id, $question->id]) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="mb-3">
                                        <label for="question_text_{{ $question->id }}" class="form-label">Question</label>
                                        <textarea name="question_text" id="question_text_{{ $question->id }}" class="form-control" rows="3" required>{{ old('question_text', $question->question_text) }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Options</label>
                                        <div>
                                            @foreach ($question->options as $index => $option)
                                                <input type="text" name="options[]" class="form-control mb-2" value="{{ old('options.'.$index, $option) }}" placeholder="Option {{ $index + 1 }}" required>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="correct_option_{{ $question->id }}" class="form-label">Correct Option</label>
                                        <select name="correct_option" id="correct_option_{{ $question->id }}" class="form-control" required>
                                            @foreach($question->options as $index => $option)
                                                <option value="{{ $index }}" @selected((string) old('correct_option', $question->correct_option) === (string) $index)>Option {{ $index + 1 }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="marks_{{ $question->id }}" class="form-label">Marks</label>
                                        <input type="number" name="marks" id="marks_{{ $question->id }}" class="form-control" value="{{ old('marks', $question->marks) }}" min="1" required>
                                    </div>

                                    <button type="submit" class="btn btn-success">Update Question</button>
                                </form>
                                <form action="{{ route($routePrefix.'.tests.questions.delete', [$test->id, $question->id]) }}" method="POST" class="mt-2" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Delete Question</button>
                                </form>
                            </li>
                    </ul>
                </div>
            </div>
            </div>
        </div>
    </div>
@endsection
