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
        'factory_daily_rate',
        'factory_hourly_rate',
        'factory_working_hours',
        'photo',
        'site_id',
        'factory_id',
        'status',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'working_hours' => 'decimal:2',
        'factory_daily_rate' => 'decimal:2',
        'factory_hourly_rate' => 'decimal:2',
        'factory_working_hours' => 'decimal:2',
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

    public function factory()
    {
        return $this->belongsTo(Factory::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Check if employee works at a factory
     */
    public function isFactoryEmployee()
    {
        return !is_null($this->factory_id);
    }

    /**
     * Check if employee works at a site
     */
    public function isSiteEmployee()
    {
        return !is_null($this->site_id);
    }

    /**
     * Get the work location (factory or site name)
     */
    public function getWorkLocation()
    {
        if ($this->isFactoryEmployee()) {
            return $this->factory ? $this->factory->name : 'Unknown Factory';
        }

        if ($this->isSiteEmployee()) {
            return $this->site ? $this->site->name : 'Unknown Site';
        }

        return 'No Location Assigned';
    }

    /**
     * Get the work location type
     */
    public function getWorkLocationType()
    {
        if ($this->isFactoryEmployee()) {
            return 'factory';
        }

        if ($this->isSiteEmployee()) {
            return 'site';
        }

        return null;
    }

    /**
     * Get the appropriate daily rate based on attendance location
     *
     * @param \App\Models\Attendance|object|null $attendance
     * @return float
     */
    public function getDailyRateForAttendance($attendance = null)
    {
        // Determine if it's a factory attendance
        $isFactory = false;
        if ($attendance) {
            if (is_object($attendance) && method_exists($attendance, 'isFactoryAttendance')) {
                $isFactory = $attendance->isFactoryAttendance();
            } elseif (is_object($attendance) && property_exists($attendance, 'factory_id')) {
                $isFactory = !is_null($attendance->factory_id);
            }
        }

        // If attendance is at a factory, use factory rate if available
        if ($isFactory) {
            return $this->factory_daily_rate ?? $this->daily_rate ?? 0;
        }

        // Otherwise use site/default daily rate
        return $this->daily_rate ?? 0;
    }

    /**
     * Get the appropriate hourly rate based on attendance location
     *
     * @param \App\Models\Attendance|object|null $attendance
     * @return float
     */
    public function getHourlyRateForAttendance($attendance = null)
    {
        // Determine if it's a factory attendance
        $isFactory = false;
        $factoryId = null;
        $siteId = null;

        if ($attendance) {
            if (is_object($attendance) && method_exists($attendance, 'isFactoryAttendance')) {
                $isFactory = $attendance->isFactoryAttendance();
                $factoryId = $attendance->factory_id ?? null;
                $siteId = $attendance->site_id ?? null;
            } elseif (is_object($attendance) && property_exists($attendance, 'factory_id')) {
                $isFactory = !is_null($attendance->factory_id);
                $factoryId = $attendance->factory_id ?? null;
                $siteId = $attendance->site_id ?? null;
            }
        }

        $calculatedRate = 0;
        $rateSource = 'none';

        // If attendance is at a factory
        if ($isFactory) {
            // Use factory hourly rate if set
            if ($this->factory_hourly_rate && $this->factory_hourly_rate > 0) {
                $calculatedRate = (float) $this->factory_hourly_rate;
                $rateSource = 'factory_hourly_rate';
            }
            // Calculate from factory daily rate and working hours
            elseif ($this->factory_daily_rate && $this->factory_working_hours && $this->factory_working_hours > 0) {
                $calculatedRate = round($this->factory_daily_rate / $this->factory_working_hours, 2);
                $rateSource = 'factory_daily_rate / factory_working_hours';
            }
            // Fall back to regular rates if factory rates not set
            elseif ($this->hourly_rate && $this->hourly_rate > 0) {
                $calculatedRate = (float) $this->hourly_rate;
                $rateSource = 'hourly_rate (factory fallback)';
            }
            elseif ($this->daily_rate && $this->working_hours && $this->working_hours > 0) {
                $calculatedRate = round($this->daily_rate / $this->working_hours, 2);
                $rateSource = 'daily_rate / working_hours (factory fallback)';
            }
        } else {
            // Use site/default hourly rate
            if ($this->hourly_rate && $this->hourly_rate > 0) {
                $calculatedRate = (float) $this->hourly_rate;
                $rateSource = 'hourly_rate (site)';
            }
            // Calculate from daily rate and working hours
            elseif ($this->daily_rate && $this->working_hours && $this->working_hours > 0) {
                $calculatedRate = round($this->daily_rate / $this->working_hours, 2);
                $rateSource = 'daily_rate / working_hours (site)';
            }
        }

        \Log::info("Employee hourly rate calculated", [
            'employee_id' => $this->id,
            'employee_name' => $this->name,
            'is_factory' => $isFactory,
            'factory_id' => $factoryId,
            'site_id' => $siteId,
            'factory_hourly_rate' => $this->factory_hourly_rate,
            'factory_daily_rate' => $this->factory_daily_rate,
            'factory_working_hours' => $this->factory_working_hours,
            'hourly_rate' => $this->hourly_rate,
            'daily_rate' => $this->daily_rate,
            'working_hours' => $this->working_hours,
            'calculated_rate' => $calculatedRate,
            'rate_source' => $rateSource,
        ]);

        return $calculatedRate;
    }

    /**
     * Get the appropriate working hours based on attendance location
     *
     * @param \App\Models\Attendance|object|null $attendance
     * @return float
     */
    public function getWorkingHoursForAttendance($attendance = null)
    {
        // Determine if it's a factory attendance
        $isFactory = false;
        if ($attendance) {
            if (is_object($attendance) && method_exists($attendance, 'isFactoryAttendance')) {
                $isFactory = $attendance->isFactoryAttendance();
            } elseif (is_object($attendance) && property_exists($attendance, 'factory_id')) {
                $isFactory = !is_null($attendance->factory_id);
            }
        }

        // If attendance is at a factory, use factory working hours if available
        if ($isFactory) {
            return $this->factory_working_hours ?? $this->working_hours ?? 8;
        }

        // Otherwise use site/default working hours
        return $this->working_hours ?? 8;
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
