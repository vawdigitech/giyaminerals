<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\DesignationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $query = Designation::withCount('employees')->with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $designations = $query->orderBy('name')->paginate(15)->withQueryString();
        $filterCategories = DesignationCategory::active()->orderBy('name')->get();

        return view('designations.index', compact('designations', 'filterCategories'));
    }

    public function byCategory($id)
    {
        $designations = Designation::active()
            ->where('category_id', $id)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($designations);
    }

    public function create()
    {
        $categories = DesignationCategory::active()->orderBy('name')->get();
        return view('designations.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:50|unique:designations',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
            'category_id' => 'nullable|exists:designation_categories,id',
        ]);

        $validated['code'] = Str::upper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active', true);

        Designation::create($validated);

        return redirect()->route('designations.index')
            ->with('success', 'Designation created successfully.');
    }

    public function edit(Designation $designation)
    {
        $categories = DesignationCategory::active()->orderBy('name')->get();
        return view('designations.edit', compact('designation', 'categories'));
    }

    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:50|unique:designations,code,' . $designation->id,
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
            'category_id' => 'nullable|exists:designation_categories,id',
        ]);

        $validated['code'] = Str::upper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $designation->update($validated);

        return redirect()->route('designations.index')
            ->with('success', 'Designation updated successfully.');
    }

    public function destroy(Designation $designation)
    {
        if ($designation->employees()->exists()) {
            return redirect()->route('designations.index')
                ->with('error', 'Cannot delete designation with existing employees. Reassign employees first.');
        }

        $designation->delete();

        return redirect()->route('designations.index')
            ->with('success', 'Designation deleted successfully.');
    }
}
