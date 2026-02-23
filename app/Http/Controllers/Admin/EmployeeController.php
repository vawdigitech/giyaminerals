<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Designation;
use App\Models\Site;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['site', 'designation']);

        // Filter by site
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by designation
        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('name')->paginate(15);
        $sites = Site::orderBy('name')->get();
        $designations = Designation::active()->orderBy('name')->get();

        return view('employees.index', compact('employees', 'sites', 'designations'));
    }

    public function create()
    {
        $sites = Site::orderBy('name')->get();
        $designations = Designation::active()->orderBy('name')->get();
        return view('employees.create', compact('sites', 'designations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'designation_id' => 'required|exists:designations,id',
            'employment_type' => 'required|in:permanent,contract,temporary',
            'daily_rate' => 'required|numeric|min:0',
            'working_hours' => 'required|numeric|min:1|max:24',
            'photo' => 'nullable|image|max:5120', // 5MB max
            'site_id' => 'nullable|exists:sites,id',
            'status' => 'required|in:active,inactive',
        ]);

        // Generate employee code based on designation
        $designation = Designation::find($validated['designation_id']);
        $prefix = $designation->code ?? 'EMP';

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

        // Handle photo upload - convert to base64
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $imageData = base64_encode(file_get_contents($file->getRealPath()));
            $mimeType = $file->getMimeType();
            $validated['photo'] = 'data:' . $mimeType . ';base64,' . $imageData;
        } else {
            unset($validated['photo']);
        }

        Employee::create($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee)
    {
        $sites = Site::orderBy('name')->get();
        $designations = Designation::active()->orderBy('name')->get();
        return view('employees.edit', compact('employee', 'sites', 'designations'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'designation_id' => 'required|exists:designations,id',
            'employment_type' => 'required|in:permanent,contract,temporary',
            'daily_rate' => 'required|numeric|min:0',
            'working_hours' => 'required|numeric|min:1|max:24',
            'photo' => 'nullable|image|max:5120', // 5MB max
            'site_id' => 'nullable|exists:sites,id',
            'status' => 'required|in:active,inactive',
        ]);

        // Handle photo upload - convert to base64
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $imageData = base64_encode(file_get_contents($file->getRealPath()));
            $mimeType = $file->getMimeType();
            $validated['photo'] = 'data:' . $mimeType . ';base64,' . $imageData;
        } else {
            unset($validated['photo']);
        }

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        // Check if employee has any work logs or assignments
        if ($employee->taskAssignments()->exists() || $employee->attendances()->exists()) {
            return redirect()->route('employees.index')
                ->with('error', 'Cannot delete employee with existing assignments or attendance records.');
        }

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['site', 'attendances' => function ($q) {
            $q->orderBy('date', 'desc')->limit(10);
        }, 'taskAssignments' => function ($q) {
            $q->with('task.project.site')->orderBy('assigned_at', 'desc')->limit(10);
        }]);

        // Calculate statistics
        $totalHoursWorked = $employee->taskAssignments()->sum('hours_worked');
        $totalEarnings = $employee->taskAssignments()->sum(\DB::raw('hours_worked * hourly_rate_at_time'));
        $activeTasks = $employee->taskAssignments()->whereNull('removed_at')->count();

        // Derive site from active task assignments if not directly assigned
        $derivedSite = $employee->site;
        if (!$derivedSite) {
            $activeAssignment = $employee->taskAssignments
                ->whereNull('removed_at')
                ->first();
            if ($activeAssignment && $activeAssignment->task && $activeAssignment->task->project) {
                $derivedSite = $activeAssignment->task->project->site;
            }
        }

        return view('employees.show', compact('employee', 'totalHoursWorked', 'totalEarnings', 'activeTasks', 'derivedSite'));
    }
}
