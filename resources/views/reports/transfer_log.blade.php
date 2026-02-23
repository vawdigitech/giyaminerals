@extends('layouts.app')
@section('page_title', 'Transfer Log')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Transfer Log</li>
@endsection
@section('content')

<div class="card mb-4">
  <div class="card-header">
    <h3 class="card-title">Filter</h3>
  </div>
  <div class="card-body">
    <form method="GET" action="{{ route('reports.transfer_log') }}" class="row g-3">
      <div class="col-md-4">
        <label for="from_date" class="form-label">From Date</label>
        <input type="date" class="form-control" id="from_date" name="from_date" value="{{ request('from_date') }}">
      </div>
      <div class="col-md-4">
        <label for="to_date" class="form-label">To Date</label>
        <input type="date" class="form-control" id="to_date" name="to_date" value="{{ request('to_date') }}">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('reports.transfer_log') }}" class="btn btn-secondary ms-2">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Transfer Log</h3>
  </div>
  <div class="card-body">
    @if($transfers->isEmpty())
      <p class="text-center text-muted">No transfer records found.</p>
    @else
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Date</th>
            <th>Product</th>
            <th>From</th>
            <th>To</th>
            <th>Quantity</th>
            <th>Type</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          @foreach($transfers as $transfer)
          <tr>
            <td>{{ $transfer->created_at->format('Y-m-d H:i') }}</td>
            <td>{{ $transfer->product->name ?? 'N/A' }}</td>
            <td>{{ $transfer->from_name }}</td>
            <td>{{ $transfer->to_name }}</td>
            <td>{{ $transfer->quantity }}</td>
            <td>{{ ucfirst($transfer->transfer_type ?? '-') }}</td>
            <td>{{ $transfer->notes ?? '-' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>

@endsection
