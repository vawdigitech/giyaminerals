<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteIssue extends Model
{
    protected $fillable = [
        'site_id',
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

    public function task()
    {
        return $this->belongsTo(Task::class);
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
