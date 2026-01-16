@extends('layouts.app')
@section('page_title', 'Edit Warehouse')
@section('content')
<form method="POST" action="{{ route('warehouses.update', $warehouse->id) }}"> @csrf @method('PUT')
  <div class="card">
    <div class="card-body">
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="{{ $warehouse->name }}" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" value="{{ $warehouse->location }}" class="form-control">
      </div>
    </div>
    <div class="card-footer">
      <button class="btn btn-primary">Update</button>
    </div>
  </div>
</form>
@endsection
