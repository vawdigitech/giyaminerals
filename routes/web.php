<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductCategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TaskStockUsageController;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\IssueController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;


Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('products', ProductController::class);
    Route::resource('sites', SiteController::class);

    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('stocks/entry', [StockController::class, 'create'])->name('stocks.entry');
    Route::post('stocks/entry', [StockController::class, 'store'])->name('stocks.store');
    Route::get('stocks-entries', [StockController::class, 'entries'])->name('stocks.entries');
    Route::get('transfer/locations', [TransferController::class, 'getStockLocations'])->name('transfers.locations');

    Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('transfers/create', [TransferController::class, 'create'])->name('transfers.create');
    Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');

    Route::get('reports/stock-summary', [ReportController::class, 'stockSummary'])->name('reports.stock_summary');
    Route::get('reports/transfer-log', [ReportController::class, 'transferLog'])->name('reports.transfer_log');

    Route::resource('warehouses', WarehouseController::class);
    Route::resource('categories', ProductCategoryController::class);

    // Employee Management
    Route::resource('employees', EmployeeController::class);

    // Project & Task Management
    Route::resource('projects', ProjectController::class);
    Route::resource('tasks', TaskController::class);
    Route::get('tasks-by-project/{project}', [TaskController::class, 'getByProject'])->name('tasks.by-project');

    // Task Material/Stock Usage
    Route::get('tasks/{task}/materials', [TaskStockUsageController::class, 'index'])->name('tasks.materials.index');
    Route::get('tasks/{task}/materials/available-stock', [TaskStockUsageController::class, 'availableStock'])->name('tasks.materials.available-stock');
    Route::post('tasks/{task}/materials', [TaskStockUsageController::class, 'store'])->name('tasks.materials.store');
    Route::delete('tasks/{task}/materials/{usage}', [TaskStockUsageController::class, 'destroy'])->name('tasks.materials.destroy');

    // Attendance Reports
    Route::get('attendance', [AttendanceReportController::class, 'index'])->name('attendance.index');
    Route::get('attendance/daily', [AttendanceReportController::class, 'daily'])->name('attendance.daily');
    Route::get('attendance/employee/{employee}', [AttendanceReportController::class, 'employeeReport'])->name('attendance.employee');
    Route::get('attendance/export', [AttendanceReportController::class, 'export'])->name('attendance.export');

    // Issues Management
    Route::get('issues', [IssueController::class, 'index'])->name('issues.index');
    Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
    Route::post('issues/{issue}/status', [IssueController::class, 'updateStatus'])->name('issues.update-status');
    Route::post('issues/{issue}/assign', [IssueController::class, 'assign'])->name('issues.assign');
    Route::delete('issues/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');

    // Analytics & Reports
    Route::get('analytics/dashboard', [AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
    Route::get('analytics/profit-loss', [AnalyticsController::class, 'profitLoss'])->name('analytics.profit-loss');
    Route::get('analytics/labor-report', [AnalyticsController::class, 'laborReport'])->name('analytics.labor-report');
    Route::get('analytics/material-usage', [AnalyticsController::class, 'materialUsage'])->name('analytics.material-usage');
    Route::get('analytics/work-progress', [AnalyticsController::class, 'workProgress'])->name('analytics.work-progress');
});
