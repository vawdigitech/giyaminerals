<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Site;
use App\Models\Stock;
use App\Models\Task;
use App\Models\Transfer;
use App\Models\WarehouseStock;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // --- Row 1: Core entity counts ---
        $totalProjects     = Project::count();
        $activeProjects    = Project::where('status', 'in_progress')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $totalSites        = Site::count();
        $totalEmployees    = Employee::where('status', 'active')->count();
        $totalStock        = Stock::sum('balance');

        // --- Row 2: Operational stats ---
        $totalTasks          = Task::whereNull('parent_id')->count();
        $completedTasks      = Task::whereNull('parent_id')->where('status', 'completed')->count();
        $inProgressTasks     = Task::whereNull('parent_id')->where('status', 'in_progress')->count();
        $pendingTasks        = Task::whereNull('parent_id')->where('status', 'pending')->count();
        $totalTransfersToday = Transfer::whereDate('created_at', Carbon::today())->count();
        $todayAttendance     = Attendance::whereDate('date', Carbon::today())->count();

        // --- Monthly Profit / Loss ---
        $currentMonthProjects = Project::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->get();
        $monthlyQuoted     = $currentMonthProjects->sum('quoted_amount');
        $monthlyActual     = $currentMonthProjects->sum('actual_amount');
        $monthlyProfitLoss = $monthlyQuoted - $monthlyActual;

        // --- Recent Projects with progress ---
        $recentProjects = Project::with('site')
            ->orderBy('updated_at', 'desc')
            ->take(6)
            ->get();

        // --- Recent Transfers ---
        $recentTransfers = Transfer::with('product')->latest()->take(5)->get();

        // --- Low Stock items ---
        $lowStock = WarehouseStock::with('product', 'warehouse')
            ->where('quantity', '<', 10)
            ->orderBy('quantity')
            ->get();

        // --- Chart: Project progress (up to 8 most recently updated projects) ---
        $projectsForChart     = Project::with('tasks')->orderBy('updated_at', 'desc')->take(8)->get();
        $chartProjectLabels   = $projectsForChart->pluck('name')->toArray();
        $chartProjectProgress = $projectsForChart->map(fn ($p) => $p->calculateProgress())->toArray();

        // --- Chart: Monthly transfer counts (last 6 months) ---
        $monthlyLabels    = [];
        $monthlyTransfers = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyLabels[]    = $month->format('M Y');
            $monthlyTransfers[] = Transfer::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return view('dashboard.index', compact(
            'totalProjects', 'activeProjects', 'completedProjects',
            'totalSites', 'totalEmployees', 'totalStock',
            'totalTasks', 'completedTasks', 'inProgressTasks', 'pendingTasks',
            'totalTransfersToday', 'todayAttendance',
            'monthlyQuoted', 'monthlyActual', 'monthlyProfitLoss',
            'recentProjects', 'recentTransfers', 'lowStock',
            'chartProjectLabels', 'chartProjectProgress',
            'monthlyTransfers', 'monthlyLabels'
        ));
    }
}
