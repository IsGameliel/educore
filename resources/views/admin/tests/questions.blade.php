@extends('layouts.dash')

@section('content')
@php($routePrefix = auth()->user()->usertype === 'lecturer' ? 'lecturer' : 'admin')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Manage Questions
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>Manage Questions <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">
                    <h3>Manage Questions for: {{ $test->name }}</h3>
                    @include('admin.tests.question-feedback')

                    <form action="{{ route($routePrefix.'.tests.questions.store', $test->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question</label>
                            <textarea name="question_text" id="question_text" class="form-control" rows="3" required>{{ old('question_text') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Options</label>
                            <div>
                                @foreach(range(0, 3) as $index)
                                    <input type="text" name="options[]" class="form-control mb-2" placeholder="Option {{ $index + 1 }}" value="{{ old('options.'.$index) }}" required>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="correct_option" class="form-label">Correct Option</label>
                            <select name="correct_option" id="correct_option" class="form-control" required>
                                <option value="">Choose the correct option</option>
                                @foreach(range(0, 3) as $index)
                                    <option value="{{ $index }}" @selected((string) old('correct_option', '') === (string) $index)>Option {{ $index + 1 }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="marks" class="form-label">Marks</label>
                            <input type="number" name="marks" id="marks" class="form-control" value="{{ old('marks') }}" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Question</button>
                    </form>

                    <h3 class="mt-5">Existing Questions</h3>
                    <ul class="list-group">
                        @foreach ($test->questions as $question)
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">{{ $loop->iteration }}. {{ $question->question_text }}</div>
                                    <ul>
                                        @foreach ($question->options as $index => $option)
                                            <li>{{ $index + 1 }}. {{ $option }}</li>
                                        @endforeach
                                    </ul>
                                    <p>Correct Answer: Option {{ (int) $question->correct_option + 1 }}</p>
                                    <p>Marks: {{ $question->marks }}</p>
                                </div>

                                <!-- Edit Button -->
                                <a href="{{ route($routePrefix.'.tests.questions.edit', [$test->id, $question->id]) }}"
                                class="btn btn-info btn-sm me-2">
                                    Edit
                                </a>

                                <!-- Delete Button -->
                                <form action="{{ route($routePrefix.'.tests.questions.delete', [$test->id, $question->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
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
