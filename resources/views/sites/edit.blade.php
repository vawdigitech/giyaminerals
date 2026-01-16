@extends('layouts.app')
@section('page_title', 'Edit Client')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('sites.index') }}">Clients</a></li>
  <li class="breadcrumb-item active">Edit Client</li>
@endsection
@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Edit Client</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('sites.update', $site->id) }}">
      @csrf
      @method('PUT')
      <div class="form-group">
        <label for="name">Client Name</label>
        <input type="text" name="name" value="{{ old('name', $site->name) }}" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="location">Location</label>
        <input type="text" name="location" value="{{ old('location', $site->location) }}" class="form-control">
      </div>
      <button type="submit" class="btn btn-success">Update</button>
      <a href="{{ route('sites.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
@endsection
