@extends('layouts.app')
@section('page_title', 'Add Warehouse')
@section('content')
<form method="POST" action="{{ route('warehouses.store') }}"> @csrf
  <div class="card">
    <div class="card-body">
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" class="form-control">
      </div>
    </div>
    <div class="card-footer">
      <button class="btn btn-success">Save</button>
    </div>
  </div>
</form>
@endsection