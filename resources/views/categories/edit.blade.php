@extends('layouts.app')
@section('page_title', 'Edit Category')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Item Categories</a></li>
  <li class="breadcrumb-item active">Edit Category</li>
@endsection
@section('content')
<form method="POST" action="{{ route('categories.update', $category->id) }}"> @csrf @method('PUT')
  <div class="card">
    <div class="card-body">
      <div class="form-group">
        <label>Category Name</label>
        <input type="text" name="name" value="{{ $category->name }}" class="form-control" required>
      </div>
    </div>
    <div class="card-footer">
      <button class="btn btn-primary">Update</button>
    </div>
  </div>
</form>
@endsection
