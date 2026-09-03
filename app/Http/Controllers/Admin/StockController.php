<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Site;
use App\Models\Stock;
use App\Models\StockEntry;
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
            'quantity' => 'required|numeric|min:0.001',
            'entry_date' => 'required|date',
            'reference' => 'nullable|string'
        ]);

        $user = Auth::user();

        [$type, $id] = explode(':', $data['location']);

        DB::beginTransaction();
        try {
            // Create the stock entry
            $stockEntry = StockEntry::create([
                'product_id' => $data['product_id'],
                'location_type' => $type,
                'location_id' => $id,
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

            $stock->received_quantity = round((float) $stock->received_quantity + (float) $data['quantity'], 3);
            $stock->last_updated_at = now();
            $stock->save(); // Triggers saving event to recalculate balance

            DB::commit();

            return redirect()->route('stocks.entries')->with('success', 'Stock entry recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to record stock entry: ' . $e->getMessage())->withInput();
        }
    }

    public function entries()
    {
        $entries = StockEntry::with('product.category', 'user')
            ->latest()
            ->get();
        return view('stocks.entries', compact('entries'));
    }

    public function edit(StockEntry $stockEntry)
    {
        return view('stocks.edit', [
            'stockEntry' => $stockEntry,
            'products' => Product::with('category')->get(),
            'warehouses' => Warehouse::all(),
            'sites' => Site::all(),
        ]);
    }

    public function update(Request $request, StockEntry $stockEntry)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'location' => 'required|string',
            'quantity' => 'required|numeric|min:0.001',
            'entry_date' => 'required|date',
            'reference' => 'nullable|string',
        ]);

        [$type, $id] = explode(':', $data['location']);

        DB::beginTransaction();
        try {
            $oldProductId = $stockEntry->product_id;
            $oldLocationType = $stockEntry->location_type;
            $oldLocationId = $stockEntry->location_id;
            $oldQuantity = $stockEntry->quantity;

            $stockKeyChanged = $oldProductId != $data['product_id']
                || $oldLocationType != $type
                || (int) $oldLocationId != (int) $id;
            $quantityChanged = $oldQuantity != $data['quantity'];

            if ($stockKeyChanged || $quantityChanged) {
                $sameStockKey = !$stockKeyChanged;

                if ($sameStockKey) {
                    $stock = $this->findStock($oldProductId, $oldLocationType, $oldLocationId);
                    if (!$stock) {
                        throw new \RuntimeException('Stock record not found for this entry.');
                    }

                    $delta = (float) $data['quantity'] - (float) $oldQuantity;
                    $newReceived = round((float) $stock->received_quantity + $delta, 3);
                    if (round($newReceived - (float) $stock->transferred_quantity, 3) < 0) {
                        throw new \RuntimeException('Cannot update: quantity would fall below already issued/transferred stock.');
                    }

                    $stock->received_quantity = $newReceived;
                    $stock->last_updated_at = now();
                    $stock->save();
                } else {
                    $oldStock = $this->findStock($oldProductId, $oldLocationType, $oldLocationId);
                    if ($oldStock) {
                        $newReceived = round((float) $oldStock->received_quantity - (float) $oldQuantity, 3);
                        if (round($newReceived - (float) $oldStock->transferred_quantity, 3) < 0) {
                            throw new \RuntimeException('Cannot update: reversing this entry would make stock balance negative.');
                        }
                        $oldStock->received_quantity = $newReceived;
                        $oldStock->last_updated_at = now();
                        $oldStock->save();
                    }

                    $newStock = Stock::firstOrCreate([
                        'product_id' => $data['product_id'],
                        'location_type' => $type,
                        'location_id' => $id,
                    ], [
                        'received_quantity' => 0,
                        'transferred_quantity' => 0,
                        'last_updated_at' => now(),
                    ]);

                    $newStock->received_quantity = round((float) $newStock->received_quantity + (float) $data['quantity'], 3);
                    $newStock->last_updated_at = now();
                    $newStock->save();
                }
            }

            $stockEntry->update([
                'product_id' => $data['product_id'],
                'location_type' => $type,
                'location_id' => $id,
                'quantity' => $data['quantity'],
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'],
            ]);

            DB::commit();

            return redirect()->route('stocks.entries')->with('success', 'Stock entry updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(StockEntry $stockEntry)
    {
        DB::beginTransaction();
        try {
            $stock = $this->findStock(
                $stockEntry->product_id,
                $stockEntry->location_type,
                $stockEntry->location_id
            );

            if ($stock) {
                $newReceived = round((float) $stock->received_quantity - (float) $stockEntry->quantity, 3);
                if (round($newReceived - (float) $stock->transferred_quantity, 3) < 0) {
                    throw new \RuntimeException('Cannot delete: stock from this entry has already been issued or transferred.');
                }

                $stock->received_quantity = $newReceived;
                $stock->last_updated_at = now();
                $stock->save();
            }

            $stockEntry->delete();

            DB::commit();

            return redirect()->route('stocks.entries')->with('success', 'Stock entry deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('stocks.entries')->with('error', $e->getMessage());
        }
    }

    private function findStock(int $productId, string $locationType, int $locationId): ?Stock
    {
        return Stock::where([
            'product_id' => $productId,
            'location_type' => $locationType,
            'location_id' => $locationId,
        ])->first();
    }
}
