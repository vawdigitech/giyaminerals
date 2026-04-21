<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteIssue extends Model
{
    protected $fillable = [
        'site_id',
        'factory_id',
        'task_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'photos',
        'reported_by',
        'assigned_to',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'photos' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function factory()
    {
        return $this->belongsTo(Factory::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the location type (site or factory)
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
     * Get the location name
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

    /**
     * Check if issue is at factory
     */
    public function isFactoryIssue()
    {
        return !is_null($this->factory_id);
    }

    /**
     * Check if issue is at site
     */
    public function isSiteIssue()
    {
        return !is_null($this->site_id);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }
}
