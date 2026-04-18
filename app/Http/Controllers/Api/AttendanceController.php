<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Employee;
use App\Services\WorkSessionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected WorkSessionService $workSessionService;

    public function __construct(WorkSessionService $workSessionService)
    {
        $this->workSessionService = $workSessionService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Attendance::with(['employee', 'site', 'factory']);

        // Supervisors only see attendance at their site
        if ($user->isSupervisor() && $user->site_id) {
            $query->where('site_id', $user->site_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        } else {
            // Default to today
            $query->whereDate('date', today());
        }

        // Filter by site
        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by factory
        if ($request->has('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('check_in_time', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $attendances,
        ]);
    }

    public function todaySummary(Request $request)
    {
        $user = $request->user();
        $siteId = $user->isSupervisor() ? $user->site_id : $request->site_id;
        $factoryId = $request->factory_id;

        $query = Employee::where('status', 'active');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        if ($factoryId) {
            $query->where('factory_id', $factoryId);
        }

        $totalEmployees = $query->count();

        $attendanceQuery = Attendance::whereDate('date', today());

        if ($siteId) {
            $attendanceQuery->where('site_id', $siteId);
        }

        if ($factoryId) {
            $attendanceQuery->where('factory_id', $factoryId);
        }

        $presentToday = (clone $attendanceQuery)->whereNotNull('check_in_time')->count();
        $checkedOut = (clone $attendanceQuery)->whereNotNull('check_out_time')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_employees' => $totalEmployees,
                'present' => $presentToday,
                'not_present' => $totalEmployees - $presentToday,
                'checked_out' => $checkedOut,
                'still_working' => $presentToday - $checkedOut,
            ],
        ]);
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'site_id' => 'nullable|exists:sites,id',
            'factory_id' => 'nullable|exists:factories,id',
            'project_id' => 'nullable|exists:projects,id',
            'photo' => 'nullable|string',
            'location' => 'nullable|string', // "lat,lng"
        ]);

        // Ensure either site_id or factory_id is provided
        if (empty($validated['site_id']) && empty($validated['factory_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Either site_id or factory_id must be provided',
            ], 422);
        }

        // Ensure only one is provided
        if (!empty($validated['site_id']) && !empty($validated['factory_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot check in to both site and factory simultaneously',
            ], 422);
        }

        $user = $request->user();

        // Check if already checked in today
        $existing = Attendance::where('employee_id', $validated['employee_id'])
            ->whereDate('date', today())
            ->first();

        if ($existing) {
            // Check if trying to check in at a different location
            $isDifferentLocation = false;

            if (!empty($validated['site_id']) && $existing->site_id != $validated['site_id']) {
                $isDifferentLocation = true;
            } elseif (!empty($validated['factory_id']) && $existing->factory_id != $validated['factory_id']) {
                $isDifferentLocation = true;
            }

            if ($isDifferentLocation) {
                // Transfer to new location
                \Log::info("Transferring employee to new location", [
                    'employee_id' => $validated['employee_id'],
                    'old_site_id' => $existing->site_id,
                    'old_factory_id' => $existing->factory_id,
                    'new_site_id' => $validated['site_id'] ?? null,
                    'new_factory_id' => $validated['factory_id'] ?? null,
                ]);

                // End current work sessions at old location
                $this->workSessionService->endAllActiveSessions($existing);

                // Update attendance to new location
                $existing->update([
                    'site_id' => $validated['site_id'] ?? null,
                    'factory_id' => $validated['factory_id'] ?? null,
                    'project_id' => $validated['project_id'] ?? null,
                ]);

                // Start new work sessions for assignments at new location
                $sessionData = $this->workSessionService->handleCheckIn($existing);

                $existing->load('employee', 'site', 'factory');

                return response()->json([
                    'success' => true,
                    'message' => 'Transferred to new location successfully',
                    'data' => $existing,
                    'sessions_started' => $sessionData['sessions_started'],
                    'active_tasks' => $sessionData['active_tasks'],
                    'transferred' => true,
                ], 200);
            }

            // Same location - return existing attendance
            $existing->load('employee', 'site', 'factory');
            return response()->json([
                'success' => true,
                'message' => 'Employee already checked in at this location today',
                'data' => $existing,
            ], 200);
        }

        // New check-in
        $attendance = Attendance::create([
            'employee_id' => $validated['employee_id'],
            'site_id' => $validated['site_id'] ?? null,
            'factory_id' => $validated['factory_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'date' => today(),
            'check_in_time' => now(),
            'check_in_photo' => $validated['photo'] ?? null,
            'check_in_location' => $validated['location'] ?? null,
            'status' => 'present',
            'marked_by' => $user->id,
        ]);

        // Start work sessions for all active task assignments
        $sessionData = $this->workSessionService->handleCheckIn($attendance);

        // Get ALL available task assignments for this employee (for task selection)
        $availableAssignments = $this->workSessionService->getAvailableTaskAssignments(
            $attendance->employee_id,
            $attendance->site_id,
            $attendance->factory_id,
            $attendance->project_id
        );

        $attendance->load('employee', 'site', 'factory');

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful',
            'data' => $attendance,
            'sessions_started' => $sessionData['sessions_started'],
            'active_tasks' => $sessionData['active_tasks'],
            'available_tasks' => $availableAssignments,
        ], 201);
    }

    public function checkOut(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'photo' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        if ($attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'Employee already checked out',
            ], 422);
        }

        // End all active work sessions before checkout
        $workSummary = $this->workSessionService->handleCheckOut($attendance);

        // Capture employee rate snapshots at checkout time
        $employee = $attendance->employee;

        // Use appropriate rates based on attendance location (factory vs site)
        $attendance->update([
            'check_out_time' => now(),
            'check_out_photo' => $validated['photo'] ?? null,
            'check_out_location' => $validated['location'] ?? null,
            'daily_rate_at_time' => $employee->getDailyRateForAttendance($attendance),
            'working_hours_at_time' => $employee->getWorkingHoursForAttendance($attendance),
        ]);

        $attendance->load('employee', 'site', 'factory', 'breaks');

        return response()->json([
            'success' => true,
            'message' => 'Check-out successful',
            'data' => $attendance,
            'work_summary' => $workSummary,
            'salary_info' => [
                'total_hours' => $attendance->total_hours,
                'daily_salary' => $attendance->daily_salary,
                'daily_rate' => $attendance->daily_rate_at_time,
                'working_hours' => $attendance->working_hours_at_time,
                'overtime_applicable' => $attendance->overtime_applicable,
                'overtime_hours' => $attendance->overtime_hours,
                'overtime_salary' => $attendance->overtime_salary,
            ],
        ]);
    }

    public function breakOut(Request $request, Attendance $attendance)
    {
        if (!$attendance->check_in_time || $attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'Employee is not currently checked in',
            ], 422);
        }

        if ($attendance->isOnBreak()) {
            return response()->json([
                'success' => false,
                'message' => 'Employee is already on a break',
            ], 422);
        }

        $this->workSessionService->handleBreakOut($attendance);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_out_time' => now(),
        ]);

        $attendance->load('employee', 'site', 'breaks');

        return response()->json([
            'success' => true,
            'message' => 'Break started',
            'data' => $attendance,
            'break' => $break,
        ]);
    }

    public function breakIn(Request $request, Attendance $attendance)
    {
        $openBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_in_time')
            ->latest()
            ->first();

        if (!$openBreak) {
            return response()->json([
                'success' => false,
                'message' => 'No active break found',
            ], 422);
        }

        $this->workSessionService->handleBreakIn($attendance);

        $openBreak->update(['break_in_time' => now()]);

        $attendance->load('employee', 'site', 'breaks');

        return response()->json([
            'success' => true,
            'message' => 'Break ended',
            'data' => $attendance,
            'break' => $openBreak->fresh(),
        ]);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('employee', 'site', 'markedBy', 'breaks');

        return response()->json([
            'success' => true,
            'data' => $attendance,
        ]);
    }

    public function employeeHistory(Request $request, Employee $employee)
    {
        $query = $employee->attendances()->with('site');

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        $totalHours = $attendances->sum('total_hours');
        $totalDays = $attendances->count();

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $employee,
                'attendances' => $attendances,
                'summary' => [
                    'total_days' => $totalDays,
                    'total_hours' => round($totalHours, 2),
                ],
            ],
        ]);
    }

    public function switchActiveTask(Request $request)
    {
        $validated = $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'task_assignment_id' => 'required|exists:task_assignments,id',
        ]);

        $attendance = Attendance::findOrFail($validated['attendance_id']);
        $taskAssignment = \App\Models\TaskAssignment::findOrFail($validated['task_assignment_id']);

        // Verify the task assignment belongs to the same employee
        if ($taskAssignment->employee_id !== $attendance->employee_id) {
            return response()->json([
                'success' => false,
                'message' => 'Task assignment does not belong to this employee',
            ], 422);
        }

        // Switch to the new task (stops all other sessions and starts this one)
        $result = $this->workSessionService->switchActiveTask($attendance, $taskAssignment);

        return response()->json([
            'success' => true,
            'message' => 'Task switched successfully',
            'data' => $result,
        ]);
    }
}
