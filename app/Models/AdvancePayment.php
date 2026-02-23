<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AdvancePayment extends Model
{
    protected $fillable = [
        'employee_id',
        'week_start_date',
        'week_end_date',
        'amount',
        'earned_salary_at_time',
        'days_worked_at_time',
        'status',
        'given_at',
        'given_by',
        'deducted_at',
        'weekly_salary_payment_id',
        'notes',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'amount' => 'decimal:2',
        'earned_salary_at_time' => 'decimal:2',
        'given_at' => 'datetime',
        'deducted_at' => 'datetime',
    ];

    /**
     * Get the employee that received the advance.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who gave the advance.
     */
    public function givenBy()
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    /**
     * Get the weekly salary payment where this advance was deducted.
     */
    public function weeklySalaryPayment()
    {
        return $this->belongsTo(WeeklySalaryPayment::class);
    }

    /**
     * Scope for a specific week.
     */
    public function scopeForWeek($query, $weekStart)
    {
        $weekStart = $weekStart instanceof Carbon ? $weekStart->toDateString() : $weekStart;
        return $query->where('week_start_date', $weekStart);
    }

    /**
     * Scope for a specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for pending advances (given but not deducted).
     */
    public function scopePending($query)
    {
        return $query->where('status', 'given');
    }

    /**
     * Scope for deducted advances.
     */
    public function scopeDeducted($query)
    {
        return $query->where('status', 'deducted');
    }

    /**
     * Scope for cancelled advances.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Get eligibility status for an employee to receive advance.
     *
     * @param int $employeeId
     * @param Carbon|string|null $date Date to check eligibility for (defaults to today)
     * @return array
     */
    public static function getEligibility($employeeId, $date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        [$weekStart, $weekEnd] = WeeklySalaryPayment::getWeekPeriod($date);

        // Check if weekly salary payment already exists and is paid
        $existingPayment = WeeklySalaryPayment::where('employee_id', $employeeId)
            ->where('week_start_date', $weekStart)
            ->first();

        $salaryAlreadyPaid = $existingPayment && $existingPayment->status === 'paid';

        // Get attendance records for this week (only completed attendance with checkout)
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->whereNotNull('check_out_time')
            ->get();

        $daysWorked = $attendances->count();
        $earnedSalary = $attendances->sum('daily_salary');

        // Get total advances already given for this week
        $totalAdvances = self::getTotalAdvancesForWeek($employeeId, $weekStart);

        // Calculate available amount for new advance
        // If salary is already paid, no advance is available
        $availableAmount = $salaryAlreadyPaid ? 0 : max(0, $earnedSalary - $totalAdvances);

        // Minimum days required (can be configured in settings)
        $minDaysRequired = (int) Setting::get('advance_min_days_required', 2);
        $advanceEnabled = Setting::get('advance_enabled', true);

        // Check eligibility - not eligible if salary already paid
        $isEligible = $advanceEnabled && !$salaryAlreadyPaid && $daysWorked >= $minDaysRequired && $availableAmount > 0;

        return [
            'is_eligible' => $isEligible,
            'week_start_date' => $weekStart->toDateString(),
            'week_end_date' => $weekEnd->toDateString(),
            'days_worked' => $daysWorked,
            'min_days_required' => $minDaysRequired,
            'earned_salary' => round($earnedSalary, 2),
            'total_advances_given' => round($totalAdvances, 2),
            'available_amount' => round($availableAmount, 2),
            'advance_enabled' => $advanceEnabled,
            'salary_already_paid' => $salaryAlreadyPaid,
            'reason' => self::getIneligibilityReason($advanceEnabled, $salaryAlreadyPaid, $daysWorked, $minDaysRequired, $availableAmount),
        ];
    }

    /**
     * Get the reason why an employee is not eligible for advance.
     */
    protected static function getIneligibilityReason($advanceEnabled, $salaryAlreadyPaid, $daysWorked, $minDaysRequired, $availableAmount)
    {
        if (!$advanceEnabled) {
            return 'Advance payments are currently disabled';
        }
        if ($salaryAlreadyPaid) {
            return 'Weekly salary has already been paid';
        }
        if ($daysWorked < $minDaysRequired) {
            return "Minimum {$minDaysRequired} days of work required (currently {$daysWorked} days)";
        }
        if ($availableAmount <= 0) {
            return 'No available salary for advance (already fully advanced)';
        }
        return null;
    }

    /**
     * Get total advances given for a specific week.
     *
     * @param int $employeeId
     * @param Carbon|string $weekStart
     * @return float
     */
    public static function getTotalAdvancesForWeek($employeeId, $weekStart)
    {
        $weekStart = $weekStart instanceof Carbon ? $weekStart->toDateString() : $weekStart;

        return (float) self::where('employee_id', $employeeId)
            ->where('week_start_date', $weekStart)
            ->whereIn('status', ['given', 'deducted']) // Don't count cancelled
            ->sum('amount');
    }

    /**
     * Check if this advance is pending (given but not deducted).
     */
    public function isPending(): bool
    {
        return $this->status === 'given';
    }

    /**
     * Check if this advance has been deducted.
     */
    public function isDeducted(): bool
    {
        return $this->status === 'deducted';
    }

    /**
     * Check if this advance has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
