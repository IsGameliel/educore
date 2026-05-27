@extends('layouts.dash')

@section('content')
    @php($routePrefix = auth()->user()->usertype === 'lecturer' ? 'lecturer' : 'admin')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="page-header">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-upload"></i>
                    </span>
                    Upload Results
                </h3>
                <nav aria-label="breadcrumb">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            Sheet format upload
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Upload Course Result Sheet</h4>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route($routePrefix.'.results.storeUpload') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label>Courses</label>
                            @php($selectedCourseIds = collect(old('course_ids', []))->map(fn ($id) => (int) $id))
                            <input
                                type="text"
                                id="course-search"
                                class="form-control mb-2"
                                placeholder="Search course code, title, semester, level, or department"
                                autocomplete="off"
                            >
                            <div class="result-course-checklist" id="course_ids">
                                @foreach ($courses as $course)
                                    <label
                                        class="result-course-checklist__item"
                                        for="course_{{ $course->id }}"
                                        data-search="{{ strtolower($course->code . ' ' . $course->title . ' ' . $course->semester . ' ' . $course->level . ' ' . ($course->department->name ?? '')) }}"
                                    >
                                        <input
                                            type="checkbox"
                                            name="course_ids[]"
                                            id="course_{{ $course->id }}"
                                            value="{{ $course->id }}"
                                            class="result-course-checkbox"
                                            {{ $selectedCourseIds->contains($course->id) ? 'checked' : '' }}
                                        >
                                        <span>
                                            {{ $course->code }} - {{ $course->title }} ({{ $course->semester }} Semester, {{ $course->level }} Level, {{ $course->department->name ?? 'No Department' }})
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <small class="text-muted">Tick one or more courses. The template will create one worksheet per selected course in the same order shown here.</small>
                        </div>

                        <div class="form-group mt-3">
                            <label for="session">Academic Session</label>
                            <select name="session" id="session" class="form-control" required>
                                <option value="">Select Session</option>
                                @foreach ($academicSessions as $academicSession)
                                    <option value="{{ $academicSession }}" {{ old('session') === $academicSession ? 'selected' : '' }}>
                                        {{ $academicSession }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label for="semester">Semester</label>
                            <select name="semester" id="semester" class="form-control" required>
                                <option value="">Select Semester</option>
                                <option value="First" {{ old('semester') === 'First' ? 'selected' : '' }}>First</option>
                                <option value="Second" {{ old('semester') === 'Second' ? 'selected' : '' }}>Second</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="file">Upload Result File</label>
                            <input type="file" name="file" id="file" class="form-control" accept=".csv,.xls,.xlsx" required>
                            <small class="text-muted">Accepted formats: CSV, XLS, XLSX</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Upload Sheet</button>
                        <a href="{{ route($routePrefix.'.results.template.download') }}" class="btn btn-outline-success ms-2" id="download-template-link">
                            Download Excel Template
                        </a>
                    </form>

                    <div class="mt-4 p-3 border rounded bg-light">
                        <h5 class="mb-3">Expected Sheet Pattern</h5>
                        <p class="mb-2">The file should follow the printed result-sheet layout in your sample:</p>
                        <ul class="mb-3 ps-3">
                            <li>Select multiple courses if you want one workbook with multiple worksheets.</li>
                            <li>Each selected course is placed on a separate worksheet.</li>
                            <li>Top section can remain the same as your current sheet format.</li>
                            <li>The result table heading must start on <strong>row 8</strong> of each worksheet.</li>
                            <li>All sheet entries start from <strong>column A</strong>.</li>
                            <li>Result table headers: <strong>S/NO</strong>, <strong>MATRIC NO.</strong>, <strong>NAME</strong>, <strong>CA</strong>, <strong>EXAM</strong>, <strong>Total</strong>.</li>
                            <li>The <strong>NAME</strong> column is optional and can be left blank; student records are matched by matric number.</li>
                            <li>Enter the academic session and semester on this page before uploading.</li>
                            <li>The selected course order should match the worksheet order in the uploaded workbook.</li>
                        </ul>

                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>S/NO</th>
                                        <th>MATRIC NO.</th>
                                        <th>NAME</th>
                                        <th>CA</th>
                                        <th>EXAM</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>MUI/SBMS/NS/24/0001</td>
                                        <td></td>
                                        <td>18</td>
                                        <td>30</td>
                                        <td>48</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>MUI/SBMS/NS/24/0002</td>
                                        <td></td>
                                        <td>22</td>
                                        <td>38</td>
                                        <td>60</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const courseInputs = Array.from(document.querySelectorAll('.result-course-checkbox'));
            const courseItems = Array.from(document.querySelectorAll('.result-course-checklist__item'));
            const downloadLink = document.getElementById('download-template-link');
            const searchInput = document.getElementById('course-search');

            if (!courseInputs.length || !downloadLink) {
                return;
            }

            const baseHref = downloadLink.getAttribute('href');

            function updateTemplateLink() {
                const selectedCourses = courseInputs.filter(function (input) {
                    return input.checked;
                }).map(function (input) {
                    return input.value;
                });
                const url = new URL(baseHref, window.location.origin);

                selectedCourses.forEach(function (courseId) {
                    url.searchParams.append('course_ids[]', courseId);
                });

                downloadLink.href = url.toString();
            }

            courseInputs.forEach(function (input) {
                input.addEventListener('change', updateTemplateLink);
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const term = searchInput.value.trim().toLowerCase();

                    courseItems.forEach(function (item) {
                        const matches = term === '' || (item.dataset.search || '').includes(term);
                        item.classList.toggle('d-none', !matches);
                    });
                });
            }

            downloadLink.addEventListener('click', function (event) {
                const hasSelection = courseInputs.some(function (input) {
                    return input.checked;
                });

                if (!hasSelection) {
                    event.preventDefault();
                    window.alert('Select at least one course before downloading the template.');
                }
            });

            updateTemplateLink();
        });
    </script>
@endsection

@push('styles')
    <style>
        .result-course-checklist {
            min-height: 220px;
            max-height: 280px;
            overflow-y: auto;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            background: #fff;
        }

        .result-course-checklist__item {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            padding: 0.45rem 0;
            margin: 0;
            border-bottom: 1px solid #f1f3f5;
            cursor: pointer;
        }

        .result-course-checklist__item:last-child {
            border-bottom: 0;
        }

        .result-course-checklist__item input {
            margin-top: 0.2rem;
        }
    </style>
@endpush
