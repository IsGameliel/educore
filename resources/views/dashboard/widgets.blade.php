@php
    $dashboardTodos = $dashboardTodos ?? collect();
    $dashboardProjects = $dashboardProjects ?? collect();
    $completedTodos = $dashboardTodos->where('completed', true)->count();
    $remainingTodos = $dashboardTodos->count() - $completedTodos;
@endphp

<div class="row">
    <div class="col-md-7 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="card-title mb-0">Project Status</h4>
                    <span class="text-muted small">{{ $dashboardProjects->count() }} active item(s)</span>
                </div>

                <form method="POST" action="{{ route('dashboard.widgets.projects.store') }}" class="row g-2 align-items-end mb-4">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" maxlength="120" placeholder="e.g. Complete course registration" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Due Date</label>
                        <input type="date" name="due_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Progress</label>
                        <input type="number" name="progress" class="form-control form-control-sm" min="0" max="100" value="0" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-gradient-primary btn-sm">Add</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Due Date</th>
                                <th>Progress</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dashboardProjects as $project)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td style="min-width: 190px;">
                                        <form id="project-update-{{ $project->id }}" method="POST" action="{{ route('dashboard.widgets.projects.update', $project) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $project->name }}" maxlength="120" required>
                                        </form>
                                    </td>
                                    <td style="min-width: 150px;">
                                        <input form="project-update-{{ $project->id }}" type="date" name="due_date" class="form-control form-control-sm" value="{{ optional($project->due_date)->format('Y-m-d') }}">
                                    </td>
                                    <td style="min-width: 190px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <input form="project-update-{{ $project->id }}" type="number" name="progress" class="form-control form-control-sm" min="0" max="100" value="{{ $project->progress }}" style="max-width: 80px;" required>
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <div class="progress-bar bg-gradient-{{ $project->progress >= 80 ? 'success' : ($project->progress >= 40 ? 'warning' : 'danger') }}" role="progressbar" style="width: {{ $project->progress }}%" aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button form="project-update-{{ $project->id }}" type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                        <form method="POST" action="{{ route('dashboard.widgets.projects.destroy', $project) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this project status?')">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No project status items yet.</td>
                                </tr>
                            @endforelse
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
                <form method="POST" action="{{ route('dashboard.widgets.todos.store') }}" class="add-items d-flex mb-3">
                    @csrf
                    <input type="text" name="title" class="form-control todo-list-input" placeholder="What do you need to do today?" maxlength="150" required>
                    <button class="add btn btn-gradient-primary font-weight-bold todo-list-add-btn" type="submit">Add</button>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">{{ $dashboardTodos->count() }} task(s), {{ $remainingTodos }} remaining</small>
                    <form method="POST" action="{{ route('dashboard.widgets.todos.clearCompleted') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-secondary" {{ $completedTodos === 0 ? 'disabled' : '' }}>Clear completed</button>
                    </form>
                </div>

                <div class="list-wrapper">
                    <ul class="d-flex flex-column-reverse todo-list todo-list-custom">
                        @forelse($dashboardTodos as $todo)
                            <li class="{{ $todo->completed ? 'completed' : '' }}">
                                <form method="POST" action="{{ route('dashboard.widgets.todos.update', $todo) }}" class="form-check">
                                    @csrf
                                    @method('PATCH')
                                    <label class="form-check-label">
                                        <input class="checkbox" type="checkbox" name="completed" value="1" {{ $todo->completed ? 'checked' : '' }} onchange="this.form.submit()">
                                        {{ $todo->title }}
                                    </label>
                                </form>
                                <form method="POST" action="{{ route('dashboard.widgets.todos.destroy', $todo) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="remove border-0 bg-transparent p-0" aria-label="Delete task">
                                        <i class="mdi mdi-close-circle-outline"></i>
                                    </button>
                                </form>
                            </li>
                        @empty
                            <li class="d-block text-center text-muted py-3">No tasks yet. Add one to get started.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="small text-muted mt-3">Your tasks and project statuses are saved to your account.</div>
            </div>
        </div>
    </div>
</div>
