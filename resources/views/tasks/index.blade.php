@extends('layouts.app')
@section('page_title', 'Tasks')

@push('styles')
<style>
    /* Master Task Styling */
    .master-task-row {
        background-color: #f8f9fa;
        border-left: 4px solid #007bff;
    }
    .master-task-row td:first-child {
        font-weight: 600;
    }

    /* Subtask Styling */
    .subtask-row {
        background-color: #fff;
        border-left: 4px solid #e9ecef;
    }
    .subtask-row td {
        padding-left: 1.5rem !important;
    }
    .subtask-row td:first-child {
        padding-left: 2rem !important;
    }

    /* Subtask connector styling */
    .subtask-connector {
        color: #6c757d;
        font-family: monospace;
        margin-right: 5px;
    }

    /* Visual separation between task groups */
    .master-task-row td {
        border-top: 2px solid #dee2e6;
    }
</style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Tasks</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('tasks.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <select name="project_id" class="form-control">
                            <option value="">All Projects</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->code }} - {{ $project->name }}
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
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Reset</a>
                        <a href="{{ route('tasks.create') }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> New Task
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tasks Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tasks List</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Code</th>
                            <th>Task Name</th>
                            <th>Project</th>
                            <th>Section</th>
                            <th>Progress</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Labor Cost</th>
                            <th>Material Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $task)
                            @if(!$task->parent_id)
                            {{-- Master Task Row --}}
                            <tr class="master-task-row">
                                <td class="font-weight-bold">
                                    @if($task->subtasks_count > 0)
                                        <i class="fas fa-folder-open text-warning mr-1"></i>
                                    @else
                                        <i class="fas fa-tasks text-secondary mr-1"></i>
                                    @endif
                                    {{ $task->code }}
                                </td>
                                <td class="font-weight-bold">{{ $task->name }}</td>
                            @else
                            {{-- Subtask Row --}}
                            <tr class="subtask-row">
                                <td class="pl-4">
                                    <span class="subtask-connector">└─</span>
                                    <span class="text-muted">{{ $task->code }}</span>
                                </td>
                                <td class="pl-3">
                                    <small>{{ $task->name }}</small>
                                </td>
                            @endif
                                <td>
                                    <a href="{{ route('projects.show', $task->project) }}">
                                        {{ $task->project->code ?? '-' }}
                                    </a>
                                </td>
                                <td>{{ $task->section ?? '-' }}</td>
                                <td>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-primary" style="width: {{ $task->progress }}%"></div>
                                    </div>
                                    <small>{{ $task->progress }}%</small>
                                </td>
                                <td>
                                    @php
                                        $priorityColors = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'purple'];
                                    @endphp
                                    <span class="badge badge-{{ $priorityColors[$task->priority] ?? 'secondary' }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $statusColors = ['pending' => 'secondary', 'in_progress' => 'primary', 'completed' => 'success', 'on_hold' => 'warning'];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$task->status] ?? 'secondary' }}">
                                        {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </td>
                                <td>${{ number_format($task->labor_cost, 2) }}</td>
                                <td>${{ number_format($task->material_cost, 2) }}</td>
                                <td>
                                    <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">No tasks found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $tasks->withQueryString()->links() }}
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
