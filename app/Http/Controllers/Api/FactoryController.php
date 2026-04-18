<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use Illuminate\Http\Request;

class FactoryController extends Controller
{
    /**
     * Get list of all factories
     */
    public function index(Request $request)
    {
        $query = Factory::query();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $factories = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $factories,
        ]);
    }

    /**
     * Get factory details with projects and employees
     */
    public function show(Factory $factory)
    {
        $factory->load([
            'projects' => function ($q) {
                $q->with(['tasks' => function ($taskQuery) {
                    $taskQuery->whereNull('parent_id')
                             ->with(['subtasks', 'activeAssignments.employee']);
                }]);
            },
            'employees' => function ($q) {
                $q->where('status', 'active');
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $factory,
        ]);
    }

    /**
     * Get attendance summary for a factory
     */
    public function attendance(Factory $factory, Request $request)
    {
        $query = $factory->attendances()->with(['employee']);

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        } else {
            // Default to today
            $query->whereDate('date', today());
        }

        $attendances = $query->orderBy('check_in_time')->get();

        return response()->json([
            'success' => true,
            'data' => $attendances,
        ]);
    }
}
