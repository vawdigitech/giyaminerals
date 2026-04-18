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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::all();
        $transfers = Transfer::with(['product'])
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->from, fn($q) => $q->whereDate('transfer_date', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('transfer_date', '<=', $request->to))
            ->orderBy('transfer_date', 'desc')->get();

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
            'to' => 'required|string|different:from',
            'quantity' => 'required|integer|min:1',
            'transfer_date' => 'required|date',
            'task_id' => 'nullable|exists:tasks,id',
            'work_location' => 'nullable|required_with:task_id|string'
        ]);

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

        DB::beginTransaction();
        try {
            $stockFrom->increment('transferred_quantity', $data['quantity']);
            $stockFrom->update(['last_updated_at' => now()]);

            $stockTo = Stock::firstOrCreate([
                'product_id' => $data['product_id'],
                'location_type' => $to_type,
                'location_id' => $to_id
            ], [
                'received_quantity' => 0,
                'transferred_quantity' => 0,
                'last_updated_at' => now()
            ]);
            $stockTo->increment('received_quantity', $data['quantity']);
            $stockTo->update(['last_updated_at' => now()]);

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

            $message = 'Transfer recorded.';
            if ($request->task_id) {
                $message .= ' Material usage also recorded for task.';
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
}
