<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Employee::with(['site', 'todayAttendance']);

        // Supervisors only see employees at their site
        if ($user->isSupervisor() && $user->site_id) {
            $query->where('site_id', $user->site_id);
        }

        // Filter by site
        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    public function show(Employee $employee)
    {
        $employee->load(['site', 'todayAttendance', 'taskAssignments' => function ($query) {
            $query->whereNull('removed_at')->with('task');
        }]);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|max:100',
            'employment_type' => 'required|in:permanent,contract,temporary',
            'hourly_rate' => 'required|numeric|min:0',
            'photo' => 'nullable|string',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        // Generate employee code
        $lastEmployee = Employee::orderBy('id', 'desc')->first();
        $nextId = $lastEmployee ? $lastEmployee->id + 1 : 1;
        $validated['employee_code'] = 'EMP' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // If supervisor, default to their site
        $user = $request->user();
        if ($user->isSupervisor() && $user->site_id && !isset($validated['site_id'])) {
            $validated['site_id'] = $user->site_id;
        }

        $employee = Employee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
            'data' => $employee,
        ], 201);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|string|max:100',
            'employment_type' => 'sometimes|in:permanent,contract,temporary',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'photo' => 'nullable|string',
            'site_id' => 'nullable|exists:sites,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'data' => $employee,
        ]);
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully',
        ]);
    }

    public function available(Request $request)
    {
        $user = $request->user();

        $query = Employee::where('status', 'active')
            ->whereDoesntHave('taskAssignments', function ($q) {
                $q->whereNull('removed_at');
            });

        if ($user->isSupervisor() && $user->site_id) {
            $query->where('site_id', $user->site_id);
        }

        $employees = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }
}
