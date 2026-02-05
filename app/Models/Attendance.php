<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'site_id',
        'date',
        'check_in_time',
        'check_out_time',
        'check_in_photo',
        'check_out_photo',
        'check_in_location',
        'check_out_location',
        'total_hours',
        'status',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'total_hours' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($attendance) {
            if ($attendance->check_in_time && $attendance->check_out_time) {
                // Use check_out - check_in to ensure positive value, wrap with abs() for safety
                $minutes = $attendance->check_out_time->diffInMinutes($attendance->check_in_time);
                $attendance->total_hours = abs($minutes) / 60;
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
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

    public function isCheckedIn()
    {
        return !is_null($this->check_in_time) && is_null($this->check_out_time);
    }

    public function isCheckedOut()
    {
        return !is_null($this->check_out_time);
    }
}
