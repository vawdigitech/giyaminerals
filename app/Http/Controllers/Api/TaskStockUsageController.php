<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Stock;
use App\Models\TaskStockUsage;
use Illuminate\Http\Request;

class TaskStockUsageController extends Controller
{
    public function index(Request $request)
    {
        $query = TaskStockUsage::with(['task.project', 'product', 'stock']);

        // Filter by task
        if ($request->has('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('used_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('used_at', '<=', $request->to_date);
        }

        $usages = $query->orderBy('used_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $usages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'product_id' => 'required|exists:products,id',
            'stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $stock = Stock::findOrFail($validated['stock_id']);

        // Validate stock belongs to the product
        if ($stock->product_id != $validated['product_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Stock does not match the selected product',
            ], 422);
        }

        // Check if sufficient stock available
        if ($stock->balance < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $stock->balance,
            ], 422);
        }

        $usage = TaskStockUsage::create([
            'task_id' => $validated['task_id'],
            'product_id' => $validated['product_id'],
            'stock_id' => $validated['stock_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'notes' => $validated['notes'] ?? null,
            'used_by' => $user->id,
            'used_at' => now(),
        ]);

        $usage->load('task', 'product', 'stock');

        return response()->json([
            'success' => true,
            'message' => 'Stock usage recorded and deducted from inventory',
            'data' => $usage,
        ], 201);
    }

    public function show(TaskStockUsage $stockUsage)
    {
        $stockUsage->load(['task.project', 'product', 'stock', 'usedBy']);

        return response()->json([
            'success' => true,
            'data' => $stockUsage,
        ]);
    }

    public function destroy(TaskStockUsage $stockUsage)
    {
        // This will restore the stock via the model's deleted event
        $stockUsage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stock usage removed and quantity restored to inventory',
        ]);
    }

    public function byTask(Task $task)
    {
        $usages = $task->stockUsages()
            ->with(['product', 'stock', 'usedBy'])
            ->orderBy('used_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'task' => $task->load('project'),
                'usages' => $usages,
                'total_material_cost' => $usages->sum('total_cost'),
            ],
        ]);
    }

    public function availableStock(Request $request)
    {
        $user = $request->user();

        $query = Stock::with(['product.category'])
            ->where('balance', '>', 0);

        // Filter by location (site or warehouse)
        if ($request->has('location_type') && $request->has('location_id')) {
            $query->where('location_type', $request->location_type)
                  ->where('location_id', $request->location_id);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Search by product name
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $stocks = $query->orderBy('balance', 'desc')->get();

        // Add location names
        $stocks->each(function ($stock) {
            if ($stock->location_type === 'App\\Models\\Warehouse') {
                $stock->location_name = $stock->location?->name ?? 'Unknown Warehouse';
            } else {
                $stock->location_name = $stock->location?->name ?? 'Unknown Site';
            }
        });

        return response()->json([
            'success' => true,
            'data' => $stocks,
        ]);
    }
}
