<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdvancePayment;
use App\Models\Employee;
use App\Models\Site;
use App\Models\WeeklySalaryPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdvancePaymentController extends Controller
{
    /**
     * Display a listing of advance payments.
     */
    public function index(Request $request)
    {
        $query = AdvancePayment::with(['employee.designation', 'employee.site', 'givenBy']);

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

        // Filter by week
        if ($request->filled('week_start_date')) {
            $query->where('week_start_date', $request->week_start_date);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('week_start_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('week_end_date', '<=', $request->to_date);
        }

        $advances = $query->orderBy('given_at', 'desc')
            ->paginate(20);

        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        $sites = Site::orderBy('name')->get();

        // Get current week dates
        [$currentWeekStart, $currentWeekEnd] = WeeklySalaryPayment::getCurrentWeekPeriod();

        // Summary statistics
        $summary = [
            'total_given_this_week' => AdvancePayment::forWeek($currentWeekStart)
                ->whereIn('status', ['given', 'deducted'])
                ->sum('amount'),
            'total_pending_deduction' => AdvancePayment::pending()->sum('amount'),
            'pending_count' => AdvancePayment::pending()->count(),
            'given_count_this_week' => AdvancePayment::forWeek($currentWeekStart)
                ->whereIn('status', ['given', 'deducted'])
                ->count(),
        ];

        return view('advances.index', compact(
            'advances',
            'employees',
            'sites',
            'summary',
            'currentWeekStart',
            'currentWeekEnd'
        ));
    }

    /**
     * Show the form for giving an advance.
     */
    public function create(Request $request)
    {
        $employees = Employee::where('status', 'active')
            ->with(['designation', 'site'])
            ->orderBy('name')
            ->get();

        $sites = Site::orderBy('name')->get();

        // Get current week dates
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getCurrentWeekPeriod();

        // Pre-select employee if provided
        $selectedEmployee = null;
        $eligibility = null;

        if ($request->filled('employee_id')) {
            $selectedEmployee = Employee::find($request->employee_id);
            if ($selectedEmployee) {
                $eligibility = AdvancePayment::getEligibility($selectedEmployee->id);
            }
        }

        return view('advances.create', compact(
            'employees',
            'sites',
            'weekStart',
            'weekEnd',
            'selectedEmployee',
            'eligibility'
        ));
    }

    /**
     * Store a new advance payment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = $request->filled('date') ? Carbon::parse($validated['date']) : Carbon::today();
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);

        // Get eligibility
        $eligibility = AdvancePayment::getEligibility($validated['employee_id'], $date);

        // Validate eligibility
        if (!$eligibility['is_eligible']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Employee is not eligible for advance: ' . $eligibility['reason']);
        }

        // Validate amount does not exceed available
        if ($validated['amount'] > $eligibility['available_amount']) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Amount exceeds available advance. Maximum available: Rs." . number_format($eligibility['available_amount'], 2));
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
            'given_by' => auth()->id(),
            'notes' => $validated['notes'],
        ]);

        $employee = Employee::find($validated['employee_id']);

        return redirect()->route('advances.index')
            ->with('success', "Advance of Rs." . number_format($validated['amount'], 2) . " given to {$employee->name}");
    }

    /**
     * Cancel an advance payment.
     */
    public function cancel(Request $request, AdvancePayment $advance)
    {
        if ($advance->status !== 'given') {
            return redirect()->back()
                ->with('error', 'Only pending advances can be cancelled.');
        }

        $advance->update([
            'status' => 'cancelled',
        ]);

        return redirect()->back()
            ->with('success', "Advance payment cancelled successfully.");
    }

    /**
     * AJAX endpoint to check employee eligibility.
     */
    public function checkEligibility(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'nullable|date',
        ]);

        $date = $request->filled('date') ? Carbon::parse($validated['date']) : Carbon::today();
        $eligibility = AdvancePayment::getEligibility($validated['employee_id'], $date);

        // Get existing advances for this week
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);
        $existingAdvances = AdvancePayment::forEmployee($validated['employee_id'])
            ->forWeek($weekStart)
            ->whereIn('status', ['given', 'deducted'])
            ->with('givenBy')
            ->orderBy('given_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'eligibility' => $eligibility,
            'existing_advances' => $existingAdvances,
        ]);
    }
}
