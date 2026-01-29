<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskWorkLog;
use App\Models\TaskStockUsage;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Site;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function profitLoss(Request $request)
    {
        $query = Project::with('site');

        // Filter by site
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        // Calculate profit/loss for each project
        $projectsData = $projects->map(function ($project) {
            $laborCost = $project->tasks()->sum('labor_cost');
            $materialCost = $project->tasks()->sum('material_cost');
            $actualAmount = $laborCost + $materialCost;
            $profitLoss = $project->quoted_amount - $actualAmount;
            $profitMargin = $project->quoted_amount > 0
                ? round(($profitLoss / $project->quoted_amount) * 100, 2)
                : 0;

            return [
                'project' => $project,
                'quoted_amount' => $project->quoted_amount,
                'labor_cost' => $laborCost,
                'material_cost' => $materialCost,
                'actual_amount' => $actualAmount,
                'profit_loss' => $profitLoss,
                'profit_margin' => $profitMargin,
                'is_profitable' => $profitLoss >= 0,
            ];
        });

        // Overall summary
        $summary = [
            'total_quoted' => $projectsData->sum('quoted_amount'),
            'total_labor' => $projectsData->sum('labor_cost'),
            'total_material' => $projectsData->sum('material_cost'),
            'total_actual' => $projectsData->sum('actual_amount'),
            'total_profit_loss' => $projectsData->sum('profit_loss'),
            'profitable_projects' => $projectsData->where('is_profitable', true)->count(),
            'loss_projects' => $projectsData->where('is_profitable', false)->count(),
        ];

        $sites = Site::orderBy('name')->get();

        return view('analytics.profit-loss', compact('projectsData', 'summary', 'sites'));
    }

    public function laborReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Get work logs grouped by employee
        $laborData = TaskWorkLog::with(['employee', 'taskAssignment.task.project'])
            ->whereBetween('work_date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id')
            ->map(function ($logs, $employeeId) {
                $employee = $logs->first()->employee;
                $totalHours = $logs->sum('hours_worked');
                $totalCost = $logs->sum(function ($log) {
                    return $log->hours_worked * $log->hourly_rate;
                });

                return [
                    'employee' => $employee,
                    'total_hours' => round($totalHours, 2),
                    'total_cost' => round($totalCost, 2),
                    'average_rate' => $totalHours > 0 ? round($totalCost / $totalHours, 2) : 0,
                    'days_worked' => $logs->unique('work_date')->count(),
                ];
            })
            ->sortByDesc('total_hours')
            ->values();

        // Summary
        $summary = [
            'total_employees' => $laborData->count(),
            'total_hours' => $laborData->sum('total_hours'),
            'total_cost' => $laborData->sum('total_cost'),
            'average_daily_hours' => $laborData->count() > 0
                ? round($laborData->sum('total_hours') / max($laborData->max('days_worked'), 1), 2)
                : 0,
        ];

        return view('analytics.labor-report', compact('laborData', 'summary', 'startDate', 'endDate'));
    }

    public function materialUsage(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $projectId = $request->input('project_id');

        $query = TaskStockUsage::with(['stock.product', 'task.project'])
            ->whereBetween('used_at', [$startDate, $endDate]);

        if ($projectId) {
            $query->whereHas('task', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        $usages = $query->orderBy('used_at', 'desc')->get();

        // Group by product
        $byProduct = $usages->groupBy(function ($usage) {
            return $usage->stock->product_id ?? 'unknown';
        })->map(function ($items, $productId) {
            $product = $items->first()->stock->product ?? null;
            return [
                'product' => $product,
                'total_quantity' => $items->sum('quantity'),
                'total_cost' => $items->sum('total_cost'),
                'usage_count' => $items->count(),
            ];
        })->sortByDesc('total_cost')->values();

        // Group by project
        $byProject = $usages->groupBy(function ($usage) {
            return $usage->task->project_id ?? 'unknown';
        })->map(function ($items, $projectId) {
            $project = $items->first()->task->project ?? null;
            return [
                'project' => $project,
                'total_cost' => $items->sum('total_cost'),
                'items_count' => $items->count(),
            ];
        })->sortByDesc('total_cost')->values();

        // Summary
        $summary = [
            'total_cost' => $usages->sum('total_cost'),
            'total_items' => $usages->count(),
            'unique_products' => $byProduct->count(),
            'projects_count' => $byProject->count(),
        ];

        $projects = Project::orderBy('name')->get();

        return view('analytics.material-usage', compact(
            'usages', 'byProduct', 'byProject', 'summary', 'projects',
            'startDate', 'endDate'
        ));
    }

    public function workProgress(Request $request)
    {
        $projectId = $request->input('project_id');

        $query = Project::with(['tasks' => function ($q) {
            $q->whereNull('parent_id')->with('subtasks');
        }, 'site']);

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get()->map(function ($project) {
            $totalTasks = $project->tasks()->count();
            $completedTasks = $project->tasks()->where('status', 'completed')->count();
            $inProgressTasks = $project->tasks()->where('status', 'in_progress')->count();
            $pendingTasks = $project->tasks()->where('status', 'pending')->count();

            $totalProgress = $totalTasks > 0
                ? round($project->tasks()->avg('progress'), 2)
                : 0;

            return [
                'project' => $project,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'in_progress_tasks' => $inProgressTasks,
                'pending_tasks' => $pendingTasks,
                'overall_progress' => $totalProgress,
            ];
        });

        $allProjects = Project::orderBy('name')->get();

        // Overall summary
        $summary = [
            'total_projects' => $projects->count(),
            'total_tasks' => $projects->sum('total_tasks'),
            'completed_tasks' => $projects->sum('completed_tasks'),
            'average_progress' => $projects->count() > 0
                ? round($projects->avg('overall_progress'), 2)
                : 0,
        ];

        return view('analytics.work-progress', compact('projects', 'allProjects', 'summary'));
    }

    public function dashboard()
    {
        // Key metrics for the main dashboard
        $metrics = [
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', 'in_progress')->count(),
            'total_employees' => Employee::where('status', 'active')->count(),
            'open_issues' => \App\Models\SiteIssue::whereIn('status', ['open', 'in_progress'])->count(),
        ];

        // Recent projects
        $recentProjects = Project::with('site')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Today's attendance
        $todayAttendance = Attendance::where('date', Carbon::today())
            ->count();
        $totalActiveEmployees = Employee::where('status', 'active')->count();

        // Profit/Loss summary for current month
        $currentMonthProjects = Project::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->get();

        $monthlyStats = [
            'quoted' => $currentMonthProjects->sum('quoted_amount'),
            'actual' => $currentMonthProjects->sum('actual_amount'),
        ];
        $monthlyStats['profit_loss'] = $monthlyStats['quoted'] - $monthlyStats['actual'];

        // Critical issues
        $criticalIssues = \App\Models\SiteIssue::with('site')
            ->where('priority', 'critical')
            ->whereIn('status', ['open', 'in_progress'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('analytics.dashboard', compact(
            'metrics', 'recentProjects', 'todayAttendance',
            'totalActiveEmployees', 'monthlyStats', 'criticalIssues'
        ));
    }
}
