<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskProgressPhoto extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'employee_id',
        'photo',
        'caption',
        'captured_date',
    ];

    protected $casts = [
        'captured_date' => 'date',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
