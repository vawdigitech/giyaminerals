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
            'reference' => 'nullable|string'
        ]);

        $user = Auth::user();

        [$type, $id] = explode(':', $data['location']);

        StockEntry::create([
            'product_id' => $data['product_id'],
            'location_type' => $type,
            'location_id' => $id,
            'quantity' => $data['quantity'],
            'entry_date' => $data['entry_date'],
            'reference' => $data['reference'],
            'created_by' => $user->id ?? '',
        ]);

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

        return redirect()->route('stocks.entries')->with('success', 'Stock entry recorded.');
    }

    public function entries()
    {
        $entries = StockEntry::with('product', 'user')->orderByDesc('entry_date')->get();
        return view('stocks.entries', compact('entries'));
    }
}
