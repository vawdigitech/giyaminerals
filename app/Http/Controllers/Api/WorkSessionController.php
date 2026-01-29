<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentSession;
use App\Services\WorkSessionService;
use Illuminate\Http\Request;

class WorkSessionController extends Controller
{
    protected WorkSessionService $workSessionService;

    public function __construct(WorkSessionService $workSessionService)
    {
        $this->workSessionService = $workSessionService;
    }

    /**
     * Get today's work sessions summary
     */
    public function today(Request $request)
    {
        $user = $request->user();

        // Get all sessions for today
        $query = TaskAssignmentSession::with(['taskAssignment.task.project', 'taskAssignment.employee', 'attendance'])
            ->whereDate('date', today());

        // Filter by site if user is supervisor
        if ($user->isSupervisor() && $user->site_id) {
            $query->whereHas('attendance', function ($q) use ($user) {
                $q->where('site_id', $user->site_id);
            });
        }

        $sessions = $query->get();

        $activeSessions = $sessions->where('status', 'active');
        $completedSessions = $sessions->where('status', 'completed');

        return response()->json([
            'success' => true,
            'data' => [
                'active_sessions' => $activeSessions->map(function ($session) {
                    $currentHours = \Carbon\Carbon::parse($session->start_time)->diffInMinutes(now()) / 60;
                    return [
                        'id' => $session->id,
                        'employee' => [
                            'id' => $session->taskAssignment->employee->id,
                            'name' => $session->taskAssignment->employee->name,
                        ],
                        'task' => [
                            'id' => $session->taskAssignment->task->id,
                            'name' => $session->taskAssignment->task->name,
                            'project_name' => $session->taskAssignment->task->project->name,
                        ],
                        'started_at' => $session->start_time,
                        'current_hours' => round($currentHours, 2),
                    ];
                })->values(),
                'completed_sessions' => $completedSessions->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'employee' => [
                            'id' => $session->taskAssignment->employee->id,
                            'name' => $session->taskAssignment->employee->name,
                        ],
                        'task' => [
                            'id' => $session->taskAssignment->task->id,
                            'name' => $session->taskAssignment->task->name,
                            'project_name' => $session->taskAssignment->task->project->name,
                        ],
                        'started_at' => $session->start_time,
                        'ended_at' => $session->end_time,
                        'hours' => $session->hours,
                        'end_reason' => $session->end_reason,
                    ];
                })->values(),
                'summary' => [
                    'total_active' => $activeSessions->count(),
                    'total_completed' => $completedSessions->count(),
                    'total_hours_completed' => round($completedSessions->sum('hours'), 2),
                ],
            ],
        ]);
    }

    /**
     * Get work sessions for a specific employee
     */
    public function byEmployee(Request $request, Employee $employee)
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $sessions = $this->workSessionService->getEmployeeSessions($employee, $fromDate, $toDate);

        // Group sessions by date
        $groupedSessions = $sessions->groupBy(function ($session) {
            return $session->date->format('Y-m-d');
        });

        $dailySummaries = $groupedSessions->map(function ($daySessions, $date) {
            return [
                'date' => $date,
                'sessions' => $daySessions->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'task' => [
                            'id' => $session->taskAssignment->task->id,
                            'name' => $session->taskAssignment->task->name,
                            'project_name' => $session->taskAssignment->task->project->name,
                        ],
                        'started_at' => $session->start_time,
                        'ended_at' => $session->end_time,
                        'hours' => $session->hours,
                        'status' => $session->status,
                        'end_reason' => $session->end_reason,
                    ];
                })->values(),
                'total_hours' => round($daySessions->sum('hours'), 2),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'hourly_rate' => $employee->hourly_rate,
                ],
                'daily_summaries' => $dailySummaries,
                'total_hours' => round($sessions->sum('hours'), 2),
                'total_earnings' => round($sessions->sum(function ($s) {
                    return $s->hours * $s->taskAssignment->hourly_rate_at_time;
                }), 2),
            ],
        ]);
    }

    /**
     * Get sessions for a specific task assignment
     */
    public function byAssignment(TaskAssignment $assignment)
    {
        $sessions = $this->workSessionService->getAssignmentSessions($assignment);

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => [
                    'id' => $assignment->id,
                    'employee_id' => $assignment->employee_id,
                    'task_id' => $assignment->task_id,
                    'assigned_at' => $assignment->assigned_at,
                    'removed_at' => $assignment->removed_at,
                    'hours_worked' => $assignment->hours_worked,
                    'hourly_rate_at_time' => $assignment->hourly_rate_at_time,
                ],
                'sessions' => $sessions->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'date' => $session->date->format('Y-m-d'),
                        'started_at' => $session->start_time,
                        'ended_at' => $session->end_time,
                        'hours' => $session->hours,
                        'status' => $session->status,
                        'end_reason' => $session->end_reason,
                    ];
                }),
                'total_sessions' => $sessions->count(),
                'total_hours' => round($sessions->sum('hours'), 2),
            ],
        ]);
    }

    /**
     * Get today's summary for a specific employee
     */
    public function employeeTodaySummary(Employee $employee)
    {
        $summary = $this->workSessionService->getTodaySummary($employee);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
