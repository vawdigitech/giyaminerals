<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
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

        $query = Attendance::with(['employee', 'site']);

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

        $query = Employee::where('status', 'active');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $totalEmployees = $query->count();

        $presentToday = Attendance::whereDate('date', today())
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->whereNotNull('check_in_time')
            ->count();

        $checkedOut = Attendance::whereDate('date', today())
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->whereNotNull('check_out_time')
            ->count();

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
            'site_id' => 'required|exists:sites,id',
            'photo' => 'nullable|string',
            'location' => 'nullable|string', // "lat,lng"
        ]);

        $user = $request->user();

        // Check if already checked in today
        $existing = Attendance::where('employee_id', $validated['employee_id'])
            ->whereDate('date', today())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Employee already checked in today',
            ], 422);
        }

        $attendance = Attendance::create([
            'employee_id' => $validated['employee_id'],
            'site_id' => $validated['site_id'],
            'date' => today(),
            'check_in_time' => now(),
            'check_in_photo' => $validated['photo'] ?? null,
            'check_in_location' => $validated['location'] ?? null,
            'status' => 'present',
            'marked_by' => $user->id,
        ]);

        // Start work sessions for all active task assignments
        $sessionData = $this->workSessionService->handleCheckIn($attendance);

        $attendance->load('employee', 'site');

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful',
            'data' => $attendance,
            'sessions_started' => $sessionData['sessions_started'],
            'active_tasks' => $sessionData['active_tasks'],
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

        $attendance->update([
            'check_out_time' => now(),
            'check_out_photo' => $validated['photo'] ?? null,
            'check_out_location' => $validated['location'] ?? null,
        ]);

        $attendance->load('employee', 'site');

        return response()->json([
            'success' => true,
            'message' => 'Check-out successful',
            'data' => $attendance,
            'work_summary' => $workSummary,
        ]);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('employee', 'site', 'markedBy');

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
}
