<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskAssignment extends Model
{
    protected $fillable = [
        'task_id',
        'location_type',
        'location_id',
        'employee_id',
        'assigned_by',
        'assigned_at',
        'removed_at',
        'removed_by',
        'hours_worked',
        'hourly_rate_at_time',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'removed_at' => 'datetime',
        'hours_worked' => 'decimal:2',
        'hourly_rate_at_time' => 'decimal:2',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function removedBy()
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function workLogs()
    {
        return $this->hasMany(TaskWorkLog::class);
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

    public function isActive()
    {
        return is_null($this->removed_at);
    }

    public function hasActiveSession()
    {
        return $this->activeSessions()->exists();
    }

    public function getCurrentSession()
    {
        return $this->activeSessions()->whereDate('date', today())->first();
    }

    public function getTotalCostAttribute()
    {
        return $this->hours_worked * $this->hourly_rate_at_time;
    }

    /**
     * Recalculate hours_worked from all completed sessions
     */
    public function recalculateHoursFromSessions()
    {
        $totalHours = $this->completedSessions()->sum('hours');

        // Ensure hours are positive
        $totalHours = abs($totalHours);

        $updateData = ['hours_worked' => $totalHours];

        // Get the most recent completed session to determine the attendance location
        $latestSession = $this->completedSessions()->with('attendance')->latest('date')->first();

        // Always update the rate based on actual attendance location where work was done
        if ($latestSession && $latestSession->attendance) {
            // Use location-aware rate based on the actual attendance
            $employeeRate = $this->employee->getHourlyRateForAttendance($latestSession->attendance);

            if ($employeeRate > 0) {
                $updateData['hourly_rate_at_time'] = $employeeRate;
            }
        } elseif (empty($this->hourly_rate_at_time) || $this->hourly_rate_at_time == 0) {
            // Fallback only if no sessions and rate is 0: Get rate based on assignment location
            $attendance = new \stdClass();
            $attendance->factory_id = $this->location_type === 'factory' ? $this->location_id : null;
            $attendance->site_id = $this->location_type === 'site' ? $this->location_id : null;
            $employeeRate = $this->employee->getHourlyRateForAttendance($attendance);

            if ($employeeRate > 0) {
                $updateData['hourly_rate_at_time'] = $employeeRate;
            }
        }

        \Log::info("Task assignment hours recalculated", [
            'assignment_id' => $this->id,
            'task_id' => $this->task_id,
            'employee_id' => $this->employee_id,
            'location_type' => $this->location_type,
            'location_id' => $this->location_id,
            'total_hours' => $totalHours,
            'hourly_rate' => $updateData['hourly_rate_at_time'] ?? $this->hourly_rate_at_time,
            'labor_cost' => $totalHours * ($updateData['hourly_rate_at_time'] ?? $this->hourly_rate_at_time),
        ]);

        $this->update($updateData);

        // Recalculate task costs
        $this->task->recalculateCosts();

        return $totalHours;
    }

    /**
     * Sync hourly rate from employee if assignment rate is 0
     */
    public function syncHourlyRateFromEmployee()
    {
        if (empty($this->hourly_rate_at_time) || $this->hourly_rate_at_time == 0) {
            // Get employee's today attendance to determine appropriate rate
            $todayAttendance = $this->employee->todayAttendance()->first();

            // If no attendance today, create a mock attendance based on assignment location
            if (!$todayAttendance) {
                $todayAttendance = new \stdClass();
                $todayAttendance->factory_id = $this->location_type === 'factory' ? $this->location_id : null;
                $todayAttendance->site_id = $this->location_type === 'site' ? $this->location_id : null;
            }

            $employeeRate = $this->employee->getHourlyRateForAttendance($todayAttendance) ?? 0;

            if ($employeeRate > 0) {
                $this->update(['hourly_rate_at_time' => $employeeRate]);
                return true;
            }
        }
        return false;
    }
}
