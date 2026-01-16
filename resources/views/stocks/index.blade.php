@extends('layouts.app')
@section('page_title', 'Stock Summary')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Stock Summary</li>
@endsection
@section('content')

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Current Stock Summary</h3>
  </div>
  <div class="card-body">
    <table id="stockTable" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>Location</th>
          <th>Received</th>
          <th>Issued</th>
          <th>Balance</th>
          <th>Last Updated</th>
        </tr>
      </thead>
      <tbody>
        @foreach($summary as $row)
        <tr>
          <td>{{ $row['product'] }}</td>
          <td>{{ $row['category'] }}</td>
          <td>{{ $row['location'] }}</td>
          <td>{{ $row['received'] }}</td>
          <td>{{ $row['issued'] }}</td>
          <td>{{ $row['balance'] }}</td>
          <td>{{ $row['last_transfer'] }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@endsection

@push('scripts')
<script>
  $(function () {
    $("#stockTable").DataTable({
      responsive: true,
      autoWidth: false
    });
  });
</script>
@endpush
