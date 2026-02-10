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
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
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

    // Inventory Management
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('stocks-entries', [StockController::class, 'entries'])->name('stocks.entries');
    });
    Route::middleware('permission:inventory.create')->group(function () {
        Route::get('stocks/entry', [StockController::class, 'create'])->name('stocks.entry');
        Route::post('stocks/entry', [StockController::class, 'store'])->name('stocks.store');
    });

    Route::middleware('permission:inventory.view')->group(function () {
        Route::resource('products', ProductController::class)->only(['index', 'show']);
        Route::resource('categories', ProductCategoryController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:inventory.create')->group(function () {
        Route::resource('products', ProductController::class)->only(['create', 'store']);
        Route::resource('categories', ProductCategoryController::class)->only(['create', 'store']);
    });
    Route::middleware('permission:inventory.edit')->group(function () {
        Route::resource('products', ProductController::class)->only(['edit', 'update']);
        Route::resource('categories', ProductCategoryController::class)->only(['edit', 'update']);
    });
    Route::middleware('permission:inventory.delete')->group(function () {
        Route::resource('products', ProductController::class)->only(['destroy']);
        Route::resource('categories', ProductCategoryController::class)->only(['destroy']);
    });

    // Sites Management
    Route::middleware('permission:sites.view')->group(function () {
        Route::resource('sites', SiteController::class)->only(['index', 'show']);
        Route::get('sites/{site}/tasks', [StockController::class, 'getTasksBySite'])->name('sites.tasks');
        Route::get('tasks/{task}/subtasks', [StockController::class, 'getSubtasks'])->name('tasks.subtasks');
    });
    Route::middleware('permission:sites.create')->resource('sites', SiteController::class)->only(['create', 'store']);
    Route::middleware('permission:sites.edit')->resource('sites', SiteController::class)->only(['edit', 'update']);
    Route::middleware('permission:sites.delete')->resource('sites', SiteController::class)->only(['destroy']);

    // Transfers
    Route::middleware('permission:transfers.view')->group(function () {
        Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
        Route::get('transfer/locations', [TransferController::class, 'getStockLocations'])->name('transfers.locations');
    });
    Route::middleware('permission:transfers.create')->group(function () {
        Route::get('transfers/create', [TransferController::class, 'create'])->name('transfers.create');
        Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
    });

    // Reports
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('reports/stock-summary', [ReportController::class, 'stockSummary'])->name('reports.stock_summary');
        Route::get('reports/transfer-log', [ReportController::class, 'transferLog'])->name('reports.transfer_log');
    });

    // Warehouses
    Route::middleware('permission:warehouses.create')->resource('warehouses', WarehouseController::class)->only(['create', 'store']);
    Route::middleware('permission:warehouses.view')->resource('warehouses', WarehouseController::class)->only(['index', 'show']);
    Route::middleware('permission:warehouses.edit')->resource('warehouses', WarehouseController::class)->only(['edit', 'update']);
    Route::middleware('permission:warehouses.delete')->resource('warehouses', WarehouseController::class)->only(['destroy']);

    // Employee Management
    Route::middleware('permission:employees.create')->resource('employees', EmployeeController::class)->only(['create', 'store']);
    Route::middleware('permission:employees.view')->resource('employees', EmployeeController::class)->only(['index', 'show']);
    Route::middleware('permission:employees.edit')->resource('employees', EmployeeController::class)->only(['edit', 'update']);
    Route::middleware('permission:employees.delete')->resource('employees', EmployeeController::class)->only(['destroy']);

    // Project Management
    Route::middleware('permission:projects.create')->resource('projects', ProjectController::class)->only(['create', 'store']);
    Route::middleware('permission:projects.view')->resource('projects', ProjectController::class)->only(['index', 'show']);
    Route::middleware('permission:projects.edit')->resource('projects', ProjectController::class)->only(['edit', 'update']);
    Route::middleware('permission:projects.delete')->resource('projects', ProjectController::class)->only(['destroy']);

    // Task Management
    Route::middleware('permission:tasks.view')->group(function () {
        Route::resource('tasks', TaskController::class)->only(['index', 'show']);
        Route::get('tasks-by-project/{project}', [TaskController::class, 'getByProject'])->name('tasks.by-project');
        Route::get('tasks/{task}/materials', [TaskStockUsageController::class, 'index'])->name('tasks.materials.index');
        Route::get('tasks/{task}/materials/available-stock', [TaskStockUsageController::class, 'availableStock'])->name('tasks.materials.available-stock');
        Route::get('materials/return-destinations', [TaskStockUsageController::class, 'getReturnDestinations'])->name('materials.return-destinations');
    });
    Route::middleware('permission:tasks.create')->group(function () {
        Route::resource('tasks', TaskController::class)->only(['create', 'store']);
        Route::post('tasks/{task}/materials', [TaskStockUsageController::class, 'store'])->name('tasks.materials.store');
    });
    Route::middleware('permission:tasks.edit')->group(function () {
        Route::resource('tasks', TaskController::class)->only(['edit', 'update']);
        Route::post('tasks/{task}/materials/{usage}/return', [TaskStockUsageController::class, 'returnMaterial'])->name('tasks.materials.return');
    });
    Route::middleware('permission:tasks.delete')->group(function () {
        Route::resource('tasks', TaskController::class)->only(['destroy']);
        Route::delete('tasks/{task}/materials/{usage}', [TaskStockUsageController::class, 'destroy'])->name('tasks.materials.destroy');
    });

    // Attendance Reports
    Route::middleware('permission:attendance.view')->group(function () {
        Route::get('attendance', [AttendanceReportController::class, 'index'])->name('attendance.index');
        Route::get('attendance/daily', [AttendanceReportController::class, 'daily'])->name('attendance.daily');
        Route::get('attendance/employee/{employee}', [AttendanceReportController::class, 'employeeReport'])->name('attendance.employee');
    });
    Route::middleware('permission:attendance.export')->group(function () {
        Route::get('attendance/export', [AttendanceReportController::class, 'export'])->name('attendance.export');
    });

    // Issues Management
    Route::middleware('permission:issues.view')->group(function () {
        Route::get('issues', [IssueController::class, 'index'])->name('issues.index');
        Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
    });
    Route::middleware('permission:issues.edit')->group(function () {
        Route::post('issues/{issue}/status', [IssueController::class, 'updateStatus'])->name('issues.update-status');
        Route::post('issues/{issue}/assign', [IssueController::class, 'assign'])->name('issues.assign');
    });
    Route::middleware('permission:issues.delete')->group(function () {
        Route::delete('issues/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');
    });

    // Analytics & Reports
    Route::middleware('permission:analytics.view')->group(function () {
        Route::get('analytics/dashboard', [AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
        Route::get('analytics/profit-loss', [AnalyticsController::class, 'profitLoss'])->name('analytics.profit-loss');
        Route::get('analytics/labor-report', [AnalyticsController::class, 'laborReport'])->name('analytics.labor-report');
        Route::get('analytics/material-usage', [AnalyticsController::class, 'materialUsage'])->name('analytics.material-usage');
        Route::get('analytics/work-progress', [AnalyticsController::class, 'workProgress'])->name('analytics.work-progress');
    });

    // Administration - Designations
    Route::middleware('permission:designations.view')->resource('designations', DesignationController::class)->only(['index']);
    Route::middleware('permission:designations.create')->resource('designations', DesignationController::class)->only(['create', 'store']);
    Route::middleware('permission:designations.edit')->resource('designations', DesignationController::class)->only(['edit', 'update']);
    Route::middleware('permission:designations.delete')->resource('designations', DesignationController::class)->only(['destroy']);

    // Administration - Roles & Permissions
    Route::middleware('permission:roles.view')->resource('roles', RoleController::class)->only(['index']);
    Route::middleware('permission:roles.create')->resource('roles', RoleController::class)->only(['create', 'store']);
    Route::middleware('permission:roles.edit')->resource('roles', RoleController::class)->only(['edit', 'update']);
    Route::middleware('permission:roles.delete')->resource('roles', RoleController::class)->only(['destroy']);

    // Administration - User Management
    Route::middleware('permission:users.view')->resource('users', UserController::class)->only(['index']);
    Route::middleware('permission:users.create')->resource('users', UserController::class)->only(['create', 'store']);
    Route::middleware('permission:users.edit')->resource('users', UserController::class)->only(['edit', 'update']);
    Route::middleware('permission:users.delete')->resource('users', UserController::class)->only(['destroy']);
});
