@extends('layouts.app')
@section('page_title', 'Labor Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Labor Report</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $summary['total_employees'] }}</h3>
                        <p>Employees Worked</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($summary['total_hours'], 1) }}</h3>
                        <p>Total Hours</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>${{ number_format($summary['total_cost'], 0) }}</h3>
                        <p>Total Labor Cost</p>
                    </div>
                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ number_format($summary['average_daily_hours'], 1) }}</h3>
                        <p>Avg Daily Hours</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('analytics.labor-report') }}" class="row g-3">
                    <div class="col-md-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">Filter</button>
                        <a href="{{ route('analytics.labor-report') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Labor Data Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Labor by Employee</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped" id="laborTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th class="text-right">Days Worked</th>
                            <th class="text-right">Total Hours</th>
                            <th class="text-right">Avg Rate/Hr</th>
                            <th class="text-right">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($laborData as $data)
                            <tr>
                                <td>
                                    <a href="{{ route('employees.show', $data['employee']) }}">
                                        {{ $data['employee']->name }}
                                    </a>
                                    <br><small class="text-muted">{{ $data['employee']->employee_code }}</small>
                                </td>
                                <td>{{ ucfirst($data['employee']->role) }}</td>
                                <td class="text-right">{{ $data['days_worked'] }}</td>
                                <td class="text-right">{{ number_format($data['total_hours'], 2) }}</td>
                                <td class="text-right">${{ number_format($data['average_rate'], 2) }}</td>
                                <td class="text-right"><strong>${{ number_format($data['total_cost'], 2) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="3">Total</th>
                            <th class="text-right">{{ number_format($summary['total_hours'], 2) }}</th>
                            <th></th>
                            <th class="text-right">${{ number_format($summary['total_cost'], 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#laborTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [[5, 'desc']],
            "pageLength": 25
        });
    });
</script>
@endpush
