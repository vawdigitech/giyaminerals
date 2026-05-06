<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\Stock;
use App\Models\Site;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Task;
use App\Models\TaskStockUsage;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::all();
        $transfers = Transfer::with(['product.category', 'task'])
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->from, fn($q) => $q->whereDate('transfer_date', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('transfer_date', '<=', $request->to))
            ->orderBy('transfer_date', 'desc')
            ->get();

        return view('transfers.index', compact('transfers', 'products'));
    }

    public function create()
    {
        return view('transfers.create', [
            'products' => Product::with('category')->get(),
            'warehouses' => Warehouse::all(),
            'sites' => Site::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from' => 'required|string',
            'to' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'transfer_date' => 'required|date',
            'task_id' => 'nullable|exists:tasks,id',
            'work_location' => 'nullable|required_with:task_id|string'
        ]);

        // If from and to are the same location, task assignment is required
        if ($request->from === $request->to) {
            $request->validate([
                'task_id' => 'required|exists:tasks,id',
            ], [
                'task_id.required' => 'Task assignment is required when transferring within the same location.'
            ]);
        }

        [$from_type, $from_id] = explode(':', $request->input('from'));
        [$to_type, $to_id] = explode(':', $request->input('to'));

        $data = [
            'product_id' => $request->product_id,
            'from_type' => $from_type,
            'from_id' => $from_id,
            'to_type' => $to_type,
            'to_id' => $to_id,
            'quantity' => $request->quantity,
            'transfer_date' => $request->transfer_date,
            'task_id' => $request->task_id,
            'created_by' => Auth::id(),
        ];

        $stockFrom = Stock::where([
            'product_id' => $data['product_id'],
            'location_type' => $from_type,
            'location_id' => $from_id
        ])->first();

        if (!$stockFrom || $stockFrom->balance < $data['quantity']) {
            return back()->withInput()->with('error', 'Insufficient stock in source.');
        }

        $isSameLocation = ($request->from === $request->to);

        DB::beginTransaction();
        try {
            if ($isSameLocation) {
                // Same location - just allocate to task, don't move stock
                $stockFrom->transferred_quantity += $data['quantity'];
                $stockFrom->last_updated_at = now();
                $stockFrom->save(); // Triggers saving event to recalculate balance
                $stockTo = $stockFrom; // Reference the same stock record
            } else {
                // Different locations - normal transfer
                $stockFrom->transferred_quantity += $data['quantity'];
                $stockFrom->last_updated_at = now();
                $stockFrom->save(); // Triggers saving event to recalculate balance

                $stockTo = Stock::firstOrCreate([
                    'product_id' => $data['product_id'],
                    'location_type' => $to_type,
                    'location_id' => $to_id
                ], [
                    'received_quantity' => 0,
                    'transferred_quantity' => 0,
                    'last_updated_at' => now()
                ]);
                $stockTo->received_quantity += $data['quantity'];
                $stockTo->last_updated_at = now();
                $stockTo->save(); // Triggers saving event to recalculate balance
            }

            Transfer::create($data);

            // If task is selected, create TaskStockUsage
            if ($request->task_id && $request->work_location) {
                [$workLocationType, $workLocationId] = explode(':', $request->work_location);

                // Validate that the task is assigned to this location
                $task = Task::find($request->task_id);
                if (!$task->isAtLocation($workLocationType, $workLocationId)) {
                    throw new \Exception("Task is not assigned to the selected work location");
                }

                $product = Product::find($request->product_id);
                $unitPrice = $product->unit_price ?? 0;

                TaskStockUsage::create([
                    'task_id' => $request->task_id,
                    'location_type' => $workLocationType,
                    'location_id' => $workLocationId,
                    'product_id' => $request->product_id,
                    'stock_id' => $stockTo->id,
                    'quantity' => $request->quantity,
                    'unit_price' => $unitPrice,
                    'notes' => 'Created from transfer',
                    'used_by' => Auth::id(),
                    'used_at' => now(),
                ]);
            }

            DB::commit();

            if ($isSameLocation) {
                $message = 'Materials assigned to task successfully.';
            } else {
                $message = 'Transfer recorded.';
                if ($request->task_id) {
                    $message .= ' Material usage also recorded for task.';
                }
            }

            return redirect()->route('transfers.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to record transfer: ' . $e->getMessage());
        }
    }

    public function getStockLocations(Request $request)
    {
        $productId = $request->product_id;

        $stocks = Stock::where('product_id', $productId)
            ->select('product_id', 'location_type', 'location_id', 'received_quantity', 'transferred_quantity')
            ->get();

        $warehouses = Warehouse::pluck('name', 'id');
        $sites = Site::pluck('name', 'id');

        $locations = $stocks->map(function ($stock) use ($warehouses, $sites) {
            $balance = $stock->received_quantity - $stock->transferred_quantity;

            $name = $stock->location_type === 'warehouse'
                ? $warehouses[$stock->location_id] ?? null
                : ($sites[$stock->location_id] ?? null);

            return $name ? [
                'type' => $stock->location_type,
                'id' => $stock->location_id,
                'label' => '[' . strtoupper(substr($stock->location_type, 0, 1)) . '] ' . $name . ' (Stock: ' . $balance . ')',
                'value' => "{$stock->location_type}:{$stock->location_id}"
            ] : null;
        })->filter()->values();

        return response()->json($locations);
    }

    /**
     * Get master tasks (parent_id = null) for projects at a given site
     */
    public function getTasksBySite(Site $site)
    {
        try {
            // Get all projects that are assigned to this site through project_locations
            $projects = Project::whereHas('sites', function($query) use ($site) {
                $query->where('sites.id', $site->id);
            })->pluck('id');

            if ($projects->isEmpty()) {
                \Log::warning("No projects found for site", ['site_id' => $site->id, 'site_name' => $site->name]);
            }

            // Get all master tasks from those projects that have a task_location for this site
            $tasks = Task::whereIn('project_id', $projects)
                ->whereNull('parent_id')
                ->whereHas('taskLocations', function($query) use ($site) {
                    $query->where('location_type', 'site')
                          ->where('location_id', $site->id);
                })
                ->with('project:id,name,code')
                ->orderBy('code')
                ->get()
                ->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'code' => $task->code,
                        'name' => $task->name,
                        'project_name' => $task->project->name ?? 'Unknown',
                        'project_code' => $task->project->code ?? '',
                        'display_name' => "[{$task->code}] {$task->name} ({$task->project->name})",
                    ];
                });

            \Log::info("Tasks loaded for site", [
                'site_id' => $site->id,
                'site_name' => $site->name,
                'project_count' => $projects->count(),
                'task_count' => $tasks->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => $tasks->isEmpty() ? 'No tasks found for this site' : null,
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to load tasks for site", [
                'site_id' => $site->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Failed to load tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subtasks for a given master task
     */
    public function getSubtasks(Task $task)
    {
        $subtasks = Task::where('parent_id', $task->id)
            ->orderBy('code')
            ->get()
            ->map(function ($subtask) {
                return [
                    'id' => $subtask->id,
                    'code' => $subtask->code,
                    'name' => $subtask->name,
                    'display_name' => "[{$subtask->code}] {$subtask->name}",
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subtasks,
        ]);
    }

    /**
     * Get work locations (factory/site) for a given task
     */
    public function getTaskLocations(Task $task)
    {
        try {
            $locations = $task->taskLocations()
                ->get()
                ->map(function ($taskLocation) {
                    $location = $taskLocation->getLocation();
                    return [
                        'location_type' => $taskLocation->location_type,
                        'location_id' => $taskLocation->location_id,
                        'location_name' => $location ? $location->name : 'Unknown',
                        'display_name' => strtoupper($taskLocation->location_type) . ': ' . ($location ? $location->name : 'Unknown'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $locations,
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to load task locations", [
                'task_id' => $task->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Failed to load task locations',
            ], 500);
        }
    }
}
