<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdvancePayment;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskAssignmentSession;
use App\Models\WeeklySalaryPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    /**
     * Get an employee's weekly salary summary for the current or specified week.
     */
    public function getWeeklySummary(Request $request, Employee $employee)
    {
        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::today();
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);

        // Get all attendance records for this employee in the week
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->whereNotNull('check_out_time')
            ->orderBy('date')
            ->get();

        $totalHours = $attendances->sum('total_hours');
        $totalSalary = $attendances->sum('daily_salary');
        $totalDays = $attendances->count();

        // Check if there's already a payment record for this week
        $payment = WeeklySalaryPayment::where('employee_id', $employee->id)
            ->where('week_start_date', $weekStart)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $employee,
                'week_start_date' => $weekStart->toDateString(),
                'week_end_date' => $weekEnd->toDateString(),
                'attendances' => $attendances,
                'summary' => [
                    'total_hours' => round($totalHours, 2),
                    'total_days' => $totalDays,
                    'total_salary' => round($totalSalary, 2),
                ],
                'payment' => $payment,
            ],
        ]);
    }

    /**
     * Get weekly salary report for all employees.
     */
    public function getWeeklyReport(Request $request)
    {
        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::today();
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);

        $user = $request->user();

        // Build employee query
        $employeeQuery = Employee::where('status', 'active');

        // Supervisors only see employees at their site
        if ($user->isSupervisor() && $user->site_id) {
            $employeeQuery->where('site_id', $user->site_id);
        }

        // Optional site filter
        if ($request->has('site_id')) {
            $employeeQuery->where('site_id', $request->site_id);
        }

        $employees = $employeeQuery->get();

        $report = [];
        $grandTotalHours = 0;
        $grandTotalSalary = 0;

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

            $report[] = [
                'employee' => $employee,
                'total_hours' => round($totalHours, 2),
                'total_days' => $totalDays,
                'total_salary' => round($totalSalary, 2),
                'payment_status' => $payment ? $payment->status : null,
                'payment_id' => $payment ? $payment->id : null,
            ];

            $grandTotalHours += $totalHours;
            $grandTotalSalary += $totalSalary;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'week_start_date' => $weekStart->toDateString(),
                'week_end_date' => $weekEnd->toDateString(),
                'employees' => $report,
                'grand_total' => [
                    'total_hours' => round($grandTotalHours, 2),
                    'total_salary' => round($grandTotalSalary, 2),
                ],
            ],
        ]);
    }

    /**
     * Generate weekly payment records for a specific week.
     */
    public function generateWeeklyPayments(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $date = Carbon::parse($validated['date']);
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);

        $user = $request->user();

        // Build employee query
        $employeeQuery = Employee::where('status', 'active');

        // Supervisors only see employees at their site
        if ($user->isSupervisor() && $user->site_id) {
            $employeeQuery->where('site_id', $user->site_id);
        }

        // Filter by specific employee IDs if provided
        if (!empty($validated['employee_ids'])) {
            $employeeQuery->whereIn('id', $validated['employee_ids']);
        }

        $employees = $employeeQuery->get();

        $created = [];
        $skipped = [];

        foreach ($employees as $employee) {
            // Check if payment already exists
            $existing = WeeklySalaryPayment::where('employee_id', $employee->id)
                ->where('week_start_date', $weekStart)
                ->first();

            if ($existing) {
                $skipped[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'reason' => 'Payment record already exists',
                ];
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
                $skipped[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'reason' => 'No attendance records for this week',
                ];
                continue;
            }

            $payment = WeeklySalaryPayment::create([
                'employee_id' => $employee->id,
                'week_start_date' => $weekStart,
                'week_end_date' => $weekEnd,
                'total_hours' => $totalHours,
                'total_days' => $totalDays,
                'total_salary' => $totalSalary,
                'status' => 'pending',
            ]);

            $created[] = $payment;
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' payment records created',
            'data' => [
                'week_start_date' => $weekStart->toDateString(),
                'week_end_date' => $weekEnd->toDateString(),
                'created' => $created,
                'skipped' => $skipped,
            ],
        ]);
    }

    /**
     * Mark a weekly salary payment as paid.
     */
    public function markAsPaid(Request $request, WeeklySalaryPayment $payment)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        if ($payment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Payment is already marked as paid',
            ], 422);
        }

        if ($payment->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot mark a cancelled payment as paid',
            ], 422);
        }

        // Calculate and deduct advances
        $advanceDeducted = $this->deductAdvances($payment);

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? $payment->notes,
            'advance_deducted' => $advanceDeducted,
            'net_salary' => $payment->total_salary - $advanceDeducted,
        ]);

        // Update labour costs on tasks where this employee worked during the payment period
        $this->updateTaskLabourCosts($payment);

        $payment->load('employee', 'paidBy');

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as paid',
            'data' => $payment,
            'advance_deducted' => $advanceDeducted,
        ]);
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
     * List payment records with filters.
     */
    public function listPayments(Request $request)
    {
        $user = $request->user();

        $query = WeeklySalaryPayment::with(['employee', 'paidBy']);

        // Supervisors only see payments for employees at their site
        if ($user->isSupervisor() && $user->site_id) {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('site_id', $user->site_id);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by week
        if ($request->has('week_start_date')) {
            $query->where('week_start_date', $request->week_start_date);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('week_start_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('week_end_date', '<=', $request->to_date);
        }

        $payments = $query->orderBy('week_start_date', 'desc')
            ->orderBy('employee_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }
}
