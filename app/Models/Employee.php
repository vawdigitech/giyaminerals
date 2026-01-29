<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_code',
        'name',
        'phone',
        'role',
        'employment_type',
        'hourly_rate',
        'photo',
        'site_id',
        'status',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function taskAssignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function todayAttendance()
    {
        return $this->hasOne(Attendance::class)->whereDate('date', today());
    }
}
