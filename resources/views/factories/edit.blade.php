@extends('layouts.app')
@section('page_title', 'Edit Factory')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('factories.index') }}">Factories</a></li>
  <li class="breadcrumb-item active">Edit Factory</li>
@endsection
@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Edit Factory</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('factories.update', $factory->id) }}">
      @csrf
      @method('PUT')
      <div class="form-group">
        <label for="name">Factory Name</label>
        <input type="text" name="name" value="{{ old('name', $factory->name) }}" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="location">Location</label>
        <input type="text" name="location" value="{{ old('location', $factory->location) }}" class="form-control">
      </div>
      <button type="submit" class="btn btn-success">Update</button>
      <a href="{{ route('factories.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
@endsection
