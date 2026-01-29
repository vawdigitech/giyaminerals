<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteIssue;
use Illuminate\Http\Request;

class SiteIssueController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = SiteIssue::with(['site', 'task', 'reportedBy', 'assignedTo']);

        // Supervisors only see issues at their site
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

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $issues = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $issues,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'task_id' => 'nullable|exists:tasks,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:electrical,plumbing,safety,materials,equipment,structural,other',
            'priority' => 'required|in:low,medium,high,critical',
            'photos' => 'nullable|array',
            'photos.*' => 'string',
        ]);

        $user = $request->user();

        // If supervisor, default to their site
        if ($user->isSupervisor() && $user->site_id) {
            $validated['site_id'] = $user->site_id;
        }

        $validated['reported_by'] = $user->id;
        $validated['status'] = 'open';

        $issue = SiteIssue::create($validated);
        $issue->load('site', 'task', 'reportedBy');

        return response()->json([
            'success' => true,
            'message' => 'Issue reported successfully',
            'data' => $issue,
        ], 201);
    }

    public function show(SiteIssue $issue)
    {
        $issue->load(['site', 'task.project', 'reportedBy', 'assignedTo']);

        return response()->json([
            'success' => true,
            'data' => $issue,
        ]);
    }

    public function update(Request $request, SiteIssue $issue)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|in:electrical,plumbing,safety,materials,equipment,structural,other',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'photos' => 'nullable|array',
            'photos.*' => 'string',
        ]);

        $issue->update($validated);
        $issue->load('site', 'task', 'reportedBy', 'assignedTo');

        return response()->json([
            'success' => true,
            'message' => 'Issue updated',
            'data' => $issue,
        ]);
    }

    public function updateStatus(Request $request, SiteIssue $issue)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'resolution_notes' => 'nullable|string',
        ]);

        $issue->status = $validated['status'];

        if (in_array($validated['status'], ['resolved', 'closed'])) {
            $issue->resolved_at = now();
            $issue->resolution_notes = $validated['resolution_notes'] ?? null;
        }

        $issue->save();
        $issue->load('site', 'task', 'reportedBy', 'assignedTo');

        return response()->json([
            'success' => true,
            'message' => 'Issue status updated',
            'data' => $issue,
        ]);
    }

    public function assign(Request $request, SiteIssue $issue)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $issue->update([
            'assigned_to' => $validated['assigned_to'],
            'status' => 'in_progress',
        ]);

        $issue->load('site', 'task', 'reportedBy', 'assignedTo');

        return response()->json([
            'success' => true,
            'message' => 'Issue assigned',
            'data' => $issue,
        ]);
    }

    public function destroy(SiteIssue $issue)
    {
        $issue->delete();

        return response()->json([
            'success' => true,
            'message' => 'Issue deleted',
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $siteId = $user->isSupervisor() ? $user->site_id : $request->site_id;

        $query = SiteIssue::query();

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $total = $query->count();
        $open = (clone $query)->where('status', 'open')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        $resolved = (clone $query)->where('status', 'resolved')->count();
        $critical = (clone $query)->where('priority', 'critical')->where('status', '!=', 'resolved')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'open' => $open,
                'in_progress' => $inProgress,
                'resolved' => $resolved,
                'critical_pending' => $critical,
            ],
        ]);
    }

    public function categories()
    {
        $categories = [
            'electrical' => 'Electrical',
            'plumbing' => 'Plumbing',
            'safety' => 'Safety',
            'materials' => 'Materials',
            'equipment' => 'Equipment',
            'structural' => 'Structural',
            'other' => 'Other',
        ];

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
