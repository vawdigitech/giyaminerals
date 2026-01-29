<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskAssignmentController;
use App\Http\Controllers\Api\TaskWorkLogController;
use App\Http\Controllers\Api\TaskStockUsageController;
use App\Http\Controllers\Api\SiteIssueController;
use App\Http\Controllers\Api\WorkSessionController;
use App\Http\Controllers\Api\TaskProgressPhotoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are for the mobile app (React Native) used by supervisors
| at construction sites.
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Employees
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/employees/available', [EmployeeController::class, 'available']);
    Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::get('/attendance/today-summary', [AttendanceController::class, 'todaySummary']);
    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show']);
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/{attendance}/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/employees/{employee}/attendance-history', [AttendanceController::class, 'employeeHistory']);

    // Projects
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/summary', [ProjectController::class, 'summary']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::post('/projects/{project}/update-progress', [ProjectController::class, 'updateProgress']);

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/sections', [TaskController::class, 'sections']);
    Route::get('/tasks/parents', [TaskController::class, 'getParentTasks']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::post('/tasks/{task}/update-progress', [TaskController::class, 'updateProgress']);
    Route::post('/tasks/{task}/update-status', [TaskController::class, 'updateStatus']);
    Route::get('/projects/{project}/tasks', [TaskController::class, 'byProject']);

    // Task Progress Photos
    Route::get('/tasks/{task}/photos', [TaskProgressPhotoController::class, 'index']);
    Route::get('/tasks/{task}/photos/by-date', [TaskProgressPhotoController::class, 'byDate']);
    Route::post('/tasks/{task}/photos', [TaskProgressPhotoController::class, 'store']);
    Route::delete('/task-photos/{photo}', [TaskProgressPhotoController::class, 'destroy']);

    // Task Assignments
    Route::get('/task-assignments', [TaskAssignmentController::class, 'index']);
    Route::get('/task-assignments/{assignment}', [TaskAssignmentController::class, 'show']);
    Route::post('/task-assignments/assign', [TaskAssignmentController::class, 'assign']);
    Route::post('/task-assignments/{assignment}/remove', [TaskAssignmentController::class, 'remove']);
    Route::get('/tasks/{task}/assignments', [TaskAssignmentController::class, 'byTask']);
    Route::get('/employees/{employee}/assignments', [TaskAssignmentController::class, 'byEmployee']);

    // Work Logs
    Route::get('/work-logs', [TaskWorkLogController::class, 'index']);
    Route::get('/work-logs/{workLog}', [TaskWorkLogController::class, 'show']);
    Route::post('/work-logs', [TaskWorkLogController::class, 'store']);
    Route::post('/work-logs/log-today', [TaskWorkLogController::class, 'logToday']);
    Route::put('/work-logs/{workLog}', [TaskWorkLogController::class, 'update']);
    Route::delete('/work-logs/{workLog}', [TaskWorkLogController::class, 'destroy']);
    Route::get('/task-assignments/{assignment}/work-logs', [TaskWorkLogController::class, 'byAssignment']);

    // Stock Usage
    Route::get('/stock-usage', [TaskStockUsageController::class, 'index']);
    Route::get('/stock-usage/available-stock', [TaskStockUsageController::class, 'availableStock']);
    Route::get('/stock-usage/{stockUsage}', [TaskStockUsageController::class, 'show']);
    Route::post('/stock-usage', [TaskStockUsageController::class, 'store']);
    Route::delete('/stock-usage/{stockUsage}', [TaskStockUsageController::class, 'destroy']);
    Route::get('/tasks/{task}/stock-usage', [TaskStockUsageController::class, 'byTask']);

    // Site Issues
    Route::get('/issues', [SiteIssueController::class, 'index']);
    Route::get('/issues/summary', [SiteIssueController::class, 'summary']);
    Route::get('/issues/categories', [SiteIssueController::class, 'categories']);
    Route::get('/issues/{issue}', [SiteIssueController::class, 'show']);
    Route::post('/issues', [SiteIssueController::class, 'store']);
    Route::put('/issues/{issue}', [SiteIssueController::class, 'update']);
    Route::post('/issues/{issue}/status', [SiteIssueController::class, 'updateStatus']);
    Route::post('/issues/{issue}/assign', [SiteIssueController::class, 'assign']);
    Route::delete('/issues/{issue}', [SiteIssueController::class, 'destroy']);

    // Work Sessions
    Route::get('/work-sessions/today', [WorkSessionController::class, 'today']);
    Route::get('/work-sessions/employee/{employee}', [WorkSessionController::class, 'byEmployee']);
    Route::get('/work-sessions/employee/{employee}/today', [WorkSessionController::class, 'employeeTodaySummary']);
    Route::get('/task-assignments/{assignment}/sessions', [WorkSessionController::class, 'byAssignment']);
});
