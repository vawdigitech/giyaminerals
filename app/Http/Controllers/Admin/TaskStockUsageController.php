<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Stock;
use App\Models\TaskStockUsage;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                    'unit_price' => $stock->product->unit_price ?? 0,
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

    /**
     * Return material from a task back to inventory
     */
    public function returnMaterial(Request $request, Task $task, TaskStockUsage $usage)
    {
        // Verify the usage belongs to this task
        if ($usage->task_id !== $task->id) {
            return response()->json([
                'success' => false,
                'message' => 'This material usage does not belong to this task',
            ], 403);
        }

        $validated = $request->validate([
            'quantity_to_return' => 'required|numeric|min:0.01',
            'destination_type' => 'required|in:warehouse,site',
            'destination_id' => 'required|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        // Validate quantity doesn't exceed current usage
        if ($validated['quantity_to_return'] > $usage->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot return more than the used quantity. Available: ' . $usage->quantity,
            ], 422);
        }

        // Validate destination exists
        if ($validated['destination_type'] === 'warehouse') {
            $destination = Warehouse::find($validated['destination_id']);
        } else {
            $destination = Site::find($validated['destination_id']);
        }

        if (!$destination) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid destination selected',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $sourceStock = $usage->stock;
            $project = $task->project;

            // Create transfer record for the return
            Transfer::create([
                'product_id' => $usage->product_id,
                'task_id' => $task->id,
                'task_stock_usage_id' => $usage->id,
                'from_type' => 'site',
                'from_id' => $project->site_id,
                'to_type' => $validated['destination_type'],
                'to_id' => $validated['destination_id'],
                'quantity' => $validated['quantity_to_return'],
                'transfer_date' => now(),
                'transfer_type' => 'return',
                'notes' => $validated['notes'] ?? 'Material returned from task',
                'created_by' => auth()->id(),
            ]);

            // Update source stock (decrease transferred_quantity since material is coming back)
            if ($sourceStock) {
                $sourceStock->decrement('transferred_quantity', $validated['quantity_to_return']);
                $sourceStock->update(['last_updated_at' => now()]);
            }

            // Update or create destination stock
            $destinationStock = Stock::firstOrCreate([
                'product_id' => $usage->product_id,
                'location_type' => $validated['destination_type'],
                'location_id' => $validated['destination_id'],
            ], [
                'received_quantity' => 0,
                'transferred_quantity' => 0,
                'last_updated_at' => now(),
            ]);
            $destinationStock->increment('received_quantity', $validated['quantity_to_return']);
            $destinationStock->update(['last_updated_at' => now()]);

            // Update or delete the TaskStockUsage
            $remainingQuantity = $usage->quantity - $validated['quantity_to_return'];
            if ($remainingQuantity <= 0) {
                // Delete the usage record directly from DB to avoid triggering the deleted event
                // which would restore stock (we handle stock updates manually above)
                DB::table('task_stock_usages')->where('id', $usage->id)->delete();
            } else {
                // Update the usage with reduced quantity
                $usage->update([
                    'quantity' => $remainingQuantity,
                    'total_cost' => $remainingQuantity * $usage->unit_price,
                ]);
            }

            // Recalculate task costs
            $task->recalculateCosts();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Material returned successfully. ' . $validated['quantity_to_return'] . ' units transferred to ' . $destination->name,
                'data' => [
                    'remaining_quantity' => $remainingQuantity > 0 ? $remainingQuantity : 0,
                    'deleted' => $remainingQuantity <= 0,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to return material: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available destinations (warehouses and sites) for material return
     */
    public function getReturnDestinations()
    {
        $warehouses = Warehouse::all()->map(function ($w) {
            return [
                'type' => 'warehouse',
                'id' => $w->id,
                'name' => $w->name,
                'label' => 'Warehouse: ' . $w->name,
            ];
        });

        $sites = Site::all()->map(function ($s) {
            return [
                'type' => 'site',
                'id' => $s->id,
                'name' => $s->name,
                'label' => 'Site: ' . $s->name,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'warehouses' => $warehouses,
                'sites' => $sites,
            ],
        ]);
    }
}
