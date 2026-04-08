<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    // Temporary storage for original hours (not persisted to DB)
    public static $originalHoursCache = [];

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
        'daily_salary',
        'overtime_hours',
        'overtime_salary',
        'overtime_applicable',
        'daily_rate_at_time',
        'working_hours_at_time',
        'status',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'total_hours' => 'decimal:2',
        'daily_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_salary' => 'decimal:2',
        'overtime_applicable' => 'boolean',
        'daily_rate_at_time' => 'decimal:2',
        'working_hours_at_time' => 'decimal:2',
    ];

    protected static function booted()
    {
        // Store original hours before saving hook modifies it
        static::updating(function ($attendance) {
            if ($attendance->isDirty('check_in_time') || $attendance->isDirty('check_out_time')) {
                // Store the original total_hours in static cache before any modifications
                self::$originalHoursCache[$attendance->id] = $attendance->getOriginal('total_hours');
            }
        });

        static::saving(function ($attendance) {
            if ($attendance->check_in_time && $attendance->check_out_time) {
                $minutes = abs($attendance->check_out_time->diffInMinutes($attendance->check_in_time));

                // Sum all completed break durations
                $breakMinutes = 0;
                if ($attendance->id) {
                    $breaks = AttendanceBreak::where('attendance_id', $attendance->id)
                        ->whereNotNull('break_in_time')
                        ->get();

                    foreach ($breaks as $break) {
                        $breakMinutes += abs($break->break_in_time->diffInMinutes($break->break_out_time));
                    }
                }

                $attendance->total_hours = ($minutes - $breakMinutes) / 60;

                // Calculate daily salary using prorated approach
                if ($attendance->daily_rate_at_time && $attendance->working_hours_at_time > 0) {
                    $hourlyEquivalent = $attendance->daily_rate_at_time / $attendance->working_hours_at_time;
                    $attendance->daily_salary = $attendance->total_hours * $hourlyEquivalent;

                    // Overtime calculation
                    if (Setting::get('overtime_enabled', true)) {
                        $threshold = (float) Setting::get('overtime_threshold_hours', 2.5);
                        $bonusPercent = (float) Setting::get('overtime_bonus_percentage', 50);
                        $standardHours = $attendance->working_hours_at_time ?? 8;

                        if ($attendance->total_hours >= ($standardHours + $threshold)) {
                            $attendance->overtime_applicable = true;
                            $attendance->overtime_hours = $attendance->total_hours - $standardHours;
                            $attendance->overtime_salary = ($attendance->daily_rate_at_time * $bonusPercent) / 100;
                            $attendance->daily_salary += $attendance->overtime_salary;
                        } else {
                            $attendance->overtime_applicable = false;
                            $attendance->overtime_hours = 0;
                            $attendance->overtime_salary = 0;
                        }
                    } else {
                        $attendance->overtime_applicable = false;
                        $attendance->overtime_hours = 0;
                        $attendance->overtime_salary = 0;
                    }
                }
            }
        });

        static::updated(function ($attendance) {
            // After attendance is updated, recalculate associated task work sessions
            $oldHours = self::$originalHoursCache[$attendance->id] ?? null;

            \Log::info("Attendance updated hook triggered", [
                'attendance_id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
                'old_hours' => $oldHours,
                'new_hours' => $attendance->total_hours,
            ]);

            if ($oldHours > 0 && $attendance->total_hours > 0 && abs($oldHours - $attendance->total_hours) > 0.01) {
                // Proportionally adjust work session hours
                $ratio = $attendance->total_hours / $oldHours;
                $sessions = $attendance->sessions()->where('status', 'completed')->get();

                \Log::info("Recalculating work sessions", [
                    'attendance_id' => $attendance->id,
                    'ratio' => $ratio,
                    'sessions_count' => $sessions->count(),
                ]);

                $affectedTaskAssignments = [];

                foreach ($sessions as $session) {
                    $newSessionHours = round($session->hours * $ratio, 2);
                    $session->updateQuietly(['hours' => $newSessionHours]);
                    $affectedTaskAssignments[$session->task_assignment_id] = $session->taskAssignment;

                    \Log::info("Session hours updated", [
                        'session_id' => $session->id,
                        'task_assignment_id' => $session->task_assignment_id,
                        'old_session_hours' => $session->getOriginal('hours'),
                        'new_session_hours' => $newSessionHours,
                    ]);
                }

                // Trigger recalculation on each affected task assignment
                foreach ($affectedTaskAssignments as $taskAssignment) {
                    \Log::info("Recalculating task assignment", [
                        'task_assignment_id' => $taskAssignment->id,
                        'task_id' => $taskAssignment->task_id,
                    ]);
                    $taskAssignment->recalculateHoursFromSessions();
                }

                // Clean up the cache
                unset(self::$originalHoursCache[$attendance->id]);
            } else {
                \Log::info("Skipping work session recalculation", [
                    'reason' => 'Hours did not change significantly',
                    'old_hours' => $oldHours,
                    'new_hours' => $attendance->total_hours,
                ]);
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

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class)->orderBy('break_out_time');
    }

    public function openBreak()
    {
        return $this->hasOne(AttendanceBreak::class)->whereNull('break_in_time')->latest();
    }

    public function isCheckedIn(): bool
    {
        return !is_null($this->check_in_time) && is_null($this->check_out_time);
    }

    public function isCheckedOut(): bool
    {
        return !is_null($this->check_out_time);
    }

    public function isOnBreak(): bool
    {
        return $this->breaks()->whereNull('break_in_time')->exists();
    }
}
