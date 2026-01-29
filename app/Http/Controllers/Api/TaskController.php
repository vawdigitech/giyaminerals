<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Task::with(['project.site', 'parent', 'activeAssignments.employee']);

        // Supervisors only see tasks at their site
        if ($user->isSupervisor() && $user->site_id) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('site_id', $user->site_id);
            });
        }

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by section
        if ($request->has('section')) {
            $query->where('section', $request->section);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Only top-level tasks (no subtasks)
        if ($request->boolean('top_level_only', false)) {
            $query->whereNull('parent_id');
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('code')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    public function show(Task $task)
    {
        $task->load([
            'project.site',
            'parent',
            'subtasks.activeAssignments.employee',
            'activeAssignments.employee',
            'stockUsages.product',
        ]);

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    public function updateProgress(Request $request, Task $task)
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $task->progress = $validated['progress'];

        // Update status based on progress
        if ($task->progress >= 100) {
            $task->status = 'completed';
            $task->completed_date = now();
        } elseif ($task->progress > 0) {
            $task->status = 'in_progress';
            if (!$task->start_date) {
                $task->start_date = now();
            }
        }

        $task->save();

        // Update parent task progress if this is a subtask
        if ($task->parent_id) {
            $task->parent->recalculateProgressFromSubtasks();
        }

        // Update parent project progress and status
        $task->project->updateProgressFromTasks();
        $task->project->updateStatusFromTasks();

        return response()->json([
            'success' => true,
            'message' => 'Task progress updated',
            'data' => $task,
        ]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,on_hold',
        ]);

        $task->status = $validated['status'];

        if ($validated['status'] === 'completed') {
            $task->progress = 100;
            $task->completed_date = now();
        } elseif ($validated['status'] === 'in_progress' && !$task->start_date) {
            $task->start_date = now();
        }

        $task->save();

        // Update parent task progress if this is a subtask
        if ($task->parent_id) {
            $task->parent->recalculateProgressFromSubtasks();
        }

        // Update parent project progress and status
        $task->project->updateProgressFromTasks();
        $task->project->updateStatusFromTasks();

        return response()->json([
            'success' => true,
            'message' => 'Task status updated',
            'data' => $task,
        ]);
    }

    public function byProject(Project $project)
    {
        $tasks = $project->tasks()
            ->whereNull('parent_id')
            ->with(['subtasks.activeAssignments.employee', 'activeAssignments.employee'])
            ->orderBy('code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    public function sections()
    {
        // Return available sections
        $sections = [
            'electrical',
            'plumbing',
            'masonry',
            'painting',
            'carpentry',
            'roofing',
            'flooring',
            'tiling',
            'plastering',
            'other',
        ];

        return response()->json([
            'success' => true,
            'data' => $sections,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'section' => 'nullable|string|max:100',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed,on_hold',
            'quoted_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        // Check for unique code within project
        $existingTask = Task::where('project_id', $validated['project_id'])
            ->where('code', $validated['code'])
            ->first();

        if ($existingTask) {
            return response()->json([
                'success' => false,
                'message' => 'Task code already exists in this project',
            ], 422);
        }

        $task = Task::create($validated);

        // Update parent task progress if this is a subtask
        if ($task->parent_id) {
            $task->parent->recalculateProgressFromSubtasks();
        }

        // Update parent project progress and status
        $task->project->updateProgressFromTasks();
        $task->project->updateStatusFromTasks();

        $task->load(['project.site', 'parent', 'activeAssignments.employee']);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);
    }

    public function getParentTasks(Request $request)
    {
        $query = Task::whereNull('parent_id')
            ->with(['project']);

        // Filter by project if provided
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $tasks = $query->orderBy('code')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }
}
