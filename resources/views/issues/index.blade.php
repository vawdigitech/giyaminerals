@extends('layouts.app')
@section('page_title', 'Site Issues')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Site Issues</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $summary['open'] }}</h3>
                        <p>Open Issues</p>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $summary['in_progress'] }}</h3>
                        <p>In Progress</p>
                    </div>
                    <div class="icon"><i class="fas fa-spinner"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $summary['resolved'] }}</h3>
                        <p>Resolved</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-purple">
                    <div class="inner">
                        <h3>{{ $summary['critical_pending'] }}</h3>
                        <p>Critical Pending</p>
                    </div>
                    <div class="icon"><i class="fas fa-fire"></i></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('issues.index') }}" class="row g-3">
                    <div class="col-md-2">
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
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="priority" class="form-control">
                            <option value="">All Priority</option>
                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('issues.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Issues Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Issues</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Site</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Reported By</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issues as $issue)
                            <tr>
                                <td>
                                    <a href="{{ route('issues.show', $issue) }}">{{ $issue->title }}</a>
                                    @if($issue->task)
                                        <br><small class="text-muted">Task: {{ $issue->task->name }}</small>
                                    @endif
                                </td>
                                <td>{{ $issue->site->name ?? '-' }}</td>
                                <td>{{ ucfirst($issue->category) }}</td>
                                <td>
                                    @php
                                        $priorityColors = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'purple'];
                                    @endphp
                                    <span class="badge badge-{{ $priorityColors[$issue->priority] ?? 'secondary' }}">
                                        {{ ucfirst($issue->priority) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $statusColors = ['open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'secondary'];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$issue->status] ?? 'secondary' }}">
                                        {{ ucwords(str_replace('_', ' ', $issue->status)) }}
                                    </span>
                                </td>
                                <td>{{ $issue->reportedBy->name ?? '-' }}</td>
                                <td>{{ $issue->assignedTo->name ?? 'Unassigned' }}</td>
                                <td>{{ $issue->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('issues.show', $issue) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No issues found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $issues->withQueryString()->links() }}
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
