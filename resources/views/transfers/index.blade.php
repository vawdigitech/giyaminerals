@extends('layouts.app')
@section('page_title', 'Transfer History')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Transfer History</li>
@endsection
@section('content')
<div class="mb-3">
    <form method="GET" class="row">
        <div class="col-md-3"><input type="date" name="from" class="form-control"
                value="{{ request('from') }}"></div>
        <div class="col-md-3"><input type="date" name="to" class="form-control"
                value="{{ request('to') }}"></div>
        <div class="col-md-3">
            <select name="product_id" class="form-control">
                <option value="">All Products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}"
                        {{ request('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary">Filter</button>
        </div>
    </form>
    <div class="text-right">
        <a href="{{ route('transfers.create') }}" class="btn btn-success">
            <i class="fas fa-exchange-alt"></i> New Transfer
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id='transferTable' class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfers as $t)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($t->transfer_date)->timezone('Asia/Kolkata')->format('Y-m-d H:i') }}</td>
                        </td>
                        <td>{{ $t->product->name }}</td>
                        <td>{{ $t->product->category->name }}</td>
                        <td>{{ $t->from_type === 'warehouse' ? 'W' : 'S' }}
                            - {{ $t->from_name }}</td>
                        <td>{{ $t->to_type === 'warehouse' ? 'W' : 'S' }}
                            - {{ $t->to_name }}</td>
                        <td>{{ $t->quantity }}</td>
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
            $('#transferTable').DataTable({
                responsive: true,
                autoWidth: false,
                lengthChange: true,
                pageLength: 10,
            }).buttons().container().appendTo('#transferTable_wrapper .col-md-6:eq(0)');
        });

    </script>
@endpush
