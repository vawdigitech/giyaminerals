<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Transfer;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function stockSummary() {
        $stockData = Stock::with('product', 'site')->get()->groupBy('site_id');
        return view('reports.stock_summary', compact('stockData'));
    }

    public function transferLog(Request $request) {
        $query = Transfer::with('product', 'fromSite', 'toSite');

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        $transfers = $query->latest()->get();
        return view('reports.transfer_log', compact('transfers'));
    }
}
