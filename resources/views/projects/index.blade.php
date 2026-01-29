@extends('layouts.app')
@section('page_title', 'Projects')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Projects</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total'] }}</h3>
                        <p>Total Projects</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['completed'] }}</h3>
                        <p>Completed</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $stats['in_progress'] }}</h3>
                        <p>In Progress</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $stats['pending'] }}</h3>
                        <p>Pending</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['on_hold'] }}</h3>
                        <p>On Hold</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('projects.index') }}" class="row g-3">
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
                            <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('projects.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Project List</h3>
                        <div class="card-tools">
                            <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Project
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Site</th>
                                    <th>Quoted Amount</th>
                                    <th>Progress</th>
                                    <th>Tasks</th>
                                    <th>Status</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    <tr>
                                        <td>{{ $project->code }}</td>
                                        <td>{{ $project->name }}</td>
                                        <td>{{ $project->site->name ?? '-' }}</td>
                                        <td>${{ number_format($project->quoted_amount, 2) }}</td>
                                        <td>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-primary" style="width: {{ $project->progress }}%"></div>
                                            </div>
                                            <small>{{ $project->progress }}%</small>
                                        </td>
                                        <td>{{ $project->tasks_count }}</td>
                                        <td>
                                            @php
                                                $statusColors = ['pending' => 'secondary', 'in_progress' => 'primary', 'completed' => 'success', 'on_hold' => 'warning'];
                                            @endphp
                                            <span class="badge badge-{{ $statusColors[$project->status] ?? 'secondary' }}">
                                                {{ ucwords(str_replace('_', ' ', $project->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('tasks.index', ['project_id' => $project->id]) }}" class="btn btn-sm btn-success" title="Tasks">
                                                <i class="fas fa-tasks"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No projects found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $projects->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    @if(session('success'))
        toastr.success(@json(session('success')));
    @elseif(session('error'))
        toastr.error(@json(session('error')));
    @endif
</script>
@endpush
