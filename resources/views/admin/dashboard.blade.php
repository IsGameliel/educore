@extends('layouts.dash')

@section('content')
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                  <i class="mdi mdi-home"></i>
                </span> Dashboard
                    </h3>
                    <nav aria-label="breadcrumb">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item active" aria-current="page">
                                <span></span>Overview <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="row">
                    <div class="col-md-4 stretch-card grid-margin">
                        <div class="card bg-gradient-danger card-img-holder text-white">
                            <div class="card-body">
                                <img src="{{ asset('dash/assets/images/dashboard/circle.svg')}}" class="card-img-absolute" alt="circle-image" />
                                <h4 class="font-weight-normal mb-3">Students <i class="mdi mdi-account-group menu-icon mdi-24px float-end"></i>
                                </h4>
                                <h2 class="mb-5">{{ $studentsCount}}</h2>
                                <h6 class="card-text">Registered student accounts</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 stretch-card grid-margin">
                        <div class="card bg-gradient-info card-img-holder text-white">
                            <div class="card-body">
                                <img src="dash/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                <h4 class="font-weight-normal mb-3">Lecturers <i class="mdi mdi-school mdi-24px float-end"></i>
                                </h4>
                                <h2 class="mb-5">{{ $lecturersCount }}</h2>
                                <h6 class="card-text">Registered lecturer accounts</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 stretch-card grid-margin">
                        <div class="card bg-gradient-success card-img-holder text-white">
                            <div class="card-body">
                                <img src="dash/assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                <h4 class="font-weight-normal mb-3">Administrators <i class="mdi mdi-shield-account mdi-24px float-end"></i>
                                </h4>
                                <h2 class="mb-5">{{ $adminsCount }}</h2>
                                <h6 class="card-text">Accounts managing the platform</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-7 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <div class="clearfix">
                                    <h4 class="card-title float-start">App Visitor Statistics</h4>
                                    <div class="text-muted float-end">Total Accounts: {{ $totalUsersCount }}</div>
                                </div>
                                <canvas id="app-visitor-chart" class="mt-4"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Platform Overview</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                        <tr>
                                            <td>Total Users</td>
                                            <td class="text-end font-weight-bold">{{ $totalUsersCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Students</td>
                                            <td class="text-end font-weight-bold">{{ $studentsCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Lecturers</td>
                                            <td class="text-end font-weight-bold">{{ $lecturersCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Administrators</td>
                                            <td class="text-end font-weight-bold">{{ $adminsCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Departments</td>
                                            <td class="text-end font-weight-bold">{{ $departmentsCount }}</td>
                                        </tr>
                                        <tr>
                                            <td>Faculties</td>
                                            <td class="text-end font-weight-bold">{{ $facultyCount }}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 grid-margin" id="academic-sessions-panel">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                                    <div>
                                        <h4 class="card-title mb-1">Academic Sessions</h4>
                                        <p class="text-muted mb-0">Create and update sessions in the format <strong>2021/2022</strong>.</p>
                                    </div>
                                </div>

                                @if ($errors->has('name'))
                                    <div class="alert alert-danger">
                                        {{ $errors->first('name') }}
                                    </div>
                                @endif

                                <div class="row g-4">
                                    <div class="col-lg-4">
                                        <div class="border rounded p-3 h-100">
                                            <h5 class="mb-3">Create Session</h5>
                                            <form method="POST" action="{{ route('admin.academic-sessions.store') }}">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="session_name" class="form-label">Session</label>
                                                    <input
                                                        type="text"
                                                        name="name"
                                                        id="session_name"
                                                        class="form-control"
                                                        value="{{ old('name') }}"
                                                        placeholder="e.g. 2021/2022"
                                                        required
                                                    >
                                                    <small class="text-muted">Use consecutive years only.</small>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Create Session</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="col-lg-8">
                                        <div class="border rounded p-3 h-100">
                                            <h5 class="mb-3">Existing Sessions</h5>
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Session</th>
                                                        <th>Update</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @forelse ($academicSessions as $academicSession)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td class="fw-semibold">{{ $academicSession->name }}</td>
                                                            <td>
                                                                <form method="POST" action="{{ route('admin.academic-sessions.update', $academicSession) }}" class="row g-2 align-items-center">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <div class="col-md-8">
                                                                        <input
                                                                            type="text"
                                                                            name="name"
                                                                            class="form-control"
                                                                            value="{{ old('name_' . $academicSession->id, $academicSession->name) }}"
                                                                            placeholder="e.g. 2021/2022"
                                                                            required
                                                                        >
                                                                    </div>
                                                                    <div class="col-md-4 d-grid">
                                                                        <button type="submit" class="btn btn-outline-primary btn-sm">Save</button>
                                                                    </div>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3" class="text-center text-muted">No academic sessions created yet.</td>
                                                        </tr>
                                                    @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 grid-margin">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="card-title mb-0">Department Course Allocation</h4>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="text-muted small">{{ $departmentCourseSummary->count() }} departments</span>
                                        <button
                                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#department-course-allocation"
                                            aria-expanded="true"
                                            aria-controls="department-course-allocation"
                                        >
                                            <i class="mdi mdi-chevron-up" data-collapse-icon></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="collapse show" id="department-course-allocation">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th class="text-end">Assigned Courses</th>
                                            </tr>
                                            </thead>
                                            <tbody id="department-course-table-body">
                                            @forelse ($departmentCourseSummary as $department)
                                                <tr class="department-course-row">
                                                    <td>{{ $department->name }}</td>
                                                    <td class="text-end font-weight-bold">{{ $department->courses_count }}</td>
                                                </tr>
                                            @empty
                                                <tr id="department-course-empty-row">
                                                    <td colspan="2" class="text-center text-muted">No departments found.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if ($departmentCourseSummary->isNotEmpty())
                                        <div class="d-flex justify-content-between align-items-center mt-3 gap-3 flex-wrap">
                                            <small class="text-muted" id="department-course-pagination-status">
                                                Showing 1-1 of {{ $departmentCourseSummary->count() }} departments
                                            </small>
                                            <div class="d-flex gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    id="department-course-prev"
                                                >
                                                    Previous
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    id="department-course-next"
                                                >
                                                    Next
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 grid-margin">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="card-title mb-0">Recent Activity</h4>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="text-muted small">Result uploads and course registrations</span>
                                        <button
                                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#recent-activity-table"
                                            aria-expanded="true"
                                            aria-controls="recent-activity-table"
                                        >
                                            <i class="mdi mdi-chevron-up" data-collapse-icon></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="collapse show" id="recent-activity-table">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th> User </th>
                                                <th> Activity </th>
                                                <th> Details </th>
                                                <th> Status </th>
                                                <th> Time </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse ($recentActivities as $activity)
                                                <tr>
                                                    <td>{{ $activity['actor'] }}</td>
                                                    <td>{{ $activity['activity'] }}</td>
                                                    <td>{{ $activity['details'] }}</td>
                                                    <td>
                                                        <label class="badge badge-gradient-{{ $activity['status_color'] }}">
                                                            {{ $activity['status'] }}
                                                        </label>
                                                    </td>
                                                    <td>
                                                        @if ($activity['occurred_at'])
                                                            {{ $activity['occurred_at']->format('M j, Y g:i A') }}
                                                            <div class="text-muted small">{{ $activity['occurred_at']->diffForHumans() }}</div>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        No recent result uploads or course registrations found.
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-5 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body p-0 d-flex">
                                <div id="inline-datepicker" class="datepicker datepicker-custom"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Recent Updates</h4>
                                <div class="d-flex">
                                    <div class="d-flex align-items-center me-4 text-muted font-weight-light">
                                        <i class="mdi mdi-account-outline icon-sm me-2"></i>
                                        <span>jack Menqu</span>
                                    </div>
                                    <div class="d-flex align-items-center text-muted font-weight-light">
                                        <i class="mdi mdi-clock icon-sm me-2"></i>
                                        <span>October 3rd, 2018</span>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-6 pe-1">
                                        <img src="assets/images/dashboard/img_1.jpg" class="mb-2 mw-100 w-100 rounded" alt="image">
                                        <img src="assets/images/dashboard/img_4.jpg" class="mw-100 w-100 rounded" alt="image">
                                    </div>
                                    <div class="col-6 ps-1">
                                        <img src="assets/images/dashboard/img_2.jpg" class="mb-2 mw-100 w-100 rounded" alt="image">
                                        <img src="assets/images/dashboard/img_3.jpg" class="mw-100 w-100 rounded" alt="image">
                                    </div>
                                </div>
                                <div class="d-flex mt-5 align-items-top">
                                    <img src="assets/images/faces/face3.jpg" class="img-sm rounded-circle me-3" alt="image">
                                    <div class="mb-0 flex-grow">
                                        <h5 class="me-2 mb-2">School Website - Authentication Module.</h5>
                                        <p class="mb-0 font-weight-light">It is a long established fact that a reader will be distracted by the readable content of a page.</p>
                                    </div>
                                    <div class="ms-auto">
                                        <i class="mdi mdi-heart-outline text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-7 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Project Status</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                        <tr>
                                            <th> # </th>
                                            <th> Name </th>
                                            <th> Due Date </th>
                                            <th> Progress </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td> 1 </td>
                                            <td> Herman Beck </td>
                                            <td> May 15, 2015 </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-gradient-success" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td> 2 </td>
                                            <td> Messsy Adam </td>
                                            <td> Jul 01, 2015 </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-gradient-danger" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td> 3 </td>
                                            <td> John Richards </td>
                                            <td> Apr 12, 2015 </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-gradient-warning" role="progressbar" style="width: 90%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td> 4 </td>
                                            <td> Peter Meggik </td>
                                            <td> May 15, 2015 </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-gradient-primary" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td> 5 </td>
                                            <td> Edward </td>
                                            <td> May 03, 2015 </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-gradient-danger" role="progressbar" style="width: 35%" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td> 5 </td>
                                            <td> Ronald </td>
                                            <td> Jun 05, 2015 </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-gradient-info" role="progressbar" style="width: 65%" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title text-dark">Todo List</h4>
                                <div class="add-items d-flex mb-3">
                                    <input
                                        type="text"
                                        class="form-control todo-list-input"
                                        id="todo-list-input"
                                        placeholder="What do you need to do today?"
                                        maxlength="150"
                                    >
                                    <button class="add btn btn-gradient-primary font-weight-bold todo-list-add-btn" id="add-task" type="button">Add</button>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted" id="todo-summary">0 tasks (0 remaining)</small>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-completed-tasks">Clear completed</button>
                                </div>
                                <div class="list-wrapper">
                                    <ul class="d-flex flex-column-reverse todo-list todo-list-custom" id="todo-list"></ul>
                                    <div class="text-muted text-center py-3 d-none" id="todo-empty-state">
                                        No tasks yet. Add one to get started.
                                    </div>
                                </div>
                                <template id="todo-item-template">
                                    <li>
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input class="checkbox todo-checkbox" type="checkbox">
                                                <span class="todo-text"></span>
                                            </label>
                                        </div>
                                        <button type="button" class="remove border-0 bg-transparent p-0" aria-label="Delete task">
                                            <i class="mdi mdi-close-circle-outline"></i>
                                        </button>
                                    </li>
                                </template>
                                <div class="small text-muted mt-3">
                                    Your tasks are saved in this browser for your account.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- content-wrapper ends -->
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartCanvas = document.getElementById('app-visitor-chart');
            const todoStorageKey = 'educore.admin.todo.{{ auth()->id() ?? 'guest' }}';
            const todoInput = document.getElementById('todo-list-input');
            const todoAddButton = document.getElementById('add-task');
            const todoList = document.getElementById('todo-list');
            const todoTemplate = document.getElementById('todo-item-template');
            const todoEmptyState = document.getElementById('todo-empty-state');
            const todoSummary = document.getElementById('todo-summary');
            const clearCompletedButton = document.getElementById('clear-completed-tasks');
            const collapseTargets = [
                'department-course-allocation',
                'recent-activity-table',
            ];
            const departmentCourseRows = Array.from(document.querySelectorAll('.department-course-row'));
            const departmentCoursePrevButton = document.getElementById('department-course-prev');
            const departmentCourseNextButton = document.getElementById('department-course-next');
            const departmentCourseStatus = document.getElementById('department-course-pagination-status');
            const departmentCoursesPerPage = 6;
            let departmentCoursePage = 0;
            let todos = loadTodos();

            collapseTargets.forEach(function (targetId) {
                const collapseElement = document.getElementById(targetId);
                const collapseButton = document.querySelector('[data-bs-target="#' + targetId + '"]');
                const collapseIcon = collapseButton?.querySelector('[data-collapse-icon]');

                if (!collapseElement || !collapseButton || !collapseIcon) {
                    return;
                }

                collapseElement.addEventListener('show.bs.collapse', function () {
                    collapseIcon.classList.remove('mdi-chevron-down');
                    collapseIcon.classList.add('mdi-chevron-up');
                    collapseButton.setAttribute('aria-expanded', 'true');
                });

                collapseElement.addEventListener('hide.bs.collapse', function () {
                    collapseIcon.classList.remove('mdi-chevron-up');
                    collapseIcon.classList.add('mdi-chevron-down');
                    collapseButton.setAttribute('aria-expanded', 'false');
                });
            });

            function loadTodos() {
                try {
                    const storedTodos = window.localStorage.getItem(todoStorageKey);
                    const parsedTodos = storedTodos ? JSON.parse(storedTodos) : [];

                    return Array.isArray(parsedTodos) ? parsedTodos : [];
                } catch (error) {
                    return [];
                }
            }

            function saveTodos() {
                window.localStorage.setItem(todoStorageKey, JSON.stringify(todos));
            }

            function updateTodoSummary() {
                const completedCount = todos.filter(function (todo) {
                    return todo.completed;
                }).length;
                const remainingCount = todos.length - completedCount;
                const taskLabel = todos.length === 1 ? 'task' : 'tasks';

                if (todoSummary) {
                    todoSummary.textContent = todos.length + ' ' + taskLabel + ' (' + remainingCount + ' remaining)';
                }

                if (clearCompletedButton) {
                    clearCompletedButton.disabled = completedCount === 0;
                }
            }

            function renderTodos() {
                if (!todoList || !todoTemplate) {
                    return;
                }

                todoList.innerHTML = '';

                if (!todos.length) {
                    todoEmptyState?.classList.remove('d-none');
                    updateTodoSummary();
                    return;
                }

                todoEmptyState?.classList.add('d-none');

                todos.forEach(function (todo) {
                    const todoItemFragment = todoTemplate.content.cloneNode(true);
                    const todoItem = todoItemFragment.querySelector('li');
                    const checkbox = todoItemFragment.querySelector('.todo-checkbox');
                    const text = todoItemFragment.querySelector('.todo-text');
                    const removeButton = todoItemFragment.querySelector('.remove');

                    todoItem.dataset.todoId = todo.id;
                    todoItem.classList.toggle('completed', Boolean(todo.completed));
                    checkbox.checked = Boolean(todo.completed);
                    text.textContent = todo.text;

                    checkbox.addEventListener('change', function () {
                        todos = todos.map(function (item) {
                            if (item.id === todo.id) {
                                return {
                                    id: item.id,
                                    text: item.text,
                                    completed: checkbox.checked,
                                };
                            }

                            return item;
                        });

                        saveTodos();
                        renderTodos();
                    });

                    removeButton.addEventListener('click', function () {
                        todos = todos.filter(function (item) {
                            return item.id !== todo.id;
                        });

                        saveTodos();
                        renderTodos();
                    });

                    todoList.appendChild(todoItemFragment);
                });

                updateTodoSummary();
            }

            function addTodo() {
                if (!todoInput) {
                    return;
                }

                const value = todoInput.value.trim();

                if (!value) {
                    todoInput.focus();
                    return;
                }

                todos.push({
                    id: Date.now().toString(),
                    text: value,
                    completed: false,
                });

                saveTodos();
                renderTodos();
                todoInput.value = '';
                todoInput.focus();
            }

            todoAddButton?.addEventListener('click', addTodo);

            todoInput?.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addTodo();
                }
            });

            clearCompletedButton?.addEventListener('click', function () {
                todos = todos.filter(function (todo) {
                    return !todo.completed;
                });

                saveTodos();
                renderTodos();
            });

            renderTodos();
            renderDepartmentCourses();

            if (!chartCanvas) {
                return;
            }

            function renderDepartmentCourses() {
                if (!departmentCourseRows.length) {
                    return;
                }

                const totalPages = Math.ceil(departmentCourseRows.length / departmentCoursesPerPage);
                const startIndex = departmentCoursePage * departmentCoursesPerPage;
                const endIndex = Math.min(startIndex + departmentCoursesPerPage, departmentCourseRows.length);

                departmentCourseRows.forEach(function (row, index) {
                    row.classList.toggle('d-none', index < startIndex || index >= endIndex);
                });

                if (departmentCourseStatus) {
                    departmentCourseStatus.textContent = 'Showing ' + (startIndex + 1) + '-' + endIndex + ' of ' + departmentCourseRows.length + ' departments';
                }

                if (departmentCoursePrevButton) {
                    departmentCoursePrevButton.disabled = departmentCoursePage === 0;
                }

                if (departmentCourseNextButton) {
                    departmentCourseNextButton.disabled = departmentCoursePage >= totalPages - 1;
                }
            }

            departmentCoursePrevButton?.addEventListener('click', function () {
                if (departmentCoursePage === 0) {
                    return;
                }

                departmentCoursePage -= 1;
                renderDepartmentCourses();
            });

            departmentCourseNextButton?.addEventListener('click', function () {
                const totalPages = Math.ceil(departmentCourseRows.length / departmentCoursesPerPage);

                if (departmentCoursePage >= totalPages - 1) {
                    return;
                }

                departmentCoursePage += 1;
                renderDepartmentCourses();
            });

            const visitorStatistics = @json($visitorStatistics);
            const labels = visitorStatistics.map(function (item) {
                return item.label;
            });
            const counts = visitorStatistics.map(function (item) {
                return item.count;
            });
            const percentages = visitorStatistics.map(function (item) {
                return item.percentage;
            });

            new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Users',
                        data: counts,
                        backgroundColor: [
                            'rgba(254, 124, 150, 0.75)',
                            'rgba(54, 185, 204, 0.75)',
                            'rgba(28, 200, 138, 0.75)'
                        ],
                        borderColor: [
                            '#fe7c96',
                            '#36b9cc',
                            '#1cc88a'
                        ],
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 72
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const value = context.raw ?? 0;
                                    const percentage = percentages[context.dataIndex] ?? 0;
                                    return value + ' users (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.08)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
