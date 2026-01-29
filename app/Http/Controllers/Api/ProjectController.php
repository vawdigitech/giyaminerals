<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Project::with(['site', 'tasks' => function ($q) {
            $q->whereNull('parent_id'); // Only top-level tasks
        }]);

        // Supervisors only see projects at their site
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

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $projects = $query->orderBy('code')->get();

        // Calculate progress for each project
        $projects->each(function ($project) {
            $project->calculated_progress = $project->calculateProgress();
        });

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    public function show(Project $project)
    {
        $project->load([
            'site',
            'tasks' => function ($q) {
                $q->whereNull('parent_id')
                  ->with(['subtasks', 'activeAssignments.employee']);
            },
        ]);

        $project->calculated_progress = $project->calculateProgress();
        $project->calculated_actual_amount = $project->calculateActualAmount();

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $siteId = $user->isSupervisor() ? $user->site_id : $request->site_id;

        $query = Project::query();

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $totalProjects = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        $pending = (clone $query)->where('status', 'pending')->count();

        $totalQuoted = (clone $query)->sum('quoted_amount');
        $totalActual = (clone $query)->sum('actual_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_projects' => $totalProjects,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'pending' => $pending,
                'total_quoted_amount' => $totalQuoted,
                'total_actual_amount' => $totalActual,
                'profit_loss' => $totalQuoted - $totalActual,
            ],
        ]);
    }

    public function updateProgress(Request $request, Project $project)
    {
        // Recalculate progress from tasks
        $project->progress = $project->calculateProgress();
        $project->actual_amount = $project->calculateActualAmount();

        // Update status based on progress
        if ($project->progress >= 100) {
            $project->status = 'completed';
            $project->actual_end_date = now();
        } elseif ($project->progress > 0) {
            $project->status = 'in_progress';
        }

        $project->save();

        return response()->json([
            'success' => true,
            'message' => 'Project progress updated',
            'data' => $project,
        ]);
    }
}
