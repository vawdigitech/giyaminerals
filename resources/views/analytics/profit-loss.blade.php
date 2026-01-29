@extends('layouts.app')
@section('page_title', 'Profit/Loss Analysis')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Profit/Loss Analysis</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>${{ number_format($summary['total_quoted'], 0) }}</h3>
                        <p>Total Quoted</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>${{ number_format($summary['total_actual'], 0) }}</h3>
                        <p>Total Actual Cost</p>
                    </div>
                    <div class="icon"><i class="fas fa-receipt"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-{{ $summary['total_profit_loss'] >= 0 ? 'success' : 'danger' }}">
                    <div class="inner">
                        <h3>${{ number_format(abs($summary['total_profit_loss']), 0) }}</h3>
                        <p>Total {{ $summary['total_profit_loss'] >= 0 ? 'Profit' : 'Loss' }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-{{ $summary['total_profit_loss'] >= 0 ? 'trending-up' : 'trending-down' }}"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $summary['profitable_projects'] }}/{{ $summary['profitable_projects'] + $summary['loss_projects'] }}</h3>
                        <p>Profitable Projects</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-pie"></i></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('analytics.profit-loss') }}" class="row g-3">
                    <div class="col-md-3">
                        <select name="site_id" class="form-control">
                            <option value="">All Sites</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                    {{ $site->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('analytics.profit-loss') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Project Cost Analysis</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped" id="profitLossTable">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Site</th>
                            <th class="text-right">Quoted</th>
                            <th class="text-right">Labor Cost</th>
                            <th class="text-right">Material Cost</th>
                            <th class="text-right">Actual Cost</th>
                            <th class="text-right">Profit/Loss</th>
                            <th class="text-right">Margin %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projectsData as $data)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $data['project']) }}">
                                        {{ $data['project']->code }} - {{ $data['project']->name }}
                                    </a>
                                </td>
                                <td>{{ $data['project']->site->name ?? '-' }}</td>
                                <td class="text-right">${{ number_format($data['quoted_amount'], 2) }}</td>
                                <td class="text-right">${{ number_format($data['labor_cost'], 2) }}</td>
                                <td class="text-right">${{ number_format($data['material_cost'], 2) }}</td>
                                <td class="text-right">${{ number_format($data['actual_amount'], 2) }}</td>
                                <td class="text-right {{ $data['is_profitable'] ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ $data['is_profitable'] ? '+' : '-' }}${{ number_format(abs($data['profit_loss']), 2) }}</strong>
                                </td>
                                <td class="text-right {{ $data['profit_margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $data['profit_margin'] }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="2">Total</th>
                            <th class="text-right">${{ number_format($summary['total_quoted'], 2) }}</th>
                            <th class="text-right">${{ number_format($summary['total_labor'], 2) }}</th>
                            <th class="text-right">${{ number_format($summary['total_material'], 2) }}</th>
                            <th class="text-right">${{ number_format($summary['total_actual'], 2) }}</th>
                            <th class="text-right {{ $summary['total_profit_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                                <strong>{{ $summary['total_profit_loss'] >= 0 ? '+' : '-' }}${{ number_format(abs($summary['total_profit_loss']), 2) }}</strong>
                            </th>
                            <th></th>
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
        $('#profitLossTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [[6, 'desc']],
            "pageLength": 25
        });
    });
</script>
@endpush
