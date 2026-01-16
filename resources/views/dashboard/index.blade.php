@extends('layouts.app')
@section('page_title', 'Dashboard')
@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalProducts }}</h3>
                <p>Total Stock Quantity</p>
            </div>
            <div class="icon"><i class="fas fa-cubes"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $totalSites }}</h3>
                <p>Total Sites</p>
            </div>
            <div class="icon"><i class="fas fa-building"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $totalTransfersToday }}</h3>
                <p>Transfers Today</p>
            </div>
            <div class="icon"><i class="fas fa-exchange-alt"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Transfers</h3>
            </div>
            <div class="card-body p-0">
                <table id="recenttransfer" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Date</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransfers as $transfer)
                            <tr>
                                <td>{{ $transfer->product->name }}</td>
                                <td>{{ $transfer->from_type === 'warehouse' ? 'W' : 'S' }}
                                    - {{ $transfer->from_name }}</td>
                                <td>{{ $transfer->to_type === 'warehouse' ? 'W' : 'S' }}
                                    - {{ $transfer->to_name }}</td>
                                <td>{{ $transfer->transfer_date }}</td>
                                <td>{{ $transfer->quantity }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td>No recent transfers.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>

@push('scripts')
    <script>
        $(function () {
            $('.recenttransfer').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
            }).buttons().container().appendTo('.dataTables_wrapper .col-md-6:eq(0)');
        });

    </script>
@endpush
@endsection
