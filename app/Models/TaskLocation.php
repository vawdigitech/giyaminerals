<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskLocation extends Model
{
    protected $fillable = [
        'task_id',
        'location_type',
        'location_id',
        'progress',
        'status',
        'labor_cost',
        'material_cost',
        'total_cost',
    ];

    protected $casts = [
        'progress' => 'integer',
        'labor_cost' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the location (site or factory) for this task location
     */
    public function getLocation()
    {
        if ($this->location_type === 'site') {
            return Site::find($this->location_id);
        } elseif ($this->location_type === 'factory') {
            return Factory::find($this->location_id);
        }
        return null;
    }

    /**
     * Get the location name
     */
    public function getLocationName()
    {
        $location = $this->getLocation();
        return $location ? $location->name : 'Unknown Location';
    }

    /**
     * Recalculate costs from assignments and stock usages at this location
     */
    public function recalculateCosts()
    {
        // Get all assignments for this task at this location
        $laborCost = \DB::table('task_assignments')
            ->where('task_id', $this->task_id)
            ->where('location_type', $this->location_type)
            ->where('location_id', $this->location_id)
            ->whereNull('removed_at')
            ->sum(\DB::raw('hours_worked * hourly_rate_at_time'));

        // Get all stock usages for this task at this location
        $materialCost = \DB::table('task_stock_usages')
            ->where('task_id', $this->task_id)
            ->where('location_type', $this->location_type)
            ->where('location_id', $this->location_id)
            ->sum('total_cost');

        $this->labor_cost = $laborCost;
        $this->material_cost = $materialCost;
        $this->total_cost = $laborCost + $materialCost;
        $this->save();

        return $this;
    }
}
