<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Project;
use App\Models\Site;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\Task;
use App\Models\TaskStockUsage;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::with('product.category')->get();

        $summary = $stocks->map(function ($stock) {
            $locationName = $stock->location_type === 'warehouse'
                ? Warehouse::find($stock->location_id)?->name
                : Site::find($stock->location_id)?->name;

            return [
                'product' => $stock->product->name,
                'category' => $stock->product->category->name ?? '-',
                'location' => strtoupper($stock->location_type[0]) . ' - ' . $locationName,
                'received' => $stock->received_quantity,
                'issued' => $stock->transferred_quantity,
                'balance' => $stock->balance,
                'last_transfer' => $stock->last_updated_at
                    ? Carbon::parse($stock->last_updated_at)->format('Y-m-d H:i')
                    : '-',
            ];
        });

        return view('stocks.index', ['summary' => $summary]);
    }

    public function create()
    {
        return view('stocks.entry', [
            'products' => Product::with('category')->get(),
            'warehouses' => Warehouse::all(),
            'sites' => Site::all()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'location' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'entry_date' => 'required|date',
            'reference' => 'nullable|string',
            'task_id' => 'nullable|exists:tasks,id'
        ]);

        $user = Auth::user();

        [$type, $id] = explode(':', $data['location']);
        $taskId = $data['task_id'] ?? null;

        DB::beginTransaction();
        try {
            // Create the stock entry
            $stockEntry = StockEntry::create([
                'product_id' => $data['product_id'],
                'location_type' => $type,
                'location_id' => $id,
                'task_id' => $taskId,
                'quantity' => $data['quantity'],
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'],
                'created_by' => $user->id ?? null,
            ]);

            // Create or update stock record
            $stock = Stock::firstOrCreate([
                'product_id' => $data['product_id'],
                'location_type' => $type,
                'location_id' => $id
            ], [
                'received_quantity' => 0,
                'transferred_quantity' => 0,
                'last_updated_at' => now()
            ]);

            $stock->increment('received_quantity', $data['quantity']);
            $stock->update(['last_updated_at' => now()]);

            // If task is selected, auto-create TaskStockUsage
            if ($taskId) {
                $product = Product::find($data['product_id']);
                $unitPrice = $product->unit_price ?? 0;

                TaskStockUsage::create([
                    'task_id' => $taskId,
                    'product_id' => $data['product_id'],
                    'stock_id' => $stock->id,
                    'quantity' => $data['quantity'],
                    'unit_price' => $unitPrice,
                    'notes' => 'Auto-created from stock entry: ' . ($data['reference'] ?? 'No reference'),
                    'used_by' => $user->id ?? null,
                    'used_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('stocks.entries')->with('success', 'Stock entry recorded.' . ($taskId ? ' Material usage also recorded for task.' : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to record stock entry: ' . $e->getMessage())->withInput();
        }
    }

    public function entries()
    {
        $entries = StockEntry::with('product', 'user', 'task')->orderByDesc('entry_date')->get();
        return view('stocks.entries', compact('entries'));
    }

    /**
     * Get master tasks (parent_id = null) for projects at a given site
     */
    public function getTasksBySite(Site $site)
    {
        $projects = Project::where('site_id', $site->id)->pluck('id');

        $tasks = Task::whereIn('project_id', $projects)
            ->whereNull('parent_id')
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

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
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
}
