@extends('layouts.app')
@section('page_title', 'Edit Product')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Items</a></li>
  <li class="breadcrumb-item active">Edit Item</li>
@endsection
@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Edit Item</h3>
  </div>
  <div class="card-body">
    <form action="{{ route('products.update', $product->id) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="form-group">
        <label for="name">Item Name</label>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="unit">Unit</label>
        <input type="text" name="unit" value="{{ old('unit', $product->unit) }}" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="category_id">Category</label>
        <select name="category_id" class="form-control" required>
          <option value="">Select Category</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ $category->id == $product->category_id ? 'selected' : '' }}>
              {{ $category->name }}
            </option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="btn btn-success">Update</button>
      <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
@endsection
