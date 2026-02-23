<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdvancePayment;
use App\Models\Employee;
use App\Models\WeeklySalaryPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdvancePaymentController extends Controller
{
    /**
     * Check if an employee is eligible for an advance.
     */
    public function checkEligibility(Request $request, Employee $employee)
    {
        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::today();
        $eligibility = AdvancePayment::getEligibility($employee->id, $date);

        // Get existing advances for this week
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);
        $existingAdvances = AdvancePayment::forEmployee($employee->id)
            ->forWeek($weekStart)
            ->whereIn('status', ['given', 'deducted'])
            ->with('givenBy')
            ->orderBy('given_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $employee->load(['designation', 'site']),
                'eligibility' => $eligibility,
                'existing_advances' => $existingAdvances,
            ],
        ]);
    }

    /**
     * Give an advance to an employee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = $request->has('date') ? Carbon::parse($validated['date']) : Carbon::today();
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);

        // Get eligibility
        $eligibility = AdvancePayment::getEligibility($validated['employee_id'], $date);

        // Validate eligibility
        if (!$eligibility['is_eligible']) {
            return response()->json([
                'success' => false,
                'message' => 'Employee is not eligible for advance',
                'reason' => $eligibility['reason'],
                'eligibility' => $eligibility,
            ], 422);
        }

        // Validate amount does not exceed available
        if ($validated['amount'] > $eligibility['available_amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Amount exceeds available advance',
                'max_available' => $eligibility['available_amount'],
                'eligibility' => $eligibility,
            ], 422);
        }

        // Create advance payment
        $advance = AdvancePayment::create([
            'employee_id' => $validated['employee_id'],
            'week_start_date' => $weekStart,
            'week_end_date' => $weekEnd,
            'amount' => $validated['amount'],
            'earned_salary_at_time' => $eligibility['earned_salary'],
            'days_worked_at_time' => $eligibility['days_worked'],
            'status' => 'given',
            'given_at' => now(),
            'given_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $advance->load(['employee', 'givenBy']);

        return response()->json([
            'success' => true,
            'message' => 'Advance payment created successfully',
            'data' => $advance,
        ], 201);
    }

    /**
     * List advance payments for the current or specified week.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = AdvancePayment::with(['employee.designation', 'employee.site', 'givenBy']);

        // Supervisors only see advances for employees at their site
        if ($user->isSupervisor() && $user->site_id) {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('site_id', $user->site_id);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by week
        if ($request->has('week_start_date')) {
            $query->where('week_start_date', $request->week_start_date);
        } else {
            // Default to current week
            [$weekStart, $weekEnd] = WeeklySalaryPayment::getCurrentWeekPeriod();
            $query->where('week_start_date', $weekStart);
        }

        $advances = $query->orderBy('given_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $advances,
        ]);
    }

    /**
     * List advances for a specific employee.
     */
    public function byEmployee(Request $request, Employee $employee)
    {
        $query = AdvancePayment::where('employee_id', $employee->id)
            ->with('givenBy');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by week
        if ($request->has('week_start_date')) {
            $query->where('week_start_date', $request->week_start_date);
        }

        // Limit results if requested
        if ($request->has('limit')) {
            $query->limit((int) $request->limit);
        }

        $advances = $query->orderBy('given_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $employee->load(['designation', 'site']),
                'advances' => $advances,
            ],
        ]);
    }

    /**
     * Cancel an advance payment.
     */
    public function cancel(Request $request, AdvancePayment $advance)
    {
        if ($advance->status !== 'given') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending advances can be cancelled',
            ], 422);
        }

        $advance->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advance payment cancelled successfully',
            'data' => $advance->fresh(['employee', 'givenBy']),
        ]);
    }
}
