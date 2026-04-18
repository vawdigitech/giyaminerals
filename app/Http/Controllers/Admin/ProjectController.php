<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Site;
use App\Models\Factory;
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

        $query = Project::with(['sites', 'factories'])->withCount('tasks');

        // Filter by site (using many-to-many relationship)
        if ($request->filled('site_id')) {
            $query->whereHas('sites', function ($q) use ($request) {
                $q->where('sites.id', $request->site_id);
            });
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
        $factories = Factory::orderBy('name')->get();
        $nextCode = $this->generateNextCode();
        return view('projects.create', compact('sites', 'factories', 'nextCode'));
    }

    public function generateCode()
    {
        return response()->json(['code' => $this->generateNextCode()]);
    }

    private function generateNextCode(): string
    {
        $year = date('Y');
        $prefix = "PRJ-{$year}-";

        $last = Project::where('code', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->value('code');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:projects',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'exists:sites,id',
            'factory_ids' => 'nullable|array',
            'factory_ids.*' => 'exists:factories,id',
            'quoted_amount' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed,on_hold',
        ]);

        // Ensure at least one location is selected
        if (empty($validated['site_ids']) && empty($validated['factory_ids'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['location' => 'Please select at least one site or factory.']);
        }

        // Create project
        $project = Project::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'quoted_amount' => $validated['quoted_amount'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
        ]);

        // Attach sites
        if (!empty($validated['site_ids'])) {
            foreach ($validated['site_ids'] as $siteId) {
                $project->sites()->attach($siteId);
            }
        }

        // Attach factories
        if (!empty($validated['factory_ids'])) {
            foreach ($validated['factory_ids'] as $factoryId) {
                $project->factories()->attach($factoryId);
            }
        }

        return redirect()->route('projects.index')
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load(['sites', 'factories', 'tasks' => function ($q) {
            $q->whereNull('parent_id')->with('subtasks');
        }]);

        $allTasks = $project->tasks();

        // Calculate statistics
        $totalTasks     = $allTasks->count();
        $completedTasks = $allTasks->where('status', 'completed')->count();
        $inProgressTasks = $allTasks->where('status', 'in_progress')->count();
        $pendingTasks   = $allTasks->where('status', 'pending')->count();
        $onHoldTasks    = $allTasks->where('status', 'on_hold')->count();

        $laborCost    = $project->tasks()->sum('labor_cost');
        $materialCost = $project->tasks()->sum('material_cost');
        $actualAmount = $laborCost + $materialCost;
        $quotedTasks  = $project->tasks()->sum('quoted_amount');
        $profitLoss   = $project->quoted_amount - $actualAmount;
        $margin       = $project->quoted_amount > 0
            ? round(($profitLoss / $project->quoted_amount) * 100, 1)
            : 0;
        $burnPct = $project->quoted_amount > 0
            ? min(round(($actualAmount / $project->quoted_amount) * 100), 100)
            : 0;

        return view('projects.show', compact(
            'project', 'totalTasks', 'completedTasks', 'inProgressTasks',
            'pendingTasks', 'onHoldTasks', 'laborCost', 'materialCost',
            'actualAmount', 'quotedTasks', 'profitLoss', 'margin', 'burnPct'
        ));
    }

    public function edit(Project $project)
    {
        $project->load(['sites', 'factories']);
        $sites = Site::orderBy('name')->get();
        $factories = Factory::orderBy('name')->get();
        return view('projects.edit', compact('project', 'sites', 'factories'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:projects,code,' . $project->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'exists:sites,id',
            'factory_ids' => 'nullable|array',
            'factory_ids.*' => 'exists:factories,id',
            'quoted_amount' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed,on_hold',
        ]);

        // Ensure at least one location is selected
        if (empty($validated['site_ids']) && empty($validated['factory_ids'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['location' => 'Please select at least one site or factory.']);
        }

        // Update project
        $project->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'quoted_amount' => $validated['quoted_amount'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
        ]);

        // Sync sites
        if (isset($validated['site_ids'])) {
            $project->sites()->sync($validated['site_ids']);
        } else {
            $project->sites()->detach();
        }

        // Sync factories
        if (isset($validated['factory_ids'])) {
            $project->factories()->sync($validated['factory_ids']);
        } else {
            $project->factories()->detach();
        }

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

    /**
     * Get project locations (API endpoint for task creation)
     */
    public function getLocations(Project $project)
    {
        $project->load(['sites', 'factories']);

        $locations = [];

        foreach ($project->sites as $site) {
            $locations[] = [
                'type' => 'site',
                'id' => $site->id,
                'name' => $site->name . ' (Site)',
            ];
        }

        foreach ($project->factories as $factory) {
            $locations[] = [
                'type' => 'factory',
                'id' => $factory->id,
                'name' => $factory->name . ' (Factory)',
            ];
        }

        return response()->json([
            'success' => true,
            'locations' => $locations,
        ]);
    }
}
