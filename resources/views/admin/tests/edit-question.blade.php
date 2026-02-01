@extends('layouts.dash')

@section('content')

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
                    <ul class="list-group">
                        @foreach ($test->questions as $question)
                            <li class="list-group-item mb-4">
                                <form action="{{ route('admin.tests.questions.update', [$test->id, $question->id]) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="mb-3">
                                        <label for="question_text_{{ $question->id }}" class="form-label">Question</label>
                                        <textarea name="question_text" id="question_text_{{ $question->id }}" class="form-control" rows="3" required>{{ $question->question_text }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Options</label>
                                        <div>
                                            @foreach ($question->options as $index => $option)
                                                <input type="text" name="options[]" class="form-control mb-2" value="{{ $option }}" placeholder="Option {{ $index + 1 }}" required>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="correct_option_{{ $question->id }}" class="form-label">Correct Option (e.g., 1, 2, 3, 4)</label>
                                        <input type="number" name="correct_option" id="correct_option_{{ $question->id }}" class="form-control" value="{{ $question->correct_option }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="marks_{{ $question->id }}" class="form-label">Marks</label>
                                        <input type="number" name="marks" id="marks_{{ $question->id }}" class="form-control" value="{{ $question->marks }}" required>
                                    </div>

                                    <button type="submit" class="btn btn-success">Update Question</button>
                                    <a href="{{ route('admin.tests.questions.delete', [$test->id, $question->id]) }}" class="btn btn-danger">Delete Question</a>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            </div>
        </div>
    </div>
@endsection
