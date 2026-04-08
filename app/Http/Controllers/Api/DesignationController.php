<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\DesignationCategory;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $query = Designation::query();

        // Only active designations by default
        if (!$request->has('include_inactive')) {
            $query->active();
        }

        $designations = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $designations,
        ]);
    }

    public function show(Designation $designation)
    {
        return response()->json([
            'success' => true,
            'data' => $designation,
        ]);
    }

    public function categories()
    {
        $categories = DesignationCategory::active()->orderBy('name')->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function byCategory($id)
    {
        $designations = Designation::active()
            ->where('category_id', $id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $designations,
        ]);
    }
}
