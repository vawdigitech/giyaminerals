<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DesignationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DesignationCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = DesignationCategory::withCount('designations');

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

        $categories = $query->orderBy('name')->paginate(15);

        return view('designation-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('designation-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:50|unique:designation_categories',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
        ]);

        $validated['code']      = Str::upper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active', true);

        DesignationCategory::create($validated);

        return redirect()->route('designation-categories.index')
            ->with('success', 'Designation category created successfully.');
    }

    public function edit(DesignationCategory $designationCategory)
    {
        return view('designation-categories.edit', compact('designationCategory'));
    }

    public function update(Request $request, DesignationCategory $designationCategory)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:50|unique:designation_categories,code,' . $designationCategory->id,
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
        ]);

        $validated['code']      = Str::upper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $designationCategory->update($validated);

        return redirect()->route('designation-categories.index')
            ->with('success', 'Designation category updated successfully.');
    }

    public function destroy(DesignationCategory $designationCategory)
    {
        if ($designationCategory->designations()->exists()) {
            return redirect()->route('designation-categories.index')
                ->with('error', 'Cannot delete category with existing designations. Reassign designations first.');
        }

        $designationCategory->delete();

        return redirect()->route('designation-categories.index')
            ->with('success', 'Designation category deleted successfully.');
    }
}
