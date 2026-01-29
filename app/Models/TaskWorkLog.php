<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskWorkLog extends Model
{
    protected $fillable = [
        'task_assignment_id',
        'date',
        'hours',
        'start_time',
        'end_time',
        'notes',
        'logged_by',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saved(function ($workLog) {
            // Update total hours on assignment
            $assignment = $workLog->assignment;
            $assignment->hours_worked = $assignment->workLogs()->sum('hours');
            $assignment->save();

            // Recalculate task costs
            $assignment->task->recalculateCosts();
        });

        static::deleted(function ($workLog) {
            $assignment = $workLog->assignment;
            $assignment->hours_worked = $assignment->workLogs()->sum('hours');
            $assignment->save();
            $assignment->task->recalculateCosts();
        });
    }

    public function assignment()
    {
        return $this->belongsTo(TaskAssignment::class, 'task_assignment_id');
    }

    public function loggedBy()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
