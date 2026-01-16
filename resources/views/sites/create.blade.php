@extends('layouts.app')
@section('page_title', 'Add Client')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('sites.index') }}">Clients</a></li>
  <li class="breadcrumb-item active">Create Client</li>
@endsection
@section('content')
<form method="POST" action="{{ route('sites.store') }}"> @csrf
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
