<?php

namespace App\Console\Commands;

use App\Models\TaskAssignment;
use Illuminate\Console\Command;

class FixTaskAssignmentRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:fix-assignment-rates {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix task assignment hourly rates based on location (factory vs site)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Analyzing task assignments...');

        // Get all active assignments with completed sessions
        $assignments = TaskAssignment::whereNull('removed_at')
            ->whereHas('completedSessions')
            ->with(['employee', 'completedSessions.attendance', 'task'])
            ->get();

        if ($assignments->isEmpty()) {
            $this->info('No task assignments with completed sessions found.');
            return 0;
        }

        $this->info("Found {$assignments->count()} task assignments with work sessions.");
        $this->newLine();

        $fixedCount = 0;
        $skippedCount = 0;

        foreach ($assignments as $assignment) {
            // Get the most recent completed session to check the attendance location
            $latestSession = $assignment->completedSessions()
                ->with('attendance')
                ->latest('date')
                ->first();

            if (!$latestSession || !$latestSession->attendance) {
                $skippedCount++;
                continue;
            }

            $attendance = $latestSession->attendance;
            $currentRate = $assignment->hourly_rate_at_time;
            $correctRate = $assignment->employee->getHourlyRateForAttendance($attendance);

            // Check if the rate is incorrect (different by more than 0.01)
            if (abs($currentRate - $correctRate) > 0.01) {
                $isFactory = $attendance->isFactoryAttendance();
                $locationType = $isFactory ? 'factory' : 'site';
                $locationName = $attendance->getLocationName();

                $this->warn("Assignment #{$assignment->id} has incorrect rate:");
                $this->line("  Task: {$assignment->task->code} - {$assignment->task->name}");
                $this->line("  Employee: {$assignment->employee->name}");
                $this->line("  Location: {$locationType} - {$locationName}");
                $this->line("  Current Rate: {$currentRate}");
                $this->line("  Correct Rate: {$correctRate}");
                $this->line("  Hours Worked: {$assignment->hours_worked}");
                $this->line("  Current Labor Cost: " . ($currentRate * $assignment->hours_worked));
                $this->line("  Correct Labor Cost: " . ($correctRate * $assignment->hours_worked));
                $this->newLine();

                if (!$isDryRun) {
                    $assignment->update(['hourly_rate_at_time' => $correctRate]);
                    $assignment->task->recalculateCosts();
                    $this->info("  ✓ Fixed and recalculated task costs");
                    $this->newLine();
                }

                $fixedCount++;
            }
        }

        $this->newLine();

        if ($isDryRun) {
            $this->info("DRY RUN COMPLETE:");
            $this->line("  Would fix: {$fixedCount} assignments");
            $this->line("  Skipped (no attendance): {$skippedCount} assignments");
            $this->line("  Correct: " . ($assignments->count() - $fixedCount - $skippedCount) . " assignments");
            $this->newLine();
            $this->info("Run without --dry-run to apply the fixes.");
        } else {
            $this->info("COMPLETE:");
            $this->line("  Fixed: {$fixedCount} assignments");
            $this->line("  Skipped (no attendance): {$skippedCount} assignments");
            $this->line("  Already correct: " . ($assignments->count() - $fixedCount - $skippedCount) . " assignments");
        }

        return 0;
    }
}
