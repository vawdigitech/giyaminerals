<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Site;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        // Fix any records with negative hours (one-time correction)
        Attendance::where('total_hours', '<', 0)
            ->whereNotNull('check_in_time')
            ->whereNotNull('check_out_time')
            ->each(function ($attendance) {
                $minutes = $attendance->check_out_time->diffInMinutes($attendance->check_in_time);
                $attendance->total_hours = abs($minutes) / 60;
                $attendance->saveQuietly(); // Save without triggering events
            });

        $query = Attendance::with(['employee', 'site', 'breaks']);

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
            'total_salary' => $query->sum('daily_salary'),
            'total_overtime_salary' => $query->sum('overtime_salary'),
            'total_overtime_hours' => $query->sum('overtime_hours'),
            'overtime_count' => (clone $query)->where('overtime_applicable', true)->count(),
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

        $query = Attendance::with(['employee', 'site', 'breaks'])
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
            'total_overtime_hours' => $attendances->sum('overtime_hours'),
            'total_overtime_salary' => $attendances->sum('overtime_salary'),
            'overtime_count' => $attendances->where('overtime_applicable', true)->count(),
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
                'Check In', 'Check Out', 'Total Hours', 'OT Hours', 'OT Salary', 'Daily Salary', 'Status'
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
                    $attendance->overtime_hours ?? 0,
                    $attendance->overtime_salary ?? 0,
                    $attendance->daily_salary ?? 0,
                    $attendance->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header row
        $headers = ['Date', 'Employee Code', 'Site Name', 'Check In', 'Check Out', 'Status', 'Notes'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2c3e50']],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Sample rows
        $samples = [
            ['2025-12-01', 'EMP001', 'Site Name Here', '09:00', '17:00', 'present', ''],
            ['2025-12-01', 'EMP002', 'Site Name Here', '09:30', '17:00', 'late', ''],
            ['2025-12-02', 'EMP001', 'Site Name Here', '', '', 'absent', 'Sick leave'],
        ];
        foreach ($samples as $rowIndex => $row) {
            foreach ($row as $col => $value) {
                $sheet->setCellValue([$col + 1, $rowIndex + 2], $value);
            }
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Notes sheet with instructions
        $notesSheet = $spreadsheet->createSheet();
        $notesSheet->setTitle('Instructions');
        $notesSheet->setCellValue('A1', 'Instructions for filling the attendance import sheet');
        $notesSheet->setCellValue('A3', 'Date');
        $notesSheet->setCellValue('B3', 'Format: YYYY-MM-DD  e.g. 2025-12-01');
        $notesSheet->setCellValue('A4', 'Employee Code');
        $notesSheet->setCellValue('B4', 'Must match an existing employee code in the system');
        $notesSheet->setCellValue('A5', 'Site Name');
        $notesSheet->setCellValue('B5', 'Must match an existing site name in the system (case-insensitive)');
        $notesSheet->setCellValue('A6', 'Check In');
        $notesSheet->setCellValue('B6', 'Format: HH:MM (24h) e.g. 09:00  — leave blank for absent records');
        $notesSheet->setCellValue('A7', 'Check Out');
        $notesSheet->setCellValue('B7', 'Format: HH:MM (24h) e.g. 17:00  — leave blank if not checked out');
        $notesSheet->setCellValue('A8', 'Status');
        $notesSheet->setCellValue('B8', 'One of: present, late, absent');
        $notesSheet->setCellValue('A9', 'Notes');
        $notesSheet->setCellValue('B9', 'Optional notes');
        $notesSheet->setCellValue('A11', 'Duplicate records (same employee + date) will be skipped.');

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = 'attendance_import_template.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            if ($extension === 'csv') {
                $reader = IOFactory::createReader('Csv');
                $reader->setDelimiter(',');
            } else {
                $reader = IOFactory::createReaderForFile($file->getPathname());
            }

            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Could not read file: ' . $e->getMessage()]);
        }

        if (count($rows) < 2) {
            return back()->withErrors(['file' => 'The file has no data rows.']);
        }

        // Parse header row (case-insensitive)
        $headerRow = array_map(fn($h) => strtolower(trim((string) $h)), $rows[0]);
        $colMap = [];
        $expectedCols = ['date', 'employee code', 'site name', 'check in', 'check out', 'status', 'notes'];
        foreach ($expectedCols as $col) {
            $idx = array_search($col, $headerRow);
            if ($idx !== false) {
                $colMap[$col] = $idx;
            }
        }

        $requiredCols = ['date', 'employee code', 'site name', 'status'];
        foreach ($requiredCols as $col) {
            if (!isset($colMap[$col])) {
                return back()->withErrors(['file' => "Missing required column: \"$col\". Please use the template."]);
            }
        }

        // Cache employee codes and site names to reduce DB queries
        $employees = Employee::get()->keyBy('employee_code')->map(fn($e) => [
            'id'            => $e->id,
            'daily_rate'    => $e->daily_rate,
            'working_hours' => $e->working_hours,
        ])->toArray();
        $sites = Site::get()->keyBy(fn($s) => strtolower($s->name))->map(fn($s) => $s->id)->toArray();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        $dataRows = array_slice($rows, 1);

        foreach ($dataRows as $rowNum => $row) {
            $lineNum = $rowNum + 2; // +2 because 1-indexed and row 1 is header

            $dateRaw       = trim((string) ($row[$colMap['date']] ?? ''));
            $employeeCode  = trim((string) ($row[$colMap['employee code']] ?? ''));
            $siteName      = trim((string) ($row[$colMap['site name']] ?? ''));
            $checkInRaw    = isset($colMap['check in']) ? trim((string) ($row[$colMap['check in']] ?? '')) : '';
            $checkOutRaw   = isset($colMap['check out']) ? trim((string) ($row[$colMap['check out']] ?? '')) : '';
            $statusRaw     = strtolower(trim((string) ($row[$colMap['status']] ?? '')));
            $notes         = isset($colMap['notes']) ? trim((string) ($row[$colMap['notes']] ?? '')) : '';

            // Skip empty rows
            if (empty($dateRaw) && empty($employeeCode)) {
                continue;
            }

            // Validate date
            try {
                // Handle Excel date serial numbers
                if (is_numeric($dateRaw)) {
                    $date = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $dateRaw))->format('Y-m-d');
                } else {
                    $date = Carbon::parse($dateRaw)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $errors[] = "Row $lineNum: Invalid date \"$dateRaw\"";
                continue;
            }

            // Validate employee
            if (empty($employeeCode) || !isset($employees[$employeeCode])) {
                $errors[] = "Row $lineNum: Employee code \"$employeeCode\" not found";
                continue;
            }
            $employeeId       = $employees[$employeeCode]['id'];
            $dailyRateAtTime  = $employees[$employeeCode]['daily_rate'];
            $workingHoursAtTime = $employees[$employeeCode]['working_hours'];

            // Validate site
            if (empty($siteName) || !isset($sites[strtolower($siteName)])) {
                $errors[] = "Row $lineNum: Site \"$siteName\" not found";
                continue;
            }
            $siteId = $sites[strtolower($siteName)];

            // Validate status
            if (!in_array($statusRaw, ['present', 'late', 'absent'])) {
                $errors[] = "Row $lineNum: Invalid status \"$statusRaw\" (use present, late, or absent)";
                continue;
            }

            // Check for duplicate
            $exists = Attendance::where('employee_id', $employeeId)->where('date', $date)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            // Parse times
            $checkInTime = null;
            $checkOutTime = null;

            if (!empty($checkInRaw)) {
                try {
                    if (is_numeric($checkInRaw)) {
                        $dtObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $checkInRaw);
                        $checkInTime = Carbon::parse($date . ' ' . $dtObj->format('H:i:s'));
                    } else {
                        $checkInTime = Carbon::parse($date . ' ' . $checkInRaw);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row $lineNum: Invalid check-in time \"$checkInRaw\"";
                    continue;
                }
            }

            if (!empty($checkOutRaw)) {
                try {
                    if (is_numeric($checkOutRaw)) {
                        $dtObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $checkOutRaw);
                        $checkOutTime = Carbon::parse($date . ' ' . $dtObj->format('H:i:s'));
                    } else {
                        $checkOutTime = Carbon::parse($date . ' ' . $checkOutRaw);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row $lineNum: Invalid check-out time \"$checkOutRaw\"";
                    continue;
                }
            }

            // Calculate total hours
            $totalHours = null;
            if ($checkInTime && $checkOutTime && $checkOutTime->gt($checkInTime)) {
                $totalHours = round($checkInTime->diffInMinutes($checkOutTime) / 60, 2);
            }

            Attendance::create([
                'employee_id'          => $employeeId,
                'site_id'              => $siteId,
                'date'                 => $date,
                'check_in_time'        => $checkInTime,
                'check_out_time'       => $checkOutTime,
                'total_hours'          => $totalHours,
                'daily_rate_at_time'   => $dailyRateAtTime,
                'working_hours_at_time' => $workingHoursAtTime,
                'status'               => $statusRaw,
                'notes'                => $notes ?: null,
                'marked_by'            => auth()->id(),
            ]);

            $imported++;
        }

        $message = "$imported record(s) imported successfully.";
        if ($skipped > 0) {
            $message .= " $skipped duplicate(s) skipped.";
        }

        $request->session()->flash('import_errors', $errors);

        return redirect()->route('attendance.index')
            ->with('success', $message);
    }

    /**
     * Show form to create new attendance record
     */
    public function create()
    {
        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        $sites = Site::orderBy('name')->get();

        return view('attendance.create', compact('employees', 'sites'));
    }

    /**
     * Store new attendance record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'site_id' => 'required|exists:sites,id',
            'date' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'status' => 'required|in:present,late,absent',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check for duplicate attendance record
        $exists = Attendance::where('employee_id', $validated['employee_id'])
            ->where('date', $validated['date'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['date' => 'Attendance record already exists for this employee on this date.']);
        }

        // Get employee for rate snapshot
        $employee = Employee::find($validated['employee_id']);

        // Calculate total hours if both times are provided
        $totalHours = null;
        if ($validated['check_in_time'] && $validated['check_out_time']) {
            $checkIn = Carbon::parse($validated['date'] . ' ' . $validated['check_in_time']);
            $checkOut = Carbon::parse($validated['date'] . ' ' . $validated['check_out_time']);
            $totalHours = round($checkIn->diffInMinutes($checkOut) / 60, 2);
        }

        Attendance::create([
            'employee_id' => $validated['employee_id'],
            'site_id' => $validated['site_id'],
            'date' => $validated['date'],
            'check_in_time' => $validated['check_in_time'] ? Carbon::parse($validated['date'] . ' ' . $validated['check_in_time']) : null,
            'check_out_time' => $validated['check_out_time'] ? Carbon::parse($validated['date'] . ' ' . $validated['check_out_time']) : null,
            'total_hours' => $totalHours,
            'daily_rate_at_time' => $employee->daily_rate,
            'working_hours_at_time' => $employee->working_hours,
            'status' => $validated['status'],
            'notes' => $validated['notes'],
            'marked_by' => auth()->id(),
        ]);

        return redirect()->route('attendance.index')
            ->with('success', 'Attendance record created successfully.');
    }

    /**
     * Show form to edit attendance record
     */
    public function edit(Attendance $attendance)
    {
        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        $sites = Site::orderBy('name')->get();

        return view('attendance.edit', compact('attendance', 'employees', 'sites'));
    }

    /**
     * Update attendance record
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'site_id' => 'required|exists:sites,id',
            'date' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'status' => 'required|in:present,late,absent',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check for duplicate (excluding current record)
        $exists = Attendance::where('employee_id', $validated['employee_id'])
            ->where('date', $validated['date'])
            ->where('id', '!=', $attendance->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['date' => 'Attendance record already exists for this employee on this date.']);
        }

        // Store old total hours for proportional adjustment
        $oldTotalHours = $attendance->total_hours;

        // Calculate total hours if both times are provided
        $totalHours = null;
        if ($validated['check_in_time'] && $validated['check_out_time']) {
            $checkIn = Carbon::parse($validated['date'] . ' ' . $validated['check_in_time']);
            $checkOut = Carbon::parse($validated['date'] . ' ' . $validated['check_out_time']);
            $totalHours = round($checkIn->diffInMinutes($checkOut) / 60, 2);
        }

        $attendance->update([
            'employee_id' => $validated['employee_id'],
            'site_id' => $validated['site_id'],
            'date' => $validated['date'],
            'check_in_time' => $validated['check_in_time'] ? Carbon::parse($validated['date'] . ' ' . $validated['check_in_time']) : null,
            'check_out_time' => $validated['check_out_time'] ? Carbon::parse($validated['date'] . ' ' . $validated['check_out_time']) : null,
            'total_hours' => $totalHours,
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ]);

        // Refresh to get the actual saved total_hours (model hook recalculates it)
        $attendance->refresh();
        $newTotalHours = $attendance->total_hours;

        // Recalculate work sessions if hours changed and sessions exist
        if ($oldTotalHours > 0 && $newTotalHours > 0 && $oldTotalHours != $newTotalHours) {
            $this->recalculateWorkSessions($attendance, $oldTotalHours, $newTotalHours);
        } elseif ($newTotalHours > 0 && $attendance->sessions()->exists()) {
            // If sessions exist, trigger recalculation even if hours didn't change significantly
            $this->triggerTaskRecalculation($attendance);
        }

        return redirect()->route('attendance.index')
            ->with('success', 'Attendance record updated successfully.');
    }

    /**
     * Recalculate work session hours proportionally when attendance hours change
     */
    private function recalculateWorkSessions(Attendance $attendance, $oldHours, $newHours)
    {
        $ratio = $newHours / $oldHours;
        $sessions = $attendance->sessions()->where('status', 'completed')->get();

        $affectedTaskAssignments = [];

        foreach ($sessions as $session) {
            // Adjust session hours proportionally
            $newSessionHours = round($session->hours * $ratio, 2);

            // Update session hours directly without triggering hooks
            // to avoid multiple recalculations
            $session->updateQuietly(['hours' => $newSessionHours]);

            // Track affected task assignments
            $affectedTaskAssignments[$session->task_assignment_id] = $session->taskAssignment;
        }

        // Now trigger recalculation on each affected task assignment
        foreach ($affectedTaskAssignments as $taskAssignment) {
            $taskAssignment->recalculateHoursFromSessions();
        }
    }

    /**
     * Trigger task recalculation for all sessions in this attendance
     */
    private function triggerTaskRecalculation(Attendance $attendance)
    {
        $sessions = $attendance->sessions()->where('status', 'completed')->get();
        $affectedTaskAssignments = [];

        foreach ($sessions as $session) {
            $affectedTaskAssignments[$session->task_assignment_id] = $session->taskAssignment;
        }

        // Trigger recalculation on each affected task assignment
        foreach ($affectedTaskAssignments as $taskAssignment) {
            $taskAssignment->recalculateHoursFromSessions();
        }
    }

    /**
     * Delete attendance record
     */
    public function destroy(Attendance $attendance)
    {
        $employeeName = $attendance->employee->name;
        $date = $attendance->date->format('Y-m-d');

        $attendance->delete();

        return redirect()->route('attendance.index')
            ->with('success', "Attendance record for {$employeeName} on {$date} deleted successfully.");
    }
}
