<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteIssue;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index(Request $request)
    {
        $query = SiteIssue::with(['site', 'task', 'reportedBy', 'assignedTo']);

        // Filter by site
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $issues = $query->orderBy('created_at', 'desc')->paginate(15);
        $sites = Site::orderBy('name')->get();

        // Summary statistics
        $summary = [
            'total' => SiteIssue::count(),
            'open' => SiteIssue::where('status', 'open')->count(),
            'in_progress' => SiteIssue::where('status', 'in_progress')->count(),
            'resolved' => SiteIssue::where('status', 'resolved')->count(),
            'critical_pending' => SiteIssue::where('priority', 'critical')
                ->whereIn('status', ['open', 'in_progress'])->count(),
        ];

        $categories = [
            'electrical' => 'Electrical',
            'plumbing' => 'Plumbing',
            'safety' => 'Safety',
            'materials' => 'Materials',
            'equipment' => 'Equipment',
            'structural' => 'Structural',
            'other' => 'Other',
        ];

        return view('issues.index', compact('issues', 'sites', 'summary', 'categories'));
    }

    public function show(SiteIssue $issue)
    {
        $issue->load(['site', 'task.project', 'reportedBy', 'assignedTo']);
        $supervisors = User::role('supervisor')->orderBy('name')->get();

        return view('issues.show', compact('issue', 'supervisors'));
    }

    public function updateStatus(Request $request, SiteIssue $issue)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'resolution_notes' => 'nullable|string',
        ]);

        $issue->status = $validated['status'];

        if ($validated['status'] === 'resolved' || $validated['status'] === 'closed') {
            $issue->resolved_at = now();
            $issue->resolution_notes = $validated['resolution_notes'] ?? null;
        }

        $issue->save();

        return redirect()->back()->with('success', 'Issue status updated successfully.');
    }

    public function assign(Request $request, SiteIssue $issue)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $issue->assigned_to = $validated['assigned_to'];

        if ($issue->status === 'open') {
            $issue->status = 'in_progress';
        }

        $issue->save();

        return redirect()->back()->with('success', 'Issue assigned successfully.');
    }

    public function destroy(SiteIssue $issue)
    {
        $issue->delete();

        return redirect()->route('issues.index')
            ->with('success', 'Issue deleted successfully.');
    }
}
