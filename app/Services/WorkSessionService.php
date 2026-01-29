<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkSessionService
{
    /**
     * Handle employee check-in
     * Starts sessions for all active task assignments
     */
    public function handleCheckIn(Attendance $attendance): array
    {
        $employee = $attendance->employee;
        $sessionsStarted = [];

        // Get all active task assignments for this employee
        $activeAssignments = TaskAssignment::where('employee_id', $employee->id)
            ->whereNull('removed_at')
            ->get();

        foreach ($activeAssignments as $assignment) {
            $session = $this->startSession($assignment, $attendance);
            if ($session) {
                $sessionsStarted[] = $session;
            }
        }

        return [
            'sessions_started' => count($sessionsStarted),
            'active_tasks' => collect($sessionsStarted)->map(function ($session) {
                return [
                    'session_id' => $session->id,
                    'task_id' => $session->taskAssignment->task_id,
                    'task_name' => $session->taskAssignment->task->name,
                    'project_name' => $session->taskAssignment->task->project->name,
                    'started_at' => $session->start_time,
                ];
            })->toArray(),
        ];
    }

    /**
     * Handle employee check-out
     * Ends all active sessions for this attendance
     */
    public function handleCheckOut(Attendance $attendance): array
    {
        $activeSessions = $attendance->activeSessions()->with('taskAssignment.task.project')->get();
        $workSummary = [];

        foreach ($activeSessions as $session) {
            $session->end('checkout');

            $workSummary[] = [
                'task_id' => $session->taskAssignment->task_id,
                'task_name' => $session->taskAssignment->task->name,
                'project_name' => $session->taskAssignment->task->project->name,
                'hours_worked' => $session->hours,
                'cost' => $session->hours * $session->taskAssignment->hourly_rate_at_time,
            ];
        }

        return [
            'sessions_ended' => count($workSummary),
            'work_summary' => $workSummary,
            'total_hours' => collect($workSummary)->sum('hours_worked'),
            'total_cost' => collect($workSummary)->sum('cost'),
        ];
    }

    /**
     * Handle new task assignment
     * Starts session if employee is currently checked in
     */
    public function handleNewAssignment(TaskAssignment $assignment): ?TaskAssignmentSession
    {
        $employee = $assignment->employee;

        // Check if employee is currently checked in (has attendance today without checkout)
        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', today())
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->first();

        if (!$todayAttendance) {
            // Employee not checked in, session will start when they check in
            return null;
        }

        return $this->startSession($assignment, $todayAttendance);
    }

    /**
     * Handle assignment removal
     * Ends any active session for this assignment
     */
    public function handleAssignmentRemoval(TaskAssignment $assignment): ?TaskAssignmentSession
    {
        $activeSession = $assignment->activeSessions()
            ->whereDate('date', today())
            ->first();

        if ($activeSession) {
            $activeSession->end('removed');
            return $activeSession;
        }

        return null;
    }

    /**
     * Start a new work session
     */
    public function startSession(TaskAssignment $assignment, Attendance $attendance): TaskAssignmentSession
    {
        // Check if there's already an active session for this assignment today
        $existingSession = TaskAssignmentSession::where('task_assignment_id', $assignment->id)
            ->where('attendance_id', $attendance->id)
            ->whereDate('date', today())
            ->where('status', 'active')
            ->first();

        if ($existingSession) {
            return $existingSession;
        }

        return TaskAssignmentSession::create([
            'task_assignment_id' => $assignment->id,
            'attendance_id' => $attendance->id,
            'date' => today(),
            'start_time' => now()->format('H:i:s'),
            'status' => 'active',
        ]);
    }

    /**
     * Close all stale sessions (for day-end cleanup)
     * Called by scheduled command
     */
    public function closeStaleSession(): int
    {
        $staleSessions = TaskAssignmentSession::where('status', 'active')
            ->whereDate('date', '<', today())
            ->get();

        $count = 0;
        foreach ($staleSessions as $session) {
            // Set end time to end of the day (23:59:59)
            $session->update([
                'end_time' => '23:59:59',
                'end_reason' => 'day_end',
                'status' => 'completed',
                'hours' => Carbon::parse($session->start_time)->diffInMinutes(Carbon::parse('23:59:59')) / 60,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Get today's work summary for an employee
     */
    public function getTodaySummary(Employee $employee): array
    {
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', today())
            ->first();

        if (!$attendance) {
            return [
                'checked_in' => false,
                'sessions' => [],
                'total_hours' => 0,
            ];
        }

        $sessions = $attendance->sessions()
            ->with('taskAssignment.task.project')
            ->get();

        $activeSessions = $sessions->where('status', 'active');
        $completedSessions = $sessions->where('status', 'completed');

        return [
            'checked_in' => true,
            'checked_out' => !is_null($attendance->check_out_time),
            'check_in_time' => $attendance->check_in_time,
            'check_out_time' => $attendance->check_out_time,
            'active_sessions' => $activeSessions->map(function ($session) {
                $currentHours = Carbon::parse($session->start_time)->diffInMinutes(now()) / 60;
                return [
                    'session_id' => $session->id,
                    'task_id' => $session->taskAssignment->task_id,
                    'task_name' => $session->taskAssignment->task->name,
                    'project_name' => $session->taskAssignment->task->project->name,
                    'started_at' => $session->start_time,
                    'current_hours' => round($currentHours, 2),
                ];
            })->values(),
            'completed_sessions' => $completedSessions->map(function ($session) {
                return [
                    'session_id' => $session->id,
                    'task_id' => $session->taskAssignment->task_id,
                    'task_name' => $session->taskAssignment->task->name,
                    'project_name' => $session->taskAssignment->task->project->name,
                    'started_at' => $session->start_time,
                    'ended_at' => $session->end_time,
                    'hours' => $session->hours,
                    'end_reason' => $session->end_reason,
                ];
            })->values(),
            'total_hours' => $completedSessions->sum('hours'),
        ];
    }

    /**
     * Get sessions for a specific assignment
     */
    public function getAssignmentSessions(TaskAssignment $assignment): Collection
    {
        return $assignment->sessions()
            ->with('attendance')
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Get employee's sessions for a date range
     */
    public function getEmployeeSessions(Employee $employee, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = TaskAssignmentSession::whereHas('taskAssignment', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })
        ->with(['taskAssignment.task.project', 'attendance']);

        if ($fromDate) {
            $query->whereDate('date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('date', '<=', $toDate);
        }

        return $query->orderBy('date', 'desc')->get();
    }
}
