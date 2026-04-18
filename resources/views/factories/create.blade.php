@extends('layouts.app')
@section('page_title', 'Add Factory')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('factories.index') }}">Factories</a></li>
  <li class="breadcrumb-item active">Create Factory</li>
@endsection
@section('content')
<form method="POST" action="{{ route('factories.store') }}"> @csrf
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
