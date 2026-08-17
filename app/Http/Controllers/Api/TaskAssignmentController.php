<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Employee;
use App\Models\TaskAssignment;
use App\Services\WorkSessionService;
use Illuminate\Http\Request;

class TaskAssignmentController extends Controller
{
    protected WorkSessionService $workSessionService;

    public function __construct(WorkSessionService $workSessionService)
    {
        $this->workSessionService = $workSessionService;
    }

    public function index(Request $request)
    {
        $query = TaskAssignment::with(['task.project', 'task.factory', 'employee']);

        // Filter by task
        if ($request->has('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by work location (site or factory)
        if ($request->has('location_type') && $request->has('location_id')) {
            $query->where('location_type', $request->location_type)
                  ->where('location_id', $request->location_id);
        }

        // Filter active only
        if ($request->boolean('active_only', true)) {
            $query->whereNull('removed_at');
        }

        $assignments = $query->orderBy('assigned_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'location_type' => 'required|in:site,factory',
            'location_id' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $task = Task::findOrFail($validated['task_id']);

        // Validate that task can be assigned (must not have subtasks)
        try {
            $task->validateCanAssign();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $assignments = [];
        $sessionsStarted = 0;

        foreach ($validated['employee_ids'] as $employeeId) {
            // Check if already assigned
            $existing = TaskAssignment::where('task_id', $task->id)
                ->where('employee_id', $employeeId)
                ->whereNull('removed_at')
                ->first();

            if ($existing) {
                continue; // Skip if already assigned
            }

            $employee = Employee::findOrFail($employeeId);

            // Always use the location selected in the app (never fall back to another site)
            $location_type = $validated['location_type'];
            $location_id = $validated['location_id'];

            // Get today's attendance to determine appropriate rate
            $todayAttendance = $employee->todayAttendance()->first();

            // If no attendance today, create a mock attendance based on assignment location
            // to ensure we get the correct rate (factory vs site)
            if (!$todayAttendance) {
                $todayAttendance = new \stdClass();
                $todayAttendance->factory_id = $location_type === 'factory' ? $location_id : null;
                $todayAttendance->site_id = $location_type === 'site' ? $location_id : null;
            }

            $assignment = TaskAssignment::create([
                'task_id' => $task->id,
                'location_type' => $location_type,
                'location_id' => $location_id,
                'employee_id' => $employeeId,
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'hourly_rate_at_time' => $employee->getHourlyRateForAttendance($todayAttendance),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Start work session if employee is currently checked in
            $session = $this->workSessionService->handleNewAssignment($assignment);
            if ($session) {
                $sessionsStarted++;
            }

            $assignment->load('employee');
            $assignment->session_started = !is_null($session);
            $assignments[] = $assignment;
        }

        // Update task status if it was pending
        if ($task->status === 'pending' && count($assignments) > 0) {
            $task->status = 'in_progress';
            $task->start_date = $task->start_date ?? now();
            $task->save();
        }

        return response()->json([
            'success' => true,
            'message' => count($assignments) . ' employee(s) assigned to task',
            'data' => $assignments,
            'sessions_started' => $sessionsStarted,
        ], 201);
    }

    public function remove(Request $request, TaskAssignment $assignment)
    {
        if ($assignment->removed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment already removed',
            ], 422);
        }

        $user = $request->user();

        // End any active session for this assignment
        $endedSession = $this->workSessionService->handleAssignmentRemoval($assignment);

        $assignment->update([
            'removed_at' => now(),
            'removed_by' => $user->id,
        ]);

        // Recalculate task costs
        $assignment->task->recalculateCosts();

        return response()->json([
            'success' => true,
            'message' => 'Employee removed from task',
            'data' => $assignment,
            'session_ended' => !is_null($endedSession),
            'session_hours' => $endedSession?->hours,
        ]);
    }

    public function show(TaskAssignment $assignment)
    {
        $assignment->load(['task.project', 'employee', 'workLogs', 'assignedBy', 'removedBy']);

        return response()->json([
            'success' => true,
            'data' => $assignment,
        ]);
    }

    public function byTask(Request $request, Task $task)
    {
        $query = $task->assignments()
            ->with(['employee', 'workLogs']);

        // Filter by work location if specified
        if ($request->has('location_type') && $request->has('location_id')) {
            $query->where('location_type', $request->location_type)
                  ->where('location_id', $request->location_id);
        }

        $assignments = $query->orderBy('assigned_at', 'desc')->get();

        $active = $assignments->whereNull('removed_at');
        $removed = $assignments->whereNotNull('removed_at');

        return response()->json([
            'success' => true,
            'data' => [
                'active' => $active->values(),
                'removed' => $removed->values(),
                'total_hours' => $active->sum('hours_worked'),
                'total_labor_cost' => $active->sum(fn($a) => $a->hours_worked * $a->hourly_rate_at_time),
            ],
        ]);
    }

    public function byEmployee(Employee $employee)
    {
        $assignments = $employee->taskAssignments()
            ->with(['task.project'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        $active = $assignments->whereNull('removed_at');
        $completed = $assignments->whereNotNull('removed_at');

        return response()->json([
            'success' => true,
            'data' => [
                'active' => $active->values(),
                'history' => $completed->values(),
                'total_hours' => $assignments->sum('hours_worked'),
                'total_earnings' => $assignments->sum(fn($a) => $a->hours_worked * $a->hourly_rate_at_time),
            ],
        ]);
    }
}
