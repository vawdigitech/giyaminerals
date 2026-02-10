@extends('layouts.app')
@section('page_title', 'Stock Entries')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Stock Entries</li>
@endsection
@section('content')
<div class="card">
  <div class="card-header">
        <h3 class="card-title">Stock Entry History</h3>
        <div class="card-tools">
            @can('inventory.create')
            <a href="{{ route('stocks.entry') }}" class="btn btn-primary btn-sm">New Stock Entry</a>
            @endcan
        </div>
    </div>
  <div class="card-body">
    <table id="entryTable" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>Location</th>
          <th>Task</th>
          <th>Qty</th>
          <th>Reference</th>
          <th>Entry Date</th>
          <th>Entered By</th>
        </tr>
      </thead>
      <tbody>
        @foreach($entries as $e)
        <tr>
          <td>{{ $e->product->name }}</td>
          <td>{{ $e->product->category->name ?? '-' }}</td>
          <td>{{ strtoupper($e->location_type[0]) }} - {{ $e->location_name }}</td>
          <td>
            @if($e->task)
              <a href="{{ route('tasks.show', $e->task) }}" class="text-primary">
                [{{ $e->task->code }}] {{ Str::limit($e->task->name, 20) }}
              </a>
            @else
              -
            @endif
          </td>
          <td>{{ $e->quantity }}</td>
          <td>{{ $e->reference ?? '-' }}</td>
          <td>{{ \Carbon\Carbon::parse($e->entry_date)->format('Y-m-d') }}</td>
          <td>{{ $e->user->name ?? 'N/A' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@endsection

@push('scripts')
<script>
  $('#entryTable').DataTable();
</script>
@endpush
