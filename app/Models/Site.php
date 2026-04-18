<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Site extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'location'
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Many-to-many relationship with projects through project_locations
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_locations')
            ->withTimestamps();
    }

    /**
     * Get tasks at this site
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function issues()
    {
        return $this->hasMany(SiteIssue::class);
    }
}