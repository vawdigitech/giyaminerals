<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceBreak extends Model
{
    protected $fillable = [
        'attendance_id',
        'break_out_time',
        'break_in_time',
    ];

    protected $casts = [
        'break_out_time' => 'datetime',
        'break_in_time'  => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function isOpen(): bool
    {
        return is_null($this->break_in_time);
    }
}
