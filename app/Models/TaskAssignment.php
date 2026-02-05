<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskAssignment extends Model
{
    protected $fillable = [
        'task_id',
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

        // If hourly_rate_at_time is 0, try to get the current employee rate
        if (empty($this->hourly_rate_at_time) || $this->hourly_rate_at_time == 0) {
            $employeeRate = $this->employee->hourly_rate ?? 0;
            if ($employeeRate > 0) {
                $updateData['hourly_rate_at_time'] = $employeeRate;
            }
        }

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
            $employeeRate = $this->employee->hourly_rate ?? 0;
            if ($employeeRate > 0) {
                $this->update(['hourly_rate_at_time' => $employeeRate]);
                return true;
            }
        }
        return false;
    }
}
