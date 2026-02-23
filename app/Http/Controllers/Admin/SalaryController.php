<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdvancePayment;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Site;
use App\Models\Task;
use App\Models\TaskAssignmentSession;
use App\Models\WeeklySalaryPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    /**
     * Display the weekly salary report.
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);

        // Build employee query with filters
        $employeeQuery = Employee::where('status', 'active');

        if ($request->filled('site_id')) {
            $employeeQuery->where('site_id', $request->site_id);
        }

        $employees = $employeeQuery->with(['site', 'designation'])->orderBy('name')->get();

        $report = [];
        $grandTotalHours = 0;
        $grandTotalSalary = 0;
        $pendingCount = 0;
        $paidCount = 0;

        foreach ($employees as $employee) {
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$weekStart, $weekEnd])
                ->whereNotNull('check_out_time')
                ->get();

            $totalHours = $attendances->sum('total_hours');
            $totalSalary = $attendances->sum('daily_salary');
            $totalDays = $attendances->count();

            // Check payment status
            $payment = WeeklySalaryPayment::where('employee_id', $employee->id)
                ->where('week_start_date', $weekStart)
                ->first();

            if ($totalDays > 0 || $payment) {
                $report[] = [
                    'employee' => $employee,
                    'total_hours' => round($totalHours, 2),
                    'total_days' => $totalDays,
                    'total_salary' => round($totalSalary, 2),
                    'payment' => $payment,
                ];

                $grandTotalHours += $totalHours;
                $grandTotalSalary += $totalSalary;

                if ($payment) {
                    if ($payment->status === 'paid') {
                        $paidCount++;
                    } else {
                        $pendingCount++;
                    }
                }
            }
        }

        $sites = Site::orderBy('name')->get();

        return view('salary.index', compact(
            'report',
            'weekStart',
            'weekEnd',
            'grandTotalHours',
            'grandTotalSalary',
            'pendingCount',
            'paidCount',
            'sites',
            'date'
        ));
    }

    /**
     * Generate payment records for a specific week.
     */
    public function generatePayments(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $date = Carbon::parse($validated['date']);
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);

        // Build employee query
        $employeeQuery = Employee::where('status', 'active');

        // Filter by specific employee IDs if provided
        if (!empty($validated['employee_ids'])) {
            $employeeQuery->whereIn('id', $validated['employee_ids']);
        }

        $employees = $employeeQuery->get();

        $created = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            // Check if payment already exists
            $existing = WeeklySalaryPayment::where('employee_id', $employee->id)
                ->where('week_start_date', $weekStart)
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            // Calculate totals from attendance
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$weekStart, $weekEnd])
                ->whereNotNull('check_out_time')
                ->get();

            $totalHours = $attendances->sum('total_hours');
            $totalSalary = $attendances->sum('daily_salary');
            $totalDays = $attendances->count();

            // Skip if no attendance records
            if ($totalDays === 0) {
                $skipped++;
                continue;
            }

            WeeklySalaryPayment::create([
                'employee_id' => $employee->id,
                'week_start_date' => $weekStart,
                'week_end_date' => $weekEnd,
                'total_hours' => $totalHours,
                'total_days' => $totalDays,
                'total_salary' => $totalSalary,
                'status' => 'pending',
            ]);

            $created++;
        }

        return redirect()->route('salary.index', ['date' => $date->format('Y-m-d')])
            ->with('success', "{$created} payment records created. {$skipped} skipped.");
    }

    /**
     * Mark a payment as paid.
     */
    public function markAsPaid(Request $request, WeeklySalaryPayment $payment)
    {
        if ($payment->status === 'paid') {
            return redirect()->back()->with('error', 'Payment is already marked as paid.');
        }

        if ($payment->status === 'cancelled') {
            return redirect()->back()->with('error', 'Cannot mark a cancelled payment as paid.');
        }

        // Calculate and deduct advances
        $advanceDeducted = $this->deductAdvances($payment);

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => auth()->id(),
            'notes' => $request->input('notes'),
            'advance_deducted' => $advanceDeducted,
            'net_salary' => $payment->total_salary - $advanceDeducted,
        ]);

        // Update labour costs on tasks where this employee worked during the payment period
        $this->updateTaskLabourCosts($payment);

        $message = "Payment for {$payment->employee->name} marked as paid.";
        if ($advanceDeducted > 0) {
            $message .= " Rs." . number_format($advanceDeducted, 2) . " advance deducted.";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Deduct advances from the payment and mark them as deducted.
     *
     * @param WeeklySalaryPayment $payment
     * @return float Total amount deducted
     */
    protected function deductAdvances(WeeklySalaryPayment $payment): float
    {
        // Get all pending advances for this employee and week
        $pendingAdvances = AdvancePayment::where('employee_id', $payment->employee_id)
            ->where('week_start_date', $payment->week_start_date)
            ->where('status', 'given')
            ->get();

        if ($pendingAdvances->isEmpty()) {
            return 0;
        }

        $totalDeducted = 0;

        foreach ($pendingAdvances as $advance) {
            $advance->update([
                'status' => 'deducted',
                'deducted_at' => now(),
                'weekly_salary_payment_id' => $payment->id,
            ]);
            $totalDeducted += $advance->amount;
        }

        return $totalDeducted;
    }

    /**
     * Mark multiple payments as paid.
     */
    public function markMultiplePaid(Request $request)
    {
        $validated = $request->validate([
            'payment_ids' => 'required|array',
            'payment_ids.*' => 'exists:weekly_salary_payments,id',
        ]);

        $count = 0;
        $totalAdvanceDeducted = 0;

        foreach ($validated['payment_ids'] as $paymentId) {
            $payment = WeeklySalaryPayment::find($paymentId);
            if ($payment && $payment->status === 'pending') {
                // Calculate and deduct advances
                $advanceDeducted = $this->deductAdvances($payment);
                $totalAdvanceDeducted += $advanceDeducted;

                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => auth()->id(),
                    'advance_deducted' => $advanceDeducted,
                    'net_salary' => $payment->total_salary - $advanceDeducted,
                ]);

                // Update labour costs on tasks where this employee worked
                $this->updateTaskLabourCosts($payment);

                $count++;
            }
        }

        $message = "{$count} payments marked as paid.";
        if ($totalAdvanceDeducted > 0) {
            $message .= " Total Rs." . number_format($totalAdvanceDeducted, 2) . " in advances deducted.";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Update labour costs on tasks based on paid salary.
     * Distributes the paid salary to tasks proportionally based on hours worked.
     */
    protected function updateTaskLabourCosts(WeeklySalaryPayment $payment)
    {
        // Get all attendances for this employee during the payment week
        $attendanceIds = Attendance::where('employee_id', $payment->employee_id)
            ->whereBetween('date', [$payment->week_start_date, $payment->week_end_date])
            ->whereNotNull('check_out_time')
            ->pluck('id');

        if ($attendanceIds->isEmpty()) {
            return;
        }

        // Get all task assignment sessions linked to these attendances
        $sessions = TaskAssignmentSession::whereIn('attendance_id', $attendanceIds)
            ->where('status', 'completed')
            ->with('taskAssignment.task')
            ->get();

        if ($sessions->isEmpty()) {
            return;
        }

        // Calculate total hours worked across all tasks
        $totalHoursOnTasks = $sessions->sum('hours');

        if ($totalHoursOnTasks <= 0) {
            return;
        }

        // Calculate effective hourly rate from paid salary
        $paidSalary = $payment->total_salary;
        $effectiveHourlyRate = $paidSalary / $totalHoursOnTasks;

        // Collect unique task IDs
        $taskIds = [];

        // Update task assignments with effective hourly rate
        foreach ($sessions as $session) {
            $assignment = $session->taskAssignment;
            if (!$assignment) {
                continue;
            }

            $taskIds[$assignment->task_id] = true;

            // Update hourly rate if it's not set or is zero
            if (empty($assignment->hourly_rate_at_time) || $assignment->hourly_rate_at_time == 0) {
                $assignment->update(['hourly_rate_at_time' => $effectiveHourlyRate]);
            }
        }

        // Recalculate costs for all affected tasks
        Task::whereIn('id', array_keys($taskIds))->each(function ($task) {
            $task->recalculateCosts();
        });
    }

    /**
     * Display payment history.
     */
    public function payments(Request $request)
    {
        $query = WeeklySalaryPayment::with(['employee.designation', 'employee.site', 'paidBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by site
        if ($request->filled('site_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('site_id', $request->site_id);
            });
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('week_start_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('week_end_date', '<=', $request->to_date);
        }

        $payments = $query->orderBy('week_start_date', 'desc')
            ->orderBy('employee_id')
            ->paginate(20);

        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        $sites = Site::orderBy('name')->get();

        // Summary statistics
        $summary = [
            'total_pending' => WeeklySalaryPayment::where('status', 'pending')->sum('total_salary'),
            'total_paid' => WeeklySalaryPayment::where('status', 'paid')->sum('total_salary'),
            'pending_count' => WeeklySalaryPayment::where('status', 'pending')->count(),
            'paid_count' => WeeklySalaryPayment::where('status', 'paid')->count(),
        ];

        return view('salary.payments', compact('payments', 'employees', 'sites', 'summary'));
    }

    /**
     * Show employee salary details.
     */
    public function employeeDetails(Request $request, Employee $employee)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);

        // Get attendance records for this week
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->whereNotNull('check_out_time')
            ->orderBy('date')
            ->get();

        // Get payment record
        $payment = WeeklySalaryPayment::where('employee_id', $employee->id)
            ->where('week_start_date', $weekStart)
            ->first();

        // Get payment history
        $paymentHistory = WeeklySalaryPayment::where('employee_id', $employee->id)
            ->orderBy('week_start_date', 'desc')
            ->limit(10)
            ->get();

        $summary = [
            'total_hours' => $attendances->sum('total_hours'),
            'total_days' => $attendances->count(),
            'total_salary' => $attendances->sum('daily_salary'),
        ];

        return view('salary.employee-details', compact(
            'employee',
            'attendances',
            'payment',
            'paymentHistory',
            'summary',
            'weekStart',
            'weekEnd',
            'date'
        ));
    }
}
