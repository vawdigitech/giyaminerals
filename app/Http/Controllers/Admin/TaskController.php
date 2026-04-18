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
        $projectQuery = Project::with(['sites', 'factories', 'tasks' => function ($q) use ($request) {
            $q->whereNull('parent_id')
              ->with(['allSubtasks' => function ($sq) use ($request) {
                  // Apply status/priority/search filters to all nested subtasks
                  if ($request->filled('status')) {
                      $sq->where('status', $request->status);
                  }
                  if ($request->filled('priority')) {
                      $sq->where('priority', $request->priority);
                  }
                  if ($request->filled('search')) {
                      $search = $request->search;
                      $sq->where(function ($ssq) use ($search) {
                          $ssq->where('name', 'like', "%{$search}%")
                              ->orWhere('code', 'like', "%{$search}%");
                      });
                  }
                  $sq->orderByRaw("CAST(SUBSTRING_INDEX(code, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(code, '.', -1) AS UNSIGNED)");
              }])
              ->withCount('subtasks');

            if ($request->filled('status')) {
                $q->where('status', $request->status);
            }
            if ($request->filled('priority')) {
                $q->where('priority', $request->priority);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%")
                       ->orWhere('section', 'like', "%{$search}%");
                });
            }
            $q->orderByRaw("CAST(SUBSTRING_INDEX(code, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(code, '.', -1) AS UNSIGNED)");
        }])->withCount('tasks');

        if ($request->filled('project_id')) {
            $projectQuery->where('id', $request->project_id);
        }

        // Only show projects that have matching tasks when filters are active
        if ($request->filled('status') || $request->filled('priority') || $request->filled('search')) {
            $projectQuery->whereHas('tasks', function ($q) use ($request) {
                if ($request->filled('status')) {
                    $q->where('status', $request->status);
                }
                if ($request->filled('priority')) {
                    $q->where('priority', $request->priority);
                }
                if ($request->filled('search')) {
                    $search = $request->search;
                    $q->where(function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%")
                           ->orWhere('section', 'like', "%{$search}%");
                    });
                }
            });
        }

        $grouped = $projectQuery->orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('tasks.index', compact('grouped', 'projects'));
    }

    public function create(Request $request)
    {
        $projects = Project::with(['sites', 'factories'])->orderBy('name')->get();
        $parentTasks = [];
        $projectLocations = [];

        if ($request->filled('project_id')) {
            $project = Project::with(['sites', 'factories'])->find($request->project_id);

            // Get all tasks in the project (not just master tasks)
            // Allow both master tasks and sub-tasks to be selected as parent
            $parentTasks = Task::where('project_id', $request->project_id)
                ->orderByRaw("CAST(SUBSTRING_INDEX(code, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(code, '.', -1) AS UNSIGNED)")
                ->get();

            // Get all locations for this project
            if ($project) {
                foreach ($project->sites as $site) {
                    $projectLocations[] = [
                        'type' => 'site',
                        'id' => $site->id,
                        'name' => $site->name . ' (Site)',
                    ];
                }
                foreach ($project->factories as $factory) {
                    $projectLocations[] = [
                        'type' => 'factory',
                        'id' => $factory->id,
                        'name' => $factory->name . ' (Factory)',
                    ];
                }
            }
        }

        return view('tasks.create', compact('projects', 'parentTasks', 'projectLocations'));
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
            'location_ids' => 'required|array|min:1',
            'location_ids.*' => 'required|string', // Format: "type:id" e.g. "site:1" or "factory:2"
        ]);

        // Set quoted_amount to 0 if not provided
        if (!isset($validated['quoted_amount']) || $validated['quoted_amount'] === '' || $validated['quoted_amount'] === null) {
            $validated['quoted_amount'] = 0;
        }

        // Ensure code is unique within the project
        $exists = Task::where('project_id', $validated['project_id'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'This task code already exists in the project.'])->withInput();
        }

        // Prevent circular references if parent_id is set
        if (isset($validated['parent_id']) && $validated['parent_id']) {
            $parentTask = Task::find($validated['parent_id']);
            if ($parentTask && $parentTask->project_id != $validated['project_id']) {
                return back()->withErrors(['parent_id' => 'Parent task must be from the same project.'])->withInput();
            }
        }

        $task = Task::create($validated);

        // Add task to selected locations
        foreach ($validated['location_ids'] as $locationString) {
            [$locationType, $locationId] = explode(':', $locationString);

            \App\Models\TaskLocation::create([
                'task_id' => $task->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'progress' => 0,
                'status' => 'pending',
                'labor_cost' => 0,
                'material_cost' => 0,
                'total_cost' => 0,
            ]);
        }

        // Initialize aggregated values for the newly created task
        $task->recalculateAggregatedCosts();

        // Update parent task progress and aggregated costs if this is a subtask
        if ($task->parent_id) {
            $task->parent->recalculateProgressFromSubtasks();
            $task->parent->recalculateAggregatedCosts();
        }

        // Update project status and progress
        $task->project->updateProgressFromTasks();
        $task->project->updateStatusFromTasks();

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task created successfully and added to selected locations. You can now add materials below.');
    }

    public function show(Task $task)
    {
        $task->load([
            'project.sites',
            'project.factories',
            'taskLocations',
            'parent',
            'subtasks.assignments.employee',
            'assignments.employee',
            'assignments.sessions',
            'workLogs.assignment.employee',
            'stockUsages.product',
            'stockUsages.stock',
            'progressPhotos.employee'
        ]);

        // For master tasks, recalculate aggregated costs from subtasks
        if ($task->hasSubtasks()) {
            $task->recalculateAggregatedCosts();
            $task->refresh();
        } else {
            // For leaf tasks only
            // Fix any negative hours in sessions and assignments
            $task->fixNegativeHours();

            // Fix any assignments with 0 hourly rate by syncing from employee
            $rateUpdated = false;
            foreach ($task->assignments as $assignment) {
                if ($assignment->syncHourlyRateFromEmployee()) {
                    $rateUpdated = true;
                }
            }

            // Recalculate costs if rates were updated
            if ($rateUpdated) {
                $task->recalculateCosts();
                $task->refresh();
            }
        }

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $task->load(['project.sites', 'project.factories', 'stockUsages.product']);

        $projects = Project::orderBy('name')->get();

        // Get all tasks in the project except the current task and its descendants
        // This prevents circular references
        $allTasks = Task::where('project_id', $task->project_id)
            ->where('id', '!=', $task->id)
            ->orderByRaw("CAST(SUBSTRING_INDEX(code, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(code, '.', -1) AS UNSIGNED)")
            ->get();

        // Filter out descendants to prevent circular references
        $parentTasks = $allTasks->filter(function($potentialParent) use ($task) {
            return !$task->isAncestorOf($potentialParent->id);
        });

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

        // Set quoted_amount to 0 if not provided
        if (!isset($validated['quoted_amount']) || $validated['quoted_amount'] === '' || $validated['quoted_amount'] === null) {
            $validated['quoted_amount'] = 0;
        }

        // Ensure code is unique within the project
        $exists = Task::where('project_id', $validated['project_id'])
            ->where('code', $validated['code'])
            ->where('id', '!=', $task->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'This task code already exists in the project.'])->withInput();
        }

        // Prevent circular references
        if (isset($validated['parent_id']) && $validated['parent_id']) {
            if ($task->wouldCreateCircularReference($validated['parent_id'])) {
                return back()->withErrors(['parent_id' => 'Invalid parent selection. This would create a circular reference (a task cannot be a parent of its own ancestor).'])->withInput();
            }
        }

        $task->update($validated);

        // Recalculate aggregated values for the updated task
        $task->recalculateAggregatedCosts();

        // Update parent task progress and aggregated costs if this is a subtask
        if ($task->parent_id) {
            $task->parent->recalculateProgressFromSubtasks();
            $task->parent->recalculateAggregatedCosts();
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

        // Update parent task progress and aggregated costs if this was a subtask
        if ($parent) {
            $parent->recalculateProgressFromSubtasks();
            $parent->recalculateAggregatedCosts();
        }

        // Update project status and progress after task deletion
        $project->updateProgressFromTasks();
        $project->updateStatusFromTasks();

        return redirect()->route('tasks.index', ['project_id' => $projectId])
            ->with('success', 'Task deleted successfully.');
    }

    // AJAX endpoint to get tasks by project
    // Returns all tasks (both master and sub-tasks) for parent selection
    public function getByProject(Project $project)
    {
        $tasks = $project->tasks()
            ->with('subtasks')
            ->orderByRaw("CAST(SUBSTRING_INDEX(code, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(code, '.', -1) AS UNSIGNED)")
            ->get();

        // Add a display_name attribute to show hierarchy
        $tasks->each(function($task) {
            $level = $task->getNestingLevel();
            $indent = str_repeat('  ', $level); // 2 spaces per level
            $prefix = $level > 0 ? '└─ ' : '';
            $task->display_name = $indent . $prefix . $task->code . ' - ' . $task->name;
        });

        return response()->json($tasks);
    }

    // AJAX endpoint to suggest the next task code
    public function suggestCode(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'parent_id'  => 'nullable|exists:tasks,id',
        ]);

        $projectId = $request->project_id;
        $parentId  = $request->parent_id;

        if ($parentId) {
            $parent     = Task::find($parentId);
            $parentCode = $parent->code; // e.g. "3.0"
            $baseNumber = explode('.', $parentCode)[0]; // "3"

            $lastSub = Task::where('project_id', $projectId)
                ->where('parent_id', $parentId)
                ->get()
                ->sortBy(function ($t) {
                    $parts = explode('.', $t->code);
                    return isset($parts[1]) ? (int) $parts[1] : 0;
                })
                ->last();

            if ($lastSub) {
                $parts   = explode('.', $lastSub->code);
                $lastNum = isset($parts[1]) ? (int) $parts[1] : 0;
                $nextCode = $baseNumber . '.' . ($lastNum + 1);
            } else {
                $nextCode = $baseNumber . '.1';
            }
        } else {
            $lastMaster = Task::where('project_id', $projectId)
                ->whereNull('parent_id')
                ->get()
                ->sortBy(function ($t) {
                    return (int) explode('.', $t->code)[0];
                })
                ->last();

            if ($lastMaster) {
                $lastNum  = (int) explode('.', $lastMaster->code)[0];
                $nextCode = ($lastNum + 1) . '.0';
            } else {
                $nextCode = '1.0';
            }
        }

        return response()->json(['code' => $nextCode]);
    }
}
