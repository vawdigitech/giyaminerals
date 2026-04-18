<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'project_id',
        'site_id',
        'factory_id',
        'parent_id',
        'code',
        'name',
        'description',
        'section',
        'priority',
        'status',
        'progress',
        'quoted_amount',
        'labor_cost',
        'material_cost',
        'actual_amount',
        'total_hours_worked',
        'aggregated_labor_cost',
        'aggregated_material_cost',
        'aggregated_actual_amount',
        'aggregated_quoted_amount',
        'start_date',
        'due_date',
        'completed_date',
    ];

    protected $casts = [
        'quoted_amount' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'total_hours_worked' => 'decimal:2',
        'aggregated_labor_cost' => 'decimal:2',
        'aggregated_material_cost' => 'decimal:2',
        'aggregated_actual_amount' => 'decimal:2',
        'aggregated_quoted_amount' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function factory()
    {
        return $this->belongsTo(Factory::class);
    }

    /**
     * Get all task locations (sites and factories where this task is assigned)
     */
    public function taskLocations()
    {
        return $this->hasMany(TaskLocation::class);
    }

    /**
     * Get all locations for this task
     */
    public function getLocations()
    {
        $locations = [];

        foreach ($this->taskLocations as $taskLocation) {
            $location = $taskLocation->getLocation();
            if ($location) {
                $locations[] = [
                    'type' => $taskLocation->location_type,
                    'id' => $taskLocation->location_id,
                    'name' => $location->name,
                    'progress' => $taskLocation->progress,
                    'status' => $taskLocation->status,
                    'labor_cost' => $taskLocation->labor_cost,
                    'material_cost' => $taskLocation->material_cost,
                    'total_cost' => $taskLocation->total_cost,
                    'model' => $location,
                ];
            }
        }

        return collect($locations);
    }

    /**
     * Get task location for a specific location
     */
    public function getTaskLocationFor($locationType, $locationId)
    {
        return $this->taskLocations()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->first();
    }

    /**
     * Check if task is assigned to a specific location
     */
    public function isAtLocation($locationType, $locationId)
    {
        return $this->taskLocations()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->exists();
    }

    /**
     * Get aggregated costs from all locations
     */
    public function getAggregatedCosts()
    {
        $laborCost = $this->taskLocations()->sum('labor_cost');
        $materialCost = $this->taskLocations()->sum('material_cost');
        $totalCost = $laborCost + $materialCost;

        return [
            'labor_cost' => $laborCost,
            'material_cost' => $materialCost,
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Get average progress from all locations
     */
    public function getAggregatedProgress()
    {
        $locationCount = $this->taskLocations()->count();
        if ($locationCount === 0) {
            return 0;
        }

        return (int) round($this->taskLocations()->avg('progress'));
    }

    /**
     * Get the location type (site or factory) - backward compatibility
     */
    public function getLocationType()
    {
        if ($this->site_id) {
            return 'site';
        } elseif ($this->factory_id) {
            return 'factory';
        }
        return null;
    }

    /**
     * Get the location name - backward compatibility
     */
    public function getLocationName()
    {
        if ($this->site_id && $this->site) {
            return $this->site->name;
        } elseif ($this->factory_id && $this->factory) {
            return $this->factory->name;
        }
        return 'No Location';
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Scope to filter project tasks
     */
    public function scopeProject($query, $projectId = null)
    {
        return $projectId
            ? $query->where('project_id', $projectId)
            : $query->whereNotNull('project_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    /**
     * Recursive relationship to load all nested subtasks
     */
    public function allSubtasks()
    {
        return $this->subtasks()->with('allSubtasks');
    }

    public function assignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function activeAssignments()
    {
        return $this->hasMany(TaskAssignment::class)->whereNull('removed_at');
    }

    public function workLogs()
    {
        return $this->hasManyThrough(TaskWorkLog::class, TaskAssignment::class);
    }

    public function stockUsages()
    {
        return $this->hasMany(TaskStockUsage::class);
    }

    public function assignedEmployees()
    {
        return $this->belongsToMany(Employee::class, 'task_assignments')
            ->whereNull('task_assignments.removed_at')
            ->withPivot(['assigned_at', 'hours_worked', 'hourly_rate_at_time']);
    }

    public function calculateLaborCost()
    {
        return $this->assignments()
            ->whereNull('removed_at')
            ->get()
            ->sum(function ($assignment) {
                return $assignment->hours_worked * $assignment->hourly_rate_at_time;
            });
    }

    public function calculateMaterialCost()
    {
        return $this->stockUsages->sum('total_cost');
    }

    public function recalculateCosts()
    {
        $this->labor_cost = $this->calculateLaborCost();
        $this->material_cost = $this->calculateMaterialCost();
        $this->actual_amount = $this->labor_cost + $this->material_cost;
        $this->total_hours_worked = $this->calculateTotalHoursWorked();

        \Log::info("Task direct costs calculated", [
            'task_id' => $this->id,
            'task_code' => $this->code,
            'labor_cost' => $this->labor_cost,
            'material_cost' => $this->material_cost,
            'actual_amount' => $this->actual_amount,
            'total_hours_worked' => $this->total_hours_worked
        ]);

        $this->save();

        // Recalculate location-specific costs
        foreach ($this->taskLocations as $taskLocation) {
            $taskLocation->recalculateCosts();
        }

        // Update this task's own aggregated costs (for leaf tasks, aggregated = direct)
        $this->recalculateAggregatedCosts();

        // If this is a subtask, parent's aggregated costs are already updated by the above call
    }

    public function calculateTotalHoursWorked()
    {
        return $this->assignments()
            ->whereNull('removed_at')
            ->sum('hours_worked');
    }

    /**
     * Recalculate progress from subtasks
     * For master tasks, progress is the average of all subtasks' progress
     */
    public function recalculateProgressFromSubtasks()
    {
        if (!$this->hasSubtasks()) {
            // Leaf task - progress is set directly, nothing to calculate
            return;
        }

        // Calculate average progress from all subtasks - use fresh query to avoid cached data
        $subtasks = $this->subtasks()->get();

        if ($subtasks->count() > 0) {
            $this->progress = (int) round($subtasks->avg('progress'));

            // Update status based on progress
            if ($this->progress >= 100) {
                $this->status = 'completed';
                $this->completed_date = now();
            } elseif ($this->progress > 0) {
                $this->status = 'in_progress';
                if (!$this->start_date) {
                    $this->start_date = now();
                }
            }

            $this->save();
        }

        // Propagate up to parent if exists
        if ($this->parent_id) {
            $this->parent->recalculateProgressFromSubtasks();
        }
    }

    /**
     * Recalculate aggregated costs from all subtasks
     * Propagates changes up to parent tasks
     */
    public function recalculateAggregatedCosts()
    {
        if (!$this->hasSubtasks()) {
            // For leaf tasks, aggregated values equal direct values (coalesce NULL to 0)
            $this->aggregated_labor_cost = $this->labor_cost ?? 0;
            $this->aggregated_material_cost = $this->material_cost ?? 0;
            $this->aggregated_actual_amount = $this->actual_amount ?? 0;
            $this->aggregated_quoted_amount = $this->quoted_amount ?? 0;

            \Log::info("Leaf task aggregated costs updated", [
                'task_id' => $this->id,
                'task_code' => $this->code,
                'labor_cost' => $this->labor_cost,
                'aggregated_labor_cost' => $this->aggregated_labor_cost,
                'parent_id' => $this->parent_id
            ]);

            $this->save();
            // Don't return early - need to propagate to parent
        } else {
            // Sum from all subtasks - use fresh query to avoid cached relationship data
            $subtasks = $this->subtasks()->get();

            // Only sum aggregated values (which already include direct values for leaf tasks)
            $this->aggregated_labor_cost = $subtasks->sum('aggregated_labor_cost') ?? 0;
            $this->aggregated_material_cost = $subtasks->sum('aggregated_material_cost') ?? 0;
            $this->aggregated_actual_amount = $this->aggregated_labor_cost + $this->aggregated_material_cost;
            $this->aggregated_quoted_amount = $subtasks->sum('aggregated_quoted_amount') ?? 0;
            $this->total_hours_worked = $subtasks->sum('total_hours_worked') ?? 0;

            \Log::info("Parent task aggregated costs updated", [
                'task_id' => $this->id,
                'task_code' => $this->code,
                'subtasks_count' => $subtasks->count(),
                'aggregated_labor_cost' => $this->aggregated_labor_cost,
                'aggregated_material_cost' => $this->aggregated_material_cost,
                'parent_id' => $this->parent_id
            ]);

            $this->save();
        }

        // Propagate up to parent if exists (works for both leaf and parent tasks)
        if ($this->parent_id) {
            \Log::info("Propagating costs to parent", [
                'child_task_id' => $this->id,
                'child_task_code' => $this->code,
                'parent_task_id' => $this->parent_id,
                'child_aggregated_labor_cost' => $this->aggregated_labor_cost,
            ]);

            $this->parent->refresh(); // Ensure parent has latest data
            $this->parent->recalculateAggregatedCosts();
        }
    }

    /**
     * Check if this task has subtasks
     */
    public function hasSubtasks()
    {
        return $this->subtasks()->exists();
    }

    /**
     * Check if this task is a subtask (has a parent)
     */
    public function isSubtask()
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if this task can be assigned employees
     * Only leaf tasks (tasks without subtasks) can have direct assignments
     */
    public function canBeAssigned()
    {
        return !$this->hasSubtasks();
    }

    /**
     * Validate if the task can have employees assigned
     * Throws exception if task has subtasks
     */
    public function validateCanAssign()
    {
        if ($this->hasSubtasks()) {
            throw new \Exception(
                'Cannot assign employees to a master task. Please assign to subtasks instead.'
            );
        }

        return true;
    }

    /**
     * Get all sessions for this task's assignments
     */
    public function sessions()
    {
        return $this->hasManyThrough(
            TaskAssignmentSession::class,
            TaskAssignment::class,
            'task_id',
            'task_assignment_id'
        );
    }

    /**
     * Get active sessions for this task
     */
    public function activeSessions()
    {
        return $this->sessions()->where('task_assignment_sessions.status', 'active');
    }

    /**
     * Get progress photos for this task
     */
    public function progressPhotos()
    {
        return $this->hasMany(TaskProgressPhoto::class)->orderBy('captured_date', 'desc');
    }

    /**
     * Get progress photos grouped by date
     */
    public function progressPhotosByDate()
    {
        return $this->progressPhotos()
            ->get()
            ->groupBy(function ($photo) {
                return $photo->captured_date->format('Y-m-d');
            });
    }

    /**
     * Check if a given task ID would create a circular reference if set as parent
     *
     * @param int $potentialParentId The ID of the task to be set as parent
     * @return bool True if it would create a circular reference, false otherwise
     */
    public function wouldCreateCircularReference($potentialParentId)
    {
        // Can't be your own parent
        if ($this->id == $potentialParentId) {
            return true;
        }

        // Check if the potential parent is a descendant of this task
        return $this->isAncestorOf($potentialParentId);
    }

    /**
     * Check if this task is an ancestor of another task
     *
     * @param int $taskId The ID of the task to check
     * @return bool True if this task is an ancestor of the given task
     */
    public function isAncestorOf($taskId)
    {
        $task = static::find($taskId);

        if (!$task) {
            return false;
        }

        // Traverse up the parent chain of the given task
        $currentParentId = $task->parent_id;
        $visited = []; // Prevent infinite loops in case of data corruption

        while ($currentParentId !== null) {
            // If we find this task in the parent chain, we are an ancestor
            if ($currentParentId == $this->id) {
                return true;
            }

            // Prevent infinite loops
            if (in_array($currentParentId, $visited)) {
                break;
            }
            $visited[] = $currentParentId;

            // Move up the chain
            $parent = static::find($currentParentId);
            $currentParentId = $parent ? $parent->parent_id : null;
        }

        return false;
    }

    /**
     * Get all ancestor tasks (parent, grandparent, etc.)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAncestors()
    {
        $ancestors = collect();
        $currentParentId = $this->parent_id;
        $visited = [];

        while ($currentParentId !== null) {
            // Prevent infinite loops
            if (in_array($currentParentId, $visited)) {
                break;
            }
            $visited[] = $currentParentId;

            $parent = static::find($currentParentId);
            if ($parent) {
                $ancestors->push($parent);
                $currentParentId = $parent->parent_id;
            } else {
                break;
            }
        }

        return $ancestors;
    }

    /**
     * Get the nesting level of this task (0 for master tasks, 1 for direct subtasks, etc.)
     *
     * @return int
     */
    public function getNestingLevel()
    {
        return $this->getAncestors()->count();
    }

    /**
     * Count all nested subtasks recursively
     *
     * @return int
     */
    public function countAllSubtasks()
    {
        $count = $this->subtasks()->count();

        foreach ($this->subtasks as $subtask) {
            $count += $subtask->countAllSubtasks();
        }

        return $count;
    }

    /**
     * Fix any negative hours in sessions and assignments
     */
    public function fixNegativeHours()
    {
        $fixed = false;

        // Fix sessions with negative hours
        foreach ($this->sessions()->where('hours', '<', 0)->get() as $session) {
            $session->hours = abs($session->hours);
            $session->saveQuietly();
            $fixed = true;
        }

        // Fix assignments with negative hours
        foreach ($this->assignments()->where('hours_worked', '<', 0)->get() as $assignment) {
            $assignment->hours_worked = abs($assignment->hours_worked);
            $assignment->saveQuietly();
            $fixed = true;
        }

        if ($fixed) {
            $this->recalculateCosts();
        }

        return $fixed;
    }
}
