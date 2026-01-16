@extends('layouts.app')
@section('page_title', 'Add Category')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Item Categories</a></li>
  <li class="breadcrumb-item active">Create Category</li>
@endsection
@section('content')
<form method="POST" action="{{ route('categories.store') }}"> @csrf
  <div class="card">
    <div class="card-body">
      <div class="form-group">
        <label>Category Name</label>
        <input type="text" name="name" class="form-control" required>
      </div>
    </div>
    <div class="card-footer">
      <button class="btn btn-success">Save</button>
    </div>
  </div>
</form>
@endsection