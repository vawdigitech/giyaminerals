<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['project', 'parent']);

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Show only top-level tasks
        if ($request->boolean('top_level_only')) {
            $query->whereNull('parent_id');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('section', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('code')->paginate(20);
        $projects = Project::orderBy('name')->get();

        return view('tasks.index', compact('tasks', 'projects'));
    }

    public function create(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $parentTasks = [];

        if ($request->filled('project_id')) {
            $parentTasks = Task::where('project_id', $request->project_id)
                ->whereNull('parent_id')
                ->orderBy('code')
                ->get();
        }

        return view('tasks.create', compact('projects', 'parentTasks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'section' => 'nullable|string|max:100',
            'quoted_amount' => 'nullable|numeric|min:0',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:pending,in_progress,completed,on_hold',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Ensure code is unique within the project
        $exists = Task::where('project_id', $validated['project_id'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'This task code already exists in the project.'])->withInput();
        }

        $task = Task::create($validated);

        // Update parent task progress if this is a subtask
        if ($task->parent_id) {
            $task->parent->recalculateProgressFromSubtasks();
        }

        // Update project status and progress
        $task->project->updateProgressFromTasks();
        $task->project->updateStatusFromTasks();

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task created successfully. You can now add materials below.');
    }

    public function show(Task $task)
    {
        $task->load([
            'project.site',
            'parent',
            'subtasks',
            'assignments.employee',
            'workLogs.assignment.employee',
            'stockUsages.product',
            'stockUsages.stock',
            'progressPhotos.employee'
        ]);

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $task->load(['project.site', 'stockUsages.product']);

        $projects = Project::orderBy('name')->get();
        $parentTasks = Task::where('project_id', $task->project_id)
            ->whereNull('parent_id')
            ->where('id', '!=', $task->id)
            ->orderBy('code')
            ->get();

        return view('tasks.edit', compact('task', 'projects', 'parentTasks'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'section' => 'nullable|string|max:100',
            'quoted_amount' => 'nullable|numeric|min:0',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:pending,in_progress,completed,on_hold',
            'progress' => 'nullable|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'completed_date' => 'nullable|date',
        ]);

        // Ensure code is unique within the project
        $exists = Task::where('project_id', $validated['project_id'])
            ->where('code', $validated['code'])
            ->where('id', '!=', $task->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'This task code already exists in the project.'])->withInput();
        }

        // Prevent task from being its own parent
        if (isset($validated['parent_id']) && $validated['parent_id'] == $task->id) {
            return back()->withErrors(['parent_id' => 'A task cannot be its own parent.'])->withInput();
        }

        $task->update($validated);

        // Update parent task progress if this is a subtask
        if ($task->parent_id) {
            $task->parent->recalculateProgressFromSubtasks();
        }

        // Update project status and progress
        $task->project->updateProgressFromTasks();
        $task->project->updateStatusFromTasks();

        return redirect()->route('tasks.index', ['project_id' => $task->project_id])
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $projectId = $task->project_id;

        if ($task->subtasks()->exists()) {
            return redirect()->route('tasks.index', ['project_id' => $projectId])
                ->with('error', 'Cannot delete task with subtasks. Delete subtasks first.');
        }

        if ($task->assignments()->exists() || $task->workLogs()->exists()) {
            return redirect()->route('tasks.index', ['project_id' => $projectId])
                ->with('error', 'Cannot delete task with assignments or work logs.');
        }

        $project = $task->project;
        $parent = $task->parent;
        $task->delete();

        // Update parent task progress if this was a subtask
        if ($parent) {
            $parent->recalculateProgressFromSubtasks();
        }

        // Update project status and progress after task deletion
        $project->updateProgressFromTasks();
        $project->updateStatusFromTasks();

        return redirect()->route('tasks.index', ['project_id' => $projectId])
            ->with('success', 'Task deleted successfully.');
    }

    // AJAX endpoint to get tasks by project
    public function getByProject(Project $project)
    {
        $tasks = $project->tasks()
            ->whereNull('parent_id')
            ->with('subtasks')
            ->orderBy('code')
            ->get();

        return response()->json($tasks);
    }
}
