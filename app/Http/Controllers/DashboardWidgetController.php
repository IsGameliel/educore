<?php

namespace App\Http\Controllers;

use App\Models\DashboardProject;
use App\Models\DashboardTodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardWidgetController extends Controller
{
    public function storeTodo(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
        ]);

        DashboardTodo::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
        ]);

        return back()->with('success', 'Task added.');
    }

    public function updateTodo(Request $request, DashboardTodo $todo)
    {
        $this->ensureOwnsTodo($todo);

        $todo->update([
            'completed' => $request->boolean('completed'),
        ]);

        return back();
    }

    public function destroyTodo(DashboardTodo $todo)
    {
        $this->ensureOwnsTodo($todo);
        $todo->delete();

        return back()->with('success', 'Task deleted.');
    }

    public function clearCompletedTodos()
    {
        DashboardTodo::where('user_id', Auth::id())
            ->where('completed', true)
            ->delete();

        return back()->with('success', 'Completed tasks cleared.');
    }

    public function storeProject(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'due_date' => ['nullable', 'date'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        DashboardProject::create($validated + [
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Project status added.');
    }

    public function updateProject(Request $request, DashboardProject $project)
    {
        $this->ensureOwnsProject($project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'due_date' => ['nullable', 'date'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $project->update($validated);

        return back()->with('success', 'Project status updated.');
    }

    public function destroyProject(DashboardProject $project)
    {
        $this->ensureOwnsProject($project);
        $project->delete();

        return back()->with('success', 'Project status deleted.');
    }

    protected function ensureOwnsTodo(DashboardTodo $todo): void
    {
        abort_unless((int) $todo->user_id === (int) Auth::id(), 403);
    }

    protected function ensureOwnsProject(DashboardProject $project): void
    {
        abort_unless((int) $project->user_id === (int) Auth::id(), 403);
    }
}
