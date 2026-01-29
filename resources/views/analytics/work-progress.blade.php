@extends('layouts.app')
@section('page_title', 'Work Progress Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Work Progress Report</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $summary['total_projects'] }}</h3>
                        <p>Total Projects</p>
                    </div>
                    <div class="icon"><i class="fas fa-project-diagram"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $summary['total_tasks'] }}</h3>
                        <p>Total Tasks</p>
                    </div>
                    <div class="icon"><i class="fas fa-tasks"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $summary['completed_tasks'] }}</h3>
                        <p>Completed Tasks</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $summary['average_progress'] }}%</h3>
                        <p>Average Progress</p>
                    </div>
                    <div class="icon"><i class="fas fa-percentage"></i></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('analytics.work-progress') }}" class="row g-3">
                    <div class="col-md-4">
                        <select name="project_id" class="form-control">
                            <option value="">All Projects</option>
                            @foreach($allProjects as $project)
                                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->code }} - {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('analytics.work-progress') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Projects Progress -->
        <div class="row">
            @foreach($projects as $data)
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <a href="{{ route('projects.show', $data['project']) }}">
                                    {{ $data['project']->code }} - {{ $data['project']->name }}
                                </a>
                            </h3>
                            <div class="card-tools">
                                @php
                                    $statusColors = ['pending' => 'secondary', 'in_progress' => 'primary', 'completed' => 'success', 'on_hold' => 'warning'];
                                @endphp
                                <span class="badge badge-{{ $statusColors[$data['project']->status] ?? 'secondary' }}">
                                    {{ ucwords(str_replace('_', ' ', $data['project']->status)) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="description-block border-right">
                                        <span class="description-percentage text-success">
                                            <i class="fas fa-check"></i> {{ $data['completed_tasks'] }}
                                        </span>
                                        <span class="description-text">Completed</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="description-block border-right">
                                        <span class="description-percentage text-warning">
                                            <i class="fas fa-spinner"></i> {{ $data['in_progress_tasks'] }}
                                        </span>
                                        <span class="description-text">In Progress</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <div class="description-block border-right">
                                        <span class="description-percentage text-secondary">
                                            <i class="fas fa-clock"></i> {{ $data['pending_tasks'] }}
                                        </span>
                                        <span class="description-text">Pending</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="description-block">
                                        <span class="description-percentage text-info">
                                            <i class="fas fa-tasks"></i> {{ $data['total_tasks'] }}
                                        </span>
                                        <span class="description-text">Total Tasks</span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="progress-group">
                                <span class="progress-text">Overall Progress</span>
                                <span class="float-right"><b>{{ $data['overall_progress'] }}%</b></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: {{ $data['overall_progress'] }}%"></div>
                            </div>
                            <small class="text-muted">
                                Site: {{ $data['project']->site->name ?? 'N/A' }}
                            </small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
