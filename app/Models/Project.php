<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'site_id',
        'quoted_amount',
        'actual_amount',
        'status',
        'progress',
        'start_date',
        'end_date',
        'actual_end_date',
        'created_by',
    ];

    protected $casts = [
        'quoted_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calculateProgress()
    {
        $tasks = $this->tasks;
        if ($tasks->isEmpty()) {
            return 0;
        }
        return round($tasks->avg('progress'));
    }

    public function calculateActualAmount()
    {
        return $this->tasks->sum('actual_amount');
    }

    public function getProfitLossAttribute()
    {
        return $this->quoted_amount - $this->actual_amount;
    }

    /**
     * Automatically update project status based on task statuses
     * - Pending → In Progress: when any task is in_progress or has progress > 0
     * - In Progress → Completed: when all tasks are completed
     * - On Hold: remains manual (not auto-changed)
     */
    public function updateStatusFromTasks()
    {
        // Don't auto-change if project is on_hold (manual status)
        if ($this->status === 'on_hold') {
            return;
        }

        $tasks = $this->tasks;

        // If no tasks, keep current status
        if ($tasks->isEmpty()) {
            return;
        }

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $inProgressTasks = $tasks->where('status', 'in_progress')->count();
        $hasProgress = $tasks->where('progress', '>', 0)->count() > 0;

        // All tasks completed → project completed
        if ($completedTasks === $totalTasks) {
            $this->status = 'completed';
            $this->save();
            return;
        }

        // Any task in progress or has progress → project in progress
        if ($inProgressTasks > 0 || $hasProgress || $completedTasks > 0) {
            if ($this->status === 'pending') {
                $this->status = 'in_progress';
                $this->save();
            }
            return;
        }
    }

    /**
     * Update project progress based on tasks
     */
    public function updateProgressFromTasks()
    {
        $progress = $this->calculateProgress();
        $this->progress = $progress;
        $this->save();
    }
}
