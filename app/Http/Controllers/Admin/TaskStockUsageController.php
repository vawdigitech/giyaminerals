<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Stock;
use App\Models\TaskStockUsage;
use Illuminate\Http\Request;

class TaskStockUsageController extends Controller
{
    /**
     * Get available stock for a task's project site
     */
    public function availableStock(Task $task)
    {
        $project = $task->project;

        if (!$project || !$project->site_id) {
            return response()->json([
                'success' => false,
                'message' => 'Task project has no associated site',
                'data' => [],
            ]);
        }

        $stocks = Stock::with(['product.category'])
            ->where('location_type', 'site')
            ->where('location_id', $project->site_id)
            ->where('balance', '>', 0)
            ->orderBy('product_id')
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product->name ?? 'Unknown',
                    'product_unit' => $stock->product->unit ?? '',
                    'category' => $stock->product->category->name ?? 'Uncategorized',
                    'available_qty' => $stock->balance,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $stocks,
        ]);
    }

    /**
     * Store a new stock usage for a task
     */
    public function store(Request $request, Task $task)
    {
        $validated = $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $stock = Stock::with('product')->findOrFail($validated['stock_id']);

        // Validate stock belongs to the task's project site
        $project = $task->project;
        if (!$project || !$project->site_id) {
            return response()->json([
                'success' => false,
                'message' => 'Task project has no associated site',
            ], 422);
        }

        if ($stock->location_type !== 'site' || $stock->location_id != $project->site_id) {
            return response()->json([
                'success' => false,
                'message' => 'Stock does not belong to the project site',
            ], 422);
        }

        // Check if sufficient stock available
        if ($stock->balance < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $stock->balance . ' ' . ($stock->product->unit ?? ''),
            ], 422);
        }

        $usage = TaskStockUsage::create([
            'task_id' => $task->id,
            'product_id' => $stock->product_id,
            'stock_id' => $stock->id,
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'notes' => $validated['notes'] ?? null,
            'used_by' => auth()->id(),
            'used_at' => now(),
        ]);

        $usage->load(['product', 'stock']);

        return response()->json([
            'success' => true,
            'message' => 'Material added and deducted from stock',
            'data' => [
                'id' => $usage->id,
                'product_name' => $usage->product->name ?? 'Unknown',
                'product_unit' => $usage->product->unit ?? '',
                'quantity' => $usage->quantity,
                'unit_price' => $usage->unit_price,
                'total_cost' => $usage->total_cost,
                'notes' => $usage->notes,
                'used_at' => $usage->used_at->format('M d, Y H:i'),
            ],
        ]);
    }

    /**
     * Delete a stock usage (restores stock)
     */
    public function destroy(Task $task, TaskStockUsage $usage)
    {
        // Verify the usage belongs to this task
        if ($usage->task_id !== $task->id) {
            return response()->json([
                'success' => false,
                'message' => 'This material usage does not belong to this task',
            ], 403);
        }

        // Delete will trigger model event to restore stock
        $usage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Material removed and stock restored',
        ]);
    }

    /**
     * Get all stock usages for a task
     */
    public function index(Task $task)
    {
        $usages = $task->stockUsages()
            ->with(['product', 'stock', 'usedBy'])
            ->orderBy('used_at', 'desc')
            ->get()
            ->map(function ($usage) {
                return [
                    'id' => $usage->id,
                    'product_name' => $usage->product->name ?? 'Unknown',
                    'product_unit' => $usage->product->unit ?? '',
                    'quantity' => $usage->quantity,
                    'unit_price' => $usage->unit_price,
                    'total_cost' => $usage->total_cost,
                    'notes' => $usage->notes,
                    'used_by' => $usage->usedBy->name ?? 'Unknown',
                    'used_at' => $usage->used_at ? $usage->used_at->format('M d, Y H:i') : '-',
                ];
            });

        $task->load('project');

        return response()->json([
            'success' => true,
            'data' => [
                'usages' => $usages,
                'total_material_cost' => $usages->sum('total_cost'),
                'site_name' => $task->project->site->name ?? 'No Site',
            ],
        ]);
    }
}
