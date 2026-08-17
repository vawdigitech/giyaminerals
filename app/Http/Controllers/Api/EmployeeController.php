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

        $query = Employee::with(['site', 'factory', 'todayAttendance.breaks', 'designation.category']);

        // Allow fetching all employees (bypass site restriction) when all=true
        $fetchAll = $request->boolean('all', false);

        $hasCheckedInLocationFilter =
            $request->filled('checked_in_at_location_type')
            && $request->filled('checked_in_at_location_id');

        // Supervisors only see employees at their site (unless all=true for search,
        // or filtering by who is checked in at an explicitly selected work location)
        if (!$fetchAll && !$hasCheckedInLocationFilter && $user->isSupervisor() && $user->site_id) {
            $query->where('employees.site_id', $user->site_id);
        }

        // Filter by site
        if ($request->has('site_id')) {
            $query->where('employees.site_id', $request->site_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('employees.status', $request->status);
        }

        // Filter by designation
        if ($request->has('designation_id')) {
            $query->where('employees.designation_id', $request->designation_id);
        }

        // Filter by checked-in location (employees who have attendance today at specific location)
        if ($request->has('checked_in_at_location_type') && $request->has('checked_in_at_location_id')) {
            $locationType = $request->checked_in_at_location_type;
            $locationId = $request->checked_in_at_location_id;

            $query->whereHas('todayAttendance', function ($q) use ($locationType, $locationId) {
                // Check for active attendance (checked in but not checked out)
                $q->whereNotNull('check_in_time');

                if ($locationType === 'site') {
                    $q->where('site_id', $locationId);
                } elseif ($locationType === 'factory') {
                    $q->where('factory_id', $locationId);
                }
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                  ->orWhere('employees.employee_code', 'like', "%{$search}%")
                  ->orWhere('employees.phone', 'like', "%{$search}%");
            });
        }

        // Join with designations and designation_categories for ordering
        $query->join('designations', 'employees.designation_id', '=', 'designations.id')
              ->leftJoin('designation_categories', 'designations.category_id', '=', 'designation_categories.id')
              ->select('employees.*')
              ->orderBy('designation_categories.name', 'asc')
              ->orderBy('employees.daily_rate', 'desc');

        $employees = $query->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    public function show(Employee $employee)
    {
        $employee->load(['site', 'factory', 'todayAttendance.breaks', 'designation', 'taskAssignments' => function ($query) {
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
            'designation_id' => 'required|exists:designations,id',
            'employment_type' => 'required|in:permanent,contract,temporary',
            'salary_type' => 'required|in:daily,hourly',
            'hourly_rate' => 'nullable|numeric|min:0',
            'daily_rate' => 'nullable|numeric|min:0',
            'working_hours' => 'nullable|numeric|min:0.5|max:24',
            'factory_daily_rate' => 'nullable|numeric|min:0',
            'factory_hourly_rate' => 'nullable|numeric|min:0',
            'factory_working_hours' => 'nullable|numeric|min:0.5|max:24',
            'photo' => 'nullable|string',
            'site_id' => 'nullable|exists:sites,id',
            'factory_id' => 'nullable|exists:factories,id',
        ]);

        // Generate employee code based on designation
        $designation = \App\Models\Designation::find($validated['designation_id']);
        $prefix = $designation->code ?? 'EMP';

        // Count existing employees with this designation prefix
        $lastEmployeeWithPrefix = Employee::where('employee_code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(employee_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();

        if ($lastEmployeeWithPrefix) {
            $lastNumber = (int) substr($lastEmployeeWithPrefix->employee_code, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $validated['employee_code'] = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // If supervisor, default to their site
        $user = $request->user();
        if ($user->isSupervisor() && $user->site_id && !isset($validated['site_id'])) {
            $validated['site_id'] = $user->site_id;
        }

        $employee = Employee::create($validated);
        $employee->load(['site', 'factory', 'designation']);

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
            'designation_id' => 'sometimes|exists:designations,id',
            'employment_type' => 'sometimes|in:permanent,contract,temporary',
            'salary_type' => 'sometimes|in:daily,hourly',
            'hourly_rate' => 'nullable|numeric|min:0',
            'daily_rate' => 'nullable|numeric|min:0',
            'working_hours' => 'nullable|numeric|min:0.5|max:24',
            'factory_daily_rate' => 'nullable|numeric|min:0',
            'factory_hourly_rate' => 'nullable|numeric|min:0',
            'factory_working_hours' => 'nullable|numeric|min:0.5|max:24',
            'photo' => 'nullable|string',
            'site_id' => 'nullable|exists:sites,id',
            'factory_id' => 'nullable|exists:factories,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $employee->update($validated);
        $employee->load(['site', 'factory', 'designation']);

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

        $query = Employee::with(['site', 'designation'])
            ->where('status', 'active')
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

    /**
     * Get work location options for the current user/employee
     * Returns whether the user works at a factory or site, and the available options
     */
    public function workLocationOptions(Request $request)
    {
        $user = $request->user();

        // Check if user is linked to an employee (for employee app users)
        $employee = null;
        if (isset($request->employee_id)) {
            $employee = Employee::find($request->employee_id);
        }

        // If user is a supervisor, return their assigned location
        if ($user->isSupervisor()) {
            if ($user->site_id) {
                $site = \App\Models\Site::with('projects')->find($user->site_id);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'type' => 'site',
                        'site' => $site,
                        'projects' => $site->projects ?? [],
                    ],
                ]);
            }
        }

        // For employees, return their work location
        if ($employee) {
            if ($employee->isFactoryEmployee()) {
                $factory = $employee->factory;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'type' => 'factory',
                        'factory' => $factory,
                    ],
                ]);
            } elseif ($employee->isSiteEmployee()) {
                $site = \App\Models\Site::with('projects')->find($employee->site_id);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'type' => 'site',
                        'site' => $site,
                        'projects' => $site->projects ?? [],
                    ],
                ]);
            }
        }

        // Default: return both options if admin or unassigned
        return response()->json([
            'success' => true,
            'data' => [
                'type' => 'both',
                'sites' => \App\Models\Site::with('projects')->get(),
                'factories' => \App\Models\Factory::all(),
            ],
        ]);
    }
}
