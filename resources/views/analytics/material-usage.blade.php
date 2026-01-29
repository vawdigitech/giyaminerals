@extends('layouts.app')
@section('page_title', 'Material Usage Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Material Usage Report</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $summary['total_items'] }}</h3>
                        <p>Total Usage Records</p>
                    </div>
                    <div class="icon"><i class="fas fa-list"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $summary['unique_products'] }}</h3>
                        <p>Different Products</p>
                    </div>
                    <div class="icon"><i class="fas fa-boxes"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>${{ number_format($summary['total_cost'], 0) }}</h3>
                        <p>Total Material Cost</p>
                    </div>
                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $summary['projects_count'] }}</h3>
                        <p>Projects</p>
                    </div>
                    <div class="icon"><i class="fas fa-project-diagram"></i></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('analytics.material-usage') }}" class="row g-3">
                    <div class="col-md-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3">
                        <label>Project</label>
                        <select name="project_id" class="form-control">
                            <option value="">All Projects</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">Filter</button>
                        <a href="{{ route('analytics.material-usage') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- By Product -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Usage by Product</h3>
                    </div>
                    <div class="card-body table-responsive p-0" style="max-height: 400px;">
                        <table class="table table-head-fixed">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-right">Qty Used</th>
                                    <th class="text-right">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byProduct as $data)
                                    <tr>
                                        <td>{{ $data['product']->name ?? 'Unknown' }}</td>
                                        <td class="text-right">{{ number_format($data['total_quantity'], 2) }}</td>
                                        <td class="text-right">${{ number_format($data['total_cost'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- By Project -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Usage by Project</h3>
                    </div>
                    <div class="card-body table-responsive p-0" style="max-height: 400px;">
                        <table class="table table-head-fixed">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th class="text-right">Items</th>
                                    <th class="text-right">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byProject as $data)
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.show', $data['project']) }}">
                                                {{ $data['project']->name ?? 'Unknown' }}
                                            </a>
                                        </td>
                                        <td class="text-right">{{ $data['items_count'] }}</td>
                                        <td class="text-right">${{ number_format($data['total_cost'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Usage -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Material Usage</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Task</th>
                            <th>Project</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Unit Cost</th>
                            <th class="text-right">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usages->take(50) as $usage)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($usage->used_at)->format('M d, Y') }}</td>
                                <td>{{ $usage->stock->product->name ?? '-' }}</td>
                                <td>{{ $usage->task->name ?? '-' }}</td>
                                <td>{{ $usage->task->project->name ?? '-' }}</td>
                                <td class="text-right">{{ number_format($usage->quantity, 2) }}</td>
                                <td class="text-right">${{ number_format($usage->unit_cost, 2) }}</td>
                                <td class="text-right">${{ number_format($usage->total_cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No material usage records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
