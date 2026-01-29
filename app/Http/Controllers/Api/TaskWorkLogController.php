<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskAssignment;
use App\Models\TaskWorkLog;
use Illuminate\Http\Request;

class TaskWorkLogController extends Controller
{
    public function index(Request $request)
    {
        $query = TaskWorkLog::with(['assignment.task', 'assignment.employee']);

        // Filter by assignment
        if ($request->has('task_assignment_id')) {
            $query->where('task_assignment_id', $request->task_assignment_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $workLogs = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $workLogs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_assignment_id' => 'required|exists:task_assignments,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $assignment = TaskAssignment::findOrFail($validated['task_assignment_id']);

        // Check if assignment is active
        if ($assignment->removed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot log work for removed assignment',
            ], 422);
        }

        // Check if already logged for this date
        $existing = TaskWorkLog::where('task_assignment_id', $assignment->id)
            ->whereDate('date', $validated['date'])
            ->first();

        if ($existing) {
            // Update existing log
            $existing->update([
                'hours' => $validated['hours'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'notes' => $validated['notes'] ?? $existing->notes,
                'logged_by' => $user->id,
            ]);

            $existing->load('assignment.task', 'assignment.employee');

            return response()->json([
                'success' => true,
                'message' => 'Work log updated',
                'data' => $existing,
            ]);
        }

        $workLog = TaskWorkLog::create([
            'task_assignment_id' => $assignment->id,
            'date' => $validated['date'],
            'hours' => $validated['hours'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'logged_by' => $user->id,
        ]);

        $workLog->load('assignment.task', 'assignment.employee');

        return response()->json([
            'success' => true,
            'message' => 'Work log created',
            'data' => $workLog,
        ], 201);
    }

    public function show(TaskWorkLog $workLog)
    {
        $workLog->load('assignment.task', 'assignment.employee', 'loggedBy');

        return response()->json([
            'success' => true,
            'data' => $workLog,
        ]);
    }

    public function update(Request $request, TaskWorkLog $workLog)
    {
        $validated = $request->validate([
            'hours' => 'sometimes|numeric|min:0.5|max:24',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $workLog->update($validated);
        $workLog->load('assignment.task', 'assignment.employee');

        return response()->json([
            'success' => true,
            'message' => 'Work log updated',
            'data' => $workLog,
        ]);
    }

    public function destroy(TaskWorkLog $workLog)
    {
        $workLog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work log deleted',
        ]);
    }

    public function byAssignment(TaskAssignment $assignment)
    {
        $workLogs = $assignment->workLogs()
            ->with('loggedBy')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => $assignment->load('task', 'employee'),
                'work_logs' => $workLogs,
                'total_hours' => $workLogs->sum('hours'),
                'total_cost' => $assignment->hours_worked * $assignment->hourly_rate_at_time,
            ],
        ]);
    }

    public function logToday(Request $request)
    {
        $validated = $request->validate([
            'task_assignment_id' => 'required|exists:task_assignments,id',
            'hours' => 'required|numeric|min:0.5|max:24',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $assignment = TaskAssignment::findOrFail($validated['task_assignment_id']);

        if ($assignment->removed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot log work for removed assignment',
            ], 422);
        }

        $workLog = TaskWorkLog::updateOrCreate(
            [
                'task_assignment_id' => $assignment->id,
                'date' => today(),
            ],
            [
                'hours' => $validated['hours'],
                'notes' => $validated['notes'] ?? null,
                'logged_by' => $user->id,
            ]
        );

        $workLog->load('assignment.task', 'assignment.employee');

        return response()->json([
            'success' => true,
            'message' => 'Today\'s work logged',
            'data' => $workLog,
        ]);
    }
}
