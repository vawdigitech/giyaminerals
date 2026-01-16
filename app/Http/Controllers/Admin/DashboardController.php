<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\WarehouseStock;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Stock::sum('balance');
        $totalSites = Site::count();
        $totalTransfersToday = Transfer::whereDate('created_at', Carbon::today())->count();

        $recentTransfers = Transfer::with('product')->latest()->take(5)->get();
        $lowStock = WarehouseStock::with('product', 'warehouse')
            ->where('quantity', '<', 10)->get();

        return view('dashboard.index', compact('totalProducts', 'totalSites', 'totalTransfersToday', 'recentTransfers', 'lowStock'));
    }
}
