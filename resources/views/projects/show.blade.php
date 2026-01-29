@extends('layouts.app')
@section('page_title', 'Project Details')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
    <li class="breadcrumb-item active">{{ $project->name }}</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Project Summary -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Quoted Amount</span>
                        <span class="info-box-number">${{ number_format($project->quoted_amount, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-calculator"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Actual Cost</span>
                        <span class="info-box-number">${{ number_format($actualAmount, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-{{ $profitLoss >= 0 ? 'success' : 'danger' }}">
                        <i class="fas fa-{{ $profitLoss >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ $profitLoss >= 0 ? 'Profit' : 'Loss' }}</span>
                        <span class="info-box-number">${{ number_format(abs($profitLoss), 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-tasks"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Progress</span>
                        <span class="info-box-number">{{ $completedTasks }}/{{ $totalTasks }} tasks</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Project Info -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Project Information</h3>
                        <div class="card-tools">
                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-5">Code:</dt>
                            <dd class="col-7">{{ $project->code }}</dd>

                            <dt class="col-5">Name:</dt>
                            <dd class="col-7">{{ $project->name }}</dd>

                            <dt class="col-5">Site:</dt>
                            <dd class="col-7">{{ $project->site->name ?? '-' }}</dd>

                            <dt class="col-5">Status:</dt>
                            <dd class="col-7">
                                @php
                                    $statusColors = ['pending' => 'secondary', 'in_progress' => 'primary', 'completed' => 'success', 'on_hold' => 'warning'];
                                @endphp
                                <span class="badge badge-{{ $statusColors[$project->status] ?? 'secondary' }}">
                                    {{ ucwords(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </dd>

                            <dt class="col-5">Start Date:</dt>
                            <dd class="col-7">{{ $project->start_date?->format('M d, Y') ?? '-' }}</dd>

                            <dt class="col-5">End Date:</dt>
                            <dd class="col-7">{{ $project->end_date?->format('M d, Y') ?? '-' }}</dd>

                            <dt class="col-5">Progress:</dt>
                            <dd class="col-7">
                                <div class="progress">
                                    <div class="progress-bar bg-primary" style="width: {{ $project->progress }}%">
                                        {{ $project->progress }}%
                                    </div>
                                </div>
                            </dd>
                        </dl>

                        @if($project->description)
                            <hr>
                            <strong>Description:</strong>
                            <p class="text-muted">{{ $project->description }}</p>
                        @endif
                    </div>
                </div>

                <!-- Cost Breakdown -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Cost Breakdown</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>Labor Cost</td>
                                <td class="text-right">${{ number_format($laborCost, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Material Cost</td>
                                <td class="text-right">${{ number_format($materialCost, 2) }}</td>
                            </tr>
                            <tr class="border-top">
                                <th>Total Actual Cost</th>
                                <th class="text-right">${{ number_format($actualAmount, 2) }}</th>
                            </tr>
                            <tr>
                                <td>Quoted Amount</td>
                                <td class="text-right">${{ number_format($project->quoted_amount, 2) }}</td>
                            </tr>
                            <tr class="border-top {{ $profitLoss >= 0 ? 'text-success' : 'text-danger' }}">
                                <th>{{ $profitLoss >= 0 ? 'Profit' : 'Loss' }}</th>
                                <th class="text-right">${{ number_format(abs($profitLoss), 2) }}</th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tasks -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tasks</h3>
                        <div class="card-tools">
                            <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add Task
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Task Name</th>
                                    <th>Progress</th>
                                    <th>Labor</th>
                                    <th>Material</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($project->tasks as $task)
                                    <tr>
                                        <td><strong>{{ $task->code }}</strong></td>
                                        <td>{{ $task->name }}</td>
                                        <td>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-primary" style="width: {{ $task->progress }}%"></div>
                                            </div>
                                            <small>{{ $task->progress }}%</small>
                                        </td>
                                        <td>${{ number_format($task->labor_cost, 2) }}</td>
                                        <td>${{ number_format($task->material_cost, 2) }}</td>
                                        <td>
                                            @php
                                                $taskStatusColors = ['pending' => 'secondary', 'in_progress' => 'primary', 'completed' => 'success', 'on_hold' => 'warning'];
                                            @endphp
                                            <span class="badge badge-{{ $taskStatusColors[$task->status] ?? 'secondary' }}">
                                                {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @foreach($task->subtasks as $subtask)
                                        <tr class="bg-light">
                                            <td class="pl-4">└ {{ $subtask->code }}</td>
                                            <td>{{ $subtask->name }}</td>
                                            <td>
                                                <div class="progress progress-xs">
                                                    <div class="progress-bar bg-info" style="width: {{ $subtask->progress }}%"></div>
                                                </div>
                                            </td>
                                            <td>${{ number_format($subtask->labor_cost, 2) }}</td>
                                            <td>${{ number_format($subtask->material_cost, 2) }}</td>
                                            <td>
                                                <span class="badge badge-{{ $taskStatusColors[$subtask->status] ?? 'secondary' }}">
                                                    {{ ucwords(str_replace('_', ' ', $subtask->status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No tasks found for this project.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
