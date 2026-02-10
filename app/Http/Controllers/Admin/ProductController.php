<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index', ['products' => Product::all()]);
    }

    public function create()
    {

        $categories = ProductCategory::all();

        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'unit' => 'required',
            'unit_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:product_categories,id'
        ]);
        Product::create($request->only('name', 'unit', 'unit_price', 'category_id'));

        return redirect()->route('products.index')->with('success', 'Product added');
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::all();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required',
            'unit' => 'required',
            'unit_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:product_categories,id'
        ]);

        $product->update($request->only('name', 'unit', 'unit_price', 'category_id'));

        return redirect()->route('products.index')->with('success', 'Product updated');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted');
    }
}
