<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Site;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with(['employee', 'site']);

        // Default to current month
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $query->whereBetween('date', [$startDate, $endDate]);

        // Filter by site
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->paginate(20);

        $sites = Site::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        // Calculate summary statistics
        $summary = [
            'total_records' => $query->count(),
            'total_hours' => $query->sum('total_hours'),
            'present_count' => (clone $query)->where('status', 'present')->count(),
            'absent_count' => (clone $query)->where('status', 'absent')->count(),
            'late_count' => (clone $query)->where('status', 'late')->count(),
        ];

        return view('attendance.index', compact(
            'attendances', 'sites', 'employees', 'summary',
            'startDate', 'endDate'
        ));
    }

    public function daily(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $siteId = $request->input('site_id');

        $query = Attendance::with(['employee', 'site'])
            ->where('date', $date);

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $attendances = $query->orderBy('check_in_time')->get();

        // Get all employees and check who's missing
        $employeeQuery = Employee::where('status', 'active');
        if ($siteId) {
            $employeeQuery->where('site_id', $siteId);
        }
        $allEmployees = $employeeQuery->get();

        $presentIds = $attendances->pluck('employee_id')->toArray();
        $absentEmployees = $allEmployees->whereNotIn('id', $presentIds);

        $sites = Site::orderBy('name')->get();

        // Summary for the day
        $summary = [
            'total_employees' => $allEmployees->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $absentEmployees->count(),
            'total_hours' => $attendances->sum('total_hours'),
        ];

        return view('attendance.daily', compact(
            'attendances', 'absentEmployees', 'sites', 'summary', 'date'
        ));
    }

    public function employeeReport(Request $request, Employee $employee)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $attendances = $employee->attendances()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // Calculate statistics
        $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $workingDays = 0;
        $current = Carbon::parse($startDate);
        while ($current <= Carbon::parse($endDate)) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }

        $summary = [
            'total_days' => $totalDays,
            'working_days' => $workingDays,
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $workingDays - $attendances->count(),
            'total_hours' => $attendances->sum('total_hours'),
            'average_hours' => $attendances->count() > 0 ? round($attendances->sum('total_hours') / $attendances->count(), 2) : 0,
        ];

        return view('attendance.employee-report', compact(
            'employee', 'attendances', 'summary', 'startDate', 'endDate'
        ));
    }

    public function export(Request $request)
    {
        // This would generate a CSV/Excel export
        // For now, return the data as JSON for demonstration
        $query = Attendance::with(['employee', 'site']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        $data = $query->orderBy('date', 'desc')->get();

        $filename = 'attendance_report_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Date', 'Employee Code', 'Employee Name', 'Site',
                'Check In', 'Check Out', 'Total Hours', 'Status'
            ]);

            foreach ($data as $attendance) {
                fputcsv($file, [
                    $attendance->date,
                    $attendance->employee->employee_code ?? '',
                    $attendance->employee->name ?? '',
                    $attendance->site->name ?? '',
                    $attendance->check_in_time,
                    $attendance->check_out_time,
                    $attendance->total_hours,
                    $attendance->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
