@extends('layouts.dash')

@section('content')

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-home"></i>
                    </span> Departments
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            <span></span>View Department <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Registration Form -->
            <div class="card">
                <div class="card-body">
                    <h3>Manage Questions for: {{ $test->name }}</h3>

                    <form action="{{ route('admin.tests.questions.store', $test->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question</label>
                            <textarea name="question_text" id="question_text" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Options</label>
                            <div>
                                <input type="text" name="options[]" class="form-control mb-2" placeholder="Option 1" required>
                                <input type="text" name="options[]" class="form-control mb-2" placeholder="Option 2" required>
                                <input type="text" name="options[]" class="form-control mb-2" placeholder="Option 3" required>
                                <input type="text" name="options[]" class="form-control mb-2" placeholder="Option 4" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="correct_option" class="form-label">Correct Option (e.g., 1, 2, 3, 4)</label>
                            <input type="number" name="correct_option" id="correct_option" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="marks" class="form-label">Marks</label>
                            <input type="number" name="marks" id="marks" class="form-control" required>
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
                                    <p>Correct Answer: Option {{ $question->correct_option }}</p>
                                    <p>Marks: {{ $question->marks }}</p>
                                </div>

                                <!-- Edit Button -->
                                <a href="{{ route('admin.tests.questions.edit', [$test->id, $question->id]) }}"
                                class="btn btn-info btn-sm me-2">
                                    Edit
                                </a>

                                <!-- Delete Button -->
                                <form action="{{ route('admin.tests.questions.delete', [$test->id, $question->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this question?');">
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
