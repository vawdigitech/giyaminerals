@extends('layouts.app')
@section('page_title', 'Client Details')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('sites.index') }}">Clients</a></li>
  <li class="breadcrumb-item active">{{ $site->name }}</li>
@endsection
@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Client Details</h3>
    <div class="card-tools">
      @can('sites.edit')
      <a href="{{ route('sites.edit', $site->id) }}" class="btn btn-info btn-sm">Edit</a>
      @endcan
      <a href="{{ route('sites.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
  </div>
  <div class="card-body">
    <table class="table table-bordered">
      <tr>
        <th style="width: 200px;">Name</th>
        <td>{{ $site->name }}</td>
      </tr>
      <tr>
        <th>Location</th>
        <td>{{ $site->location ?? '—' }}</td>
      </tr>
    </table>
  </div>
</div>
@endsection
