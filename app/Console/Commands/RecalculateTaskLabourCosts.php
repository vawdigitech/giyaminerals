<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Task;
use App\Models\TaskAssignmentSession;
use App\Models\WeeklySalaryPayment;
use Illuminate\Console\Command;

class RecalculateTaskLabourCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:recalculate-labour-costs {--all : Recalculate for all paid salaries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate task labour costs based on paid salaries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting labour cost recalculation...');

        // Get all paid salary payments
        $payments = WeeklySalaryPayment::where('status', 'paid')->get();

        if ($payments->isEmpty()) {
            $this->info('No paid salary payments found.');
            return 0;
        }

        $this->info("Found {$payments->count()} paid salary payments.");

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();

        $tasksUpdated = 0;
        $assignmentsUpdated = 0;

        foreach ($payments as $payment) {
            $result = $this->updateTaskLabourCosts($payment);
            $tasksUpdated += $result['tasks'];
            $assignmentsUpdated += $result['assignments'];
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Completed! Updated {$assignmentsUpdated} task assignments and recalculated {$tasksUpdated} tasks.");

        return 0;
    }

    /**
     * Update labour costs on tasks based on paid salary.
     */
    protected function updateTaskLabourCosts(WeeklySalaryPayment $payment): array
    {
        $result = ['tasks' => 0, 'assignments' => 0];

        // Get all attendances for this employee during the payment week
        $attendanceIds = Attendance::where('employee_id', $payment->employee_id)
            ->whereBetween('date', [$payment->week_start_date, $payment->week_end_date])
            ->whereNotNull('check_out_time')
            ->pluck('id');

        if ($attendanceIds->isEmpty()) {
            return $result;
        }

        // Get all task assignment sessions linked to these attendances
        $sessions = TaskAssignmentSession::whereIn('attendance_id', $attendanceIds)
            ->where('status', 'completed')
            ->with('taskAssignment.task')
            ->get();

        if ($sessions->isEmpty()) {
            return $result;
        }

        // Calculate total hours worked across all tasks
        $totalHoursOnTasks = $sessions->sum('hours');

        if ($totalHoursOnTasks <= 0) {
            return $result;
        }

        // Calculate effective hourly rate from paid salary
        $paidSalary = $payment->total_salary;
        $effectiveHourlyRate = $paidSalary / $totalHoursOnTasks;

        // Collect unique task IDs
        $taskIds = [];

        // Update task assignments with effective hourly rate
        foreach ($sessions as $session) {
            $assignment = $session->taskAssignment;
            if (!$assignment) {
                continue;
            }

            $taskIds[$assignment->task_id] = true;

            // Update hourly rate if it's not set or is zero
            if (empty($assignment->hourly_rate_at_time) || $assignment->hourly_rate_at_time == 0) {
                $assignment->update(['hourly_rate_at_time' => $effectiveHourlyRate]);
                $result['assignments']++;
            }
        }

        // Recalculate costs for all affected tasks
        Task::whereIn('id', array_keys($taskIds))->each(function ($task) use (&$result) {
            $task->recalculateCosts();
            $result['tasks']++;
        });

        return $result;
    }
}
