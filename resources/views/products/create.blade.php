@extends('layouts.app')
@section('page_title', 'Add Item')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Items</a></li>
  <li class="breadcrumb-item active">Create Product</li>
@endsection
@section('content')
<section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="card card-primary">
          <form action="{{ route('products.store') }}" method="POST"> @csrf
            <div class="card-body">
              <div class="form-group">
                <label for="name">Item Name</label>
                <input type="text" class="form-control" name="name" required>
              </div>
              <div class="form-group">
                <label for="category_id">Category</label>
                <select name="category_id" class="form-control" required>
                  <option value="">Select Category</option>
                  @foreach($categories as $category)
                  <option value="{{ $category->id }}">{{ $category->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label for="unit">Unit</label>
                <select name="unit" class="form-control" required>
                  <option value="">Select Unit</option>
                  <option value="kg">Kilogram</option>
                  <option value="inches">Inches</option>
                  <option value="meter">Meter</option>
                  <option value="bucket">Bucket</option>
                  <option value="bundle">Bundle</option>
                  <option value="liter">Liter</option>
                  <option value="roll">Roll</option>
                  <option value="bag">Bag</option>
                  <option value="pieces">Pieces</option>
                  <option value="nos">Nos</option>
                </select>
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-success">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
</section>
@endsection