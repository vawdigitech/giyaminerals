<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'site_id',
        'date',
        'check_in_time',
        'check_out_time',
        'check_in_photo',
        'check_out_photo',
        'check_in_location',
        'check_out_location',
        'total_hours',
        'daily_salary',
        'overtime_hours',
        'overtime_salary',
        'overtime_applicable',
        'daily_rate_at_time',
        'working_hours_at_time',
        'status',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'total_hours' => 'decimal:2',
        'daily_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_salary' => 'decimal:2',
        'overtime_applicable' => 'boolean',
        'daily_rate_at_time' => 'decimal:2',
        'working_hours_at_time' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($attendance) {
            if ($attendance->check_in_time && $attendance->check_out_time) {
                // Use check_out - check_in to ensure positive value, wrap with abs() for safety
                $minutes = $attendance->check_out_time->diffInMinutes($attendance->check_in_time);
                $attendance->total_hours = abs($minutes) / 60;

                // Calculate daily salary using prorated approach
                // All employees use daily rate, prorated by hours worked
                if ($attendance->daily_rate_at_time && $attendance->working_hours_at_time > 0) {
                    $hourlyEquivalent = $attendance->daily_rate_at_time / $attendance->working_hours_at_time;
                    $attendance->daily_salary = $attendance->total_hours * $hourlyEquivalent;

                    // Overtime calculation
                    if (Setting::get('overtime_enabled', true)) {
                        $threshold = (float) Setting::get('overtime_threshold_hours', 2.5);
                        $bonusPercent = (float) Setting::get('overtime_bonus_percentage', 50);
                        $standardHours = $attendance->working_hours_at_time ?? 8;

                        if ($attendance->total_hours >= ($standardHours + $threshold)) {
                            $attendance->overtime_applicable = true;
                            $attendance->overtime_hours = $attendance->total_hours - $standardHours;
                            $attendance->overtime_salary = ($attendance->daily_rate_at_time * $bonusPercent) / 100;
                            $attendance->daily_salary += $attendance->overtime_salary;
                        } else {
                            $attendance->overtime_applicable = false;
                            $attendance->overtime_hours = 0;
                            $attendance->overtime_salary = 0;
                        }
                    } else {
                        $attendance->overtime_applicable = false;
                        $attendance->overtime_hours = 0;
                        $attendance->overtime_salary = 0;
                    }
                }
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function sessions()
    {
        return $this->hasMany(TaskAssignmentSession::class);
    }

    public function activeSessions()
    {
        return $this->hasMany(TaskAssignmentSession::class)->where('status', 'active');
    }

    public function completedSessions()
    {
        return $this->hasMany(TaskAssignmentSession::class)->where('status', 'completed');
    }

    public function isCheckedIn()
    {
        return !is_null($this->check_in_time) && is_null($this->check_out_time);
    }

    public function isCheckedOut()
    {
        return !is_null($this->check_out_time);
    }
}
