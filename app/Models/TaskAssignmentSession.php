<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TaskAssignmentSession extends Model
{
    protected $fillable = [
        'task_assignment_id',
        'attendance_id',
        'date',
        'start_time',
        'end_time',
        'hours',
        'end_reason',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'hours' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($session) {
            // Auto-calculate hours when end_time is set
            if ($session->start_time && $session->end_time) {
                $start = Carbon::parse($session->start_time);
                $end = Carbon::parse($session->end_time);
                // Ensure positive hours (end - start), wrap with abs() for safety
                $minutes = $end->diffInMinutes($start);
                $session->hours = abs($minutes) / 60;
            }
        });

        static::saved(function ($session) {
            // Recalculate assignment hours when session is saved
            if ($session->status === 'completed') {
                $session->taskAssignment->recalculateHoursFromSessions();
            }
        });
    }

    public function taskAssignment()
    {
        return $this->belongsTo(TaskAssignment::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function isActive()
    {
        return $this->status === 'active' && is_null($this->end_time);
    }

    public function end(string $reason)
    {
        $this->update([
            'end_time' => now()->format('H:i:s'),
            'end_reason' => $reason,
            'status' => 'completed',
        ]);
    }

    public function getTask()
    {
        return $this->taskAssignment->task;
    }

    public function getEmployee()
    {
        return $this->taskAssignment->employee;
    }
}
