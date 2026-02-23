<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WeeklySalaryPayment extends Model
{
    protected $fillable = [
        'employee_id',
        'week_start_date',
        'week_end_date',
        'total_hours',
        'total_days',
        'total_salary',
        'advance_deducted',
        'net_salary',
        'status',
        'paid_at',
        'paid_by',
        'notes',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate net_salary when creating or updating
        static::saving(function ($model) {
            if ($model->isDirty(['total_salary', 'advance_deducted'])) {
                $model->net_salary = $model->total_salary - ($model->advance_deducted ?? 0);
            }
        });
    }

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'total_hours' => 'decimal:2',
        'total_salary' => 'decimal:2',
        'advance_deducted' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get the advance payments for this weekly salary payment.
     */
    public function advances()
    {
        return $this->hasMany(AdvancePayment::class);
    }

    /**
     * Get pending advances for the same week that haven't been deducted yet.
     */
    public function pendingAdvances()
    {
        return AdvancePayment::where('employee_id', $this->employee_id)
            ->where('week_start_date', $this->week_start_date)
            ->where('status', 'given')
            ->get();
    }

    /**
     * Get the week period (Saturday to Friday) for a given date.
     *
     * @param Carbon|string $date
     * @return array [Carbon $saturday, Carbon $friday]
     */
    public static function getWeekPeriod($date): array
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dayOfWeek = $date->dayOfWeek; // 0=Sunday, 6=Saturday

        // Find previous Saturday (or today if Saturday)
        // Saturday is dayOfWeek = 6
        // Days to subtract: (dayOfWeek + 1) % 7 gives us days since last Saturday
        $saturday = $date->copy()->subDays(($dayOfWeek + 1) % 7);
        $friday = $saturday->copy()->addDays(6);

        return [$saturday, $friday];
    }

    /**
     * Get the current week period.
     *
     * @return array [Carbon $saturday, Carbon $friday]
     */
    public static function getCurrentWeekPeriod(): array
    {
        return self::getWeekPeriod(Carbon::today());
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
