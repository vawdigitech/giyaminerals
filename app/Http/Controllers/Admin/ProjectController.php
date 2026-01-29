<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        // Get stats (before filtering)
        $stats = [
            'total' => Project::count(),
            'completed' => Project::where('status', 'completed')->count(),
            'in_progress' => Project::where('status', 'in_progress')->count(),
            'pending' => Project::where('status', 'pending')->count(),
            'on_hold' => Project::where('status', 'on_hold')->count(),
        ];

        $query = Project::with('site')->withCount('tasks');

        // Filter by site
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(15);
        $sites = Site::orderBy('name')->get();

        return view('projects.index', compact('projects', 'sites', 'stats'));
    }

    public function create()
    {
        $sites = Site::orderBy('name')->get();
        return view('projects.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:projects',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'site_id' => 'required|exists:sites,id',
            'quoted_amount' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed,on_hold',
        ]);

        Project::create($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load(['site', 'tasks' => function ($q) {
            $q->whereNull('parent_id')->with('subtasks');
        }]);

        // Calculate statistics
        $totalTasks = $project->tasks()->count();
        $completedTasks = $project->tasks()->where('status', 'completed')->count();
        $laborCost = $project->tasks()->sum('labor_cost');
        $materialCost = $project->tasks()->sum('material_cost');
        $actualAmount = $laborCost + $materialCost;
        $profitLoss = $project->quoted_amount - $actualAmount;

        return view('projects.show', compact(
            'project', 'totalTasks', 'completedTasks',
            'laborCost', 'materialCost', 'actualAmount', 'profitLoss'
        ));
    }

    public function edit(Project $project)
    {
        $sites = Site::orderBy('name')->get();
        return view('projects.edit', compact('project', 'sites'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:projects,code,' . $project->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'site_id' => 'required|exists:sites,id',
            'quoted_amount' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed,on_hold',
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        if ($project->tasks()->exists()) {
            return redirect()->route('projects.index')
                ->with('error', 'Cannot delete project with existing tasks. Delete tasks first.');
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
