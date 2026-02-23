@extends('layouts.app')
@section('page_title', 'Stock Report')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Stock Report</li>
@endsection
@section('content')

@foreach($stockData as $siteId => $stocks)
<div class="card mb-4">
  <div class="card-header">
    <h3 class="card-title">{{ $stocks->first()->site->name ?? 'Unknown Site' }}</h3>
  </div>
  <div class="card-body">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Product</th>
          <th>Received</th>
          <th>Transferred</th>
          <th>Balance</th>
          <th>Last Updated</th>
        </tr>
      </thead>
      <tbody>
        @foreach($stocks as $stock)
        <tr>
          <td>{{ $stock->product->name ?? 'N/A' }}</td>
          <td>{{ $stock->received_quantity }}</td>
          <td>{{ $stock->transferred_quantity }}</td>
          <td>{{ $stock->balance }}</td>
          <td>{{ $stock->last_updated_at ?? $stock->updated_at }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endforeach

@if($stockData->isEmpty())
<div class="card">
  <div class="card-body">
    <p class="text-center text-muted">No stock data available.</p>
  </div>
</div>
@endif

@endsection
