<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_code',
        'name',
        'phone',
        'designation_id',
        'employment_type',
        'salary_type',
        'hourly_rate',
        'daily_rate',
        'working_hours',
        'photo',
        'site_id',
        'status',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'working_hours' => 'decimal:2',
    ];

    /**
     * Get the hourly rate, calculating from daily rate if not set
     */
    public function getHourlyRateAttribute($value)
    {
        // If hourly_rate is already set and non-zero, use it
        if ($value && $value > 0) {
            return (float) $value;
        }

        // Otherwise calculate from daily_rate and working_hours
        if ($this->daily_rate && $this->working_hours && $this->working_hours > 0) {
            return round($this->daily_rate / $this->working_hours, 2);
        }

        // Return 0 if we can't calculate
        return 0;
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function taskAssignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function todayAttendance()
    {
        return $this->hasOne(Attendance::class)->whereDate('date', today());
    }

    public function weeklySalaryPayments()
    {
        return $this->hasMany(WeeklySalaryPayment::class);
    }

    public function advancePayments()
    {
        return $this->hasMany(AdvancePayment::class);
    }
}
