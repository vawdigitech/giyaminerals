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
     * Starts sessions for all active task assignments at this location
     */
    public function handleCheckIn(Attendance $attendance): array
    {
        $employee = $attendance->employee;
        $sessionsStarted = [];

        // Get all active task assignments for this employee at the current location
        $query = TaskAssignment::where('employee_id', $employee->id)
            ->whereNull('removed_at');

        // Filter by attendance location (site or factory)
        if ($attendance->site_id) {
            $query->where('location_type', 'site')
                  ->where('location_id', $attendance->site_id);
        } elseif ($attendance->factory_id) {
            $query->where('location_type', 'factory')
                  ->where('location_id', $attendance->factory_id);
        }

        // Filter by project if specified
        if ($attendance->project_id) {
            $query->whereHas('task', function ($q) use ($attendance) {
                $q->where('project_id', $attendance->project_id);
            });
        }

        $activeAssignments = $query->get();

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
     * End all active sessions for an attendance
     * Used for location transfers or early termination
     */
    public function endAllActiveSessions(Attendance $attendance, string $reason = 'transfer'): int
    {
        $activeSessions = $attendance->activeSessions()->get();

        foreach ($activeSessions as $session) {
            $session->end($reason);
        }

        return $activeSessions->count();
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

            // Refresh the task assignment to get the updated hourly_rate_at_time
            // that was set during the session end hook
            $session->taskAssignment->refresh();

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
     * Handle break start
     * Pauses all active task sessions
     */
    public function handleBreakOut(Attendance $attendance): array
    {
        $activeSessions = $attendance->activeSessions()->with('taskAssignment.task.project')->get();

        foreach ($activeSessions as $session) {
            $session->end('break');
        }

        return [
            'sessions_paused' => $activeSessions->count(),
        ];
    }

    /**
     * Handle break end
     * Resumes sessions for all active task assignments at this location
     */
    public function handleBreakIn(Attendance $attendance): array
    {
        $employee = $attendance->employee;
        $sessionsStarted = [];

        // Get all active task assignments for this employee at the current location
        $query = TaskAssignment::where('employee_id', $employee->id)
            ->whereNull('removed_at');

        // Filter by attendance location (site or factory)
        if ($attendance->site_id) {
            $query->where('location_type', 'site')
                  ->where('location_id', $attendance->site_id);
        } elseif ($attendance->factory_id) {
            $query->where('location_type', 'factory')
                  ->where('location_id', $attendance->factory_id);
        }

        // Filter by project if specified
        if ($attendance->project_id) {
            $query->whereHas('task', function ($q) use ($attendance) {
                $q->where('project_id', $attendance->project_id);
            });
        }

        $activeAssignments = $query->get();

        foreach ($activeAssignments as $assignment) {
            $session = $this->startSession($assignment, $attendance);
            if ($session) {
                $sessionsStarted[] = $session;
            }
        }

        return [
            'sessions_resumed' => count($sessionsStarted),
        ];
    }

    /**
     * Handle new task assignment
     * Starts session if employee is currently checked in at the same location
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

        // Check if assignment location matches attendance location
        $locationMatches = false;

        if ($todayAttendance->site_id && $assignment->location_type === 'site' && $assignment->location_id == $todayAttendance->site_id) {
            $locationMatches = true;
        } elseif ($todayAttendance->factory_id && $assignment->location_type === 'factory' && $assignment->location_id == $todayAttendance->factory_id) {
            $locationMatches = true;
        }

        if (!$locationMatches) {
            // Employee is checked in at a different location, don't start session
            \Log::info("Not starting session - location mismatch", [
                'assignment_id' => $assignment->id,
                'assignment_location' => $assignment->location_type . ':' . $assignment->location_id,
                'attendance_location' => ($todayAttendance->site_id ? 'site:' . $todayAttendance->site_id : 'factory:' . $todayAttendance->factory_id),
            ]);
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

    /**
     * Get all available task assignments for an employee at a location
     */
    public function getAvailableTaskAssignments($employeeId, $siteId = null, $factoryId = null, $projectId = null): array
    {
        $query = TaskAssignment::where('employee_id', $employeeId)
            ->whereNull('removed_at')
            ->with(['task.project']);

        // Filter by location
        if ($siteId) {
            $query->where(function ($q) use ($siteId) {
                $q->where('location_type', 'site')
                  ->where('location_id', $siteId);
            });
        } elseif ($factoryId) {
            $query->where(function ($q) use ($factoryId) {
                $q->where('location_type', 'factory')
                  ->where('location_id', $factoryId);
            });
        }

        // Filter by project if specified
        if ($projectId) {
            $query->whereHas('task', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        $assignments = $query->get();

        return $assignments->map(function ($assignment) {
            return [
                'assignment_id' => $assignment->id,
                'task_id' => $assignment->task_id,
                'task_name' => $assignment->task->name,
                'project_id' => $assignment->task->project_id,
                'project_name' => $assignment->task->project->name ?? 'No Project',
            ];
        })->toArray();
    }

    /**
     * Switch active task - stops all current sessions and starts the selected one
     */
    public function switchActiveTask($attendance, $taskAssignment): array
    {
        // Step 1: Stop all active sessions for this attendance
        $activeSessions = TaskAssignmentSession::where('attendance_id', $attendance->id)
            ->where('status', 'active')
            ->whereDate('date', today())
            ->get();

        foreach ($activeSessions as $session) {
            $this->endSession($session, $attendance, 'task_switched');
        }

        // Step 2: Start new session for the selected task
        $newSession = $this->startSession($taskAssignment, $attendance);

        return [
            'sessions_stopped' => $activeSessions->count(),
            'new_session' => [
                'session_id' => $newSession->id,
                'task_id' => $taskAssignment->task_id,
                'task_name' => $taskAssignment->task->name,
                'project_name' => $taskAssignment->task->project->name ?? 'No Project',
                'started_at' => $newSession->start_time,
            ],
        ];
    }
}
