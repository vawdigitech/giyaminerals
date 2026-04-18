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
                        <span class="info-box-text">Estimated (Project)</span>
                        <span class="info-box-number">₹{{ number_format($project->quoted_amount, 2) }}</span>
                        <span class="info-box-text text-muted" style="font-size:11px;">Tasks Est: ₹{{ number_format($quotedTasks, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-calculator"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Actual Cost</span>
                        <span class="info-box-number">₹{{ number_format($actualAmount, 2) }}</span>
                        <div class="progress" style="height:5px;margin-top:4px;">
                            <div class="progress-bar bg-{{ $burnPct >= 100 ? 'danger' : ($burnPct >= 80 ? 'warning' : 'success') }}"
                                 style="width:{{ $burnPct }}%"></div>
                        </div>
                        <span class="info-box-text" style="font-size:11px;">{{ $burnPct }}% of estimate used</span>
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
                        <span class="info-box-number text-{{ $profitLoss >= 0 ? 'success' : 'danger' }}">
                            {{ $profitLoss >= 0 ? '+' : '-' }}₹{{ number_format(abs($profitLoss), 2) }}
                        </span>
                        <span class="info-box-text" style="font-size:11px;">Margin: {{ $margin }}%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-tasks"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Project Stages</span>
                        <span class="info-box-number">{{ $completedTasks }}/{{ $totalTasks }} done</span>
                        <span class="info-box-text" style="font-size:11px;">
                            <span class="badge badge-primary">{{ $inProgressTasks }} active</span>
                            <span class="badge badge-secondary">{{ $pendingTasks }} pending</span>
                            @if($onHoldTasks) <span class="badge badge-warning">{{ $onHoldTasks }} on hold</span>@endif
                        </span>
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

                            <dt class="col-5">Locations:</dt>
                            <dd class="col-7">
                                @if($project->sites->count() > 0 || $project->factories->count() > 0)
                                    @foreach($project->sites as $site)
                                        <span class="badge badge-info">{{ $site->name }} (Site)</span>
                                    @endforeach
                                    @foreach($project->factories as $factory)
                                        <span class="badge badge-secondary">{{ $factory->name }} (Factory)</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">No locations assigned</span>
                                @endif
                            </dd>

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
                                <td class="text-right">₹{{ number_format($laborCost, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Material Cost</td>
                                <td class="text-right">₹{{ number_format($materialCost, 2) }}</td>
                            </tr>
                            <tr class="border-top">
                                <th>Total Actual Cost</th>
                                <th class="text-right">₹{{ number_format($actualAmount, 2) }}</th>
                            </tr>
                            <tr>
                                <td>Quoted Amount</td>
                                <td class="text-right">₹{{ number_format($project->quoted_amount, 2) }}</td>
                            </tr>
                            <tr class="border-top {{ $profitLoss >= 0 ? 'text-success' : 'text-danger' }}">
                                <th>{{ $profitLoss >= 0 ? 'Profit' : 'Loss' }}</th>
                                <th class="text-right">
                                    {{ $profitLoss >= 0 ? '+' : '-' }}₹{{ number_format(abs($profitLoss), 2) }}
                                    ({{ $margin }}%)
                                </th>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Project Stages -->
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-layer-group mr-1"></i> Project Stages</h3>
                    </div>
                    <div class="card-body p-2">
                        @php
                            $stageBar = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                        @endphp
                        <div class="row text-center mb-2">
                            <div class="col-6 col-sm-3">
                                <div class="font-weight-bold text-secondary">{{ $pendingTasks }}</div>
                                <small class="text-muted">Pending</small>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="font-weight-bold text-primary">{{ $inProgressTasks }}</div>
                                <small class="text-muted">In Progress</small>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="font-weight-bold text-success">{{ $completedTasks }}</div>
                                <small class="text-muted">Completed</small>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="font-weight-bold text-warning">{{ $onHoldTasks }}</div>
                                <small class="text-muted">On Hold</small>
                            </div>
                        </div>
                        <div class="progress" style="height:12px;">
                            <div class="progress-bar bg-success" style="width:{{ $stageBar }}%"
                                 title="{{ $completedTasks }} of {{ $totalTasks }} tasks completed">
                                {{ $stageBar }}%
                            </div>
                        </div>
                        <small class="text-muted">{{ $completedTasks }} of {{ $totalTasks }} tasks completed</small>
                    </div>
                </div>
            </div>

            <!-- Tasks -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tasks</h3>
                        <div class="card-tools">
                            @can('tasks.create')
                            <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add Task
                            </a>
                            <a href="{{ route('tasks.import') }}" class="btn btn-sm btn-success ml-1">
                                <i class="fas fa-file-import"></i> Import Tasks
                            </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm">
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
                                    @php $taskStatusColors = ['pending' => 'secondary', 'in_progress' => 'primary', 'completed' => 'success', 'on_hold' => 'warning']; @endphp
                                    <tr>
                                        <td><a href="{{ route('tasks.show', $task) }}"><strong>{{ $task->code }}</strong></a></td>
                                        <td>{{ $task->name }}</td>
                                        <td style="min-width:90px;">
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-primary" style="width: {{ $task->progress }}%"></div>
                                            </div>
                                            <small>{{ $task->progress }}%</small>
                                        </td>
                                        <td>₹{{ number_format($task->labor_cost, 2) }}</td>
                                        <td>₹{{ number_format($task->material_cost, 2) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $taskStatusColors[$task->status] ?? 'secondary' }}">
                                                {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @foreach($task->subtasks as $subtask)
                                        <tr class="bg-light">
                                            <td class="pl-4 text-muted">└ {{ $subtask->code }}</td>
                                            <td>{{ $subtask->name }}</td>
                                            <td>
                                                <div class="progress progress-xs">
                                                    <div class="progress-bar bg-info" style="width: {{ $subtask->progress }}%"></div>
                                                </div>
                                                <small>{{ $subtask->progress }}%</small>
                                            </td>
                                            <td>₹{{ number_format($subtask->labor_cost, 2) }}</td>
                                            <td>₹{{ number_format($subtask->material_cost, 2) }}</td>
                                            <td>
                                                <span class="badge badge-{{ $taskStatusColors[$subtask->status] ?? 'secondary' }}">
                                                    {{ ucwords(str_replace('_', ' ', $subtask->status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No tasks found for this project.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Estimate Analysis Table -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="card-title"><i class="fas fa-balance-scale mr-1"></i> Estimate vs Actual Analysis</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Task</th>
                                    <th class="text-right">Estimated</th>
                                    <th class="text-right">Actual</th>
                                    <th class="text-right">Diff</th>
                                    <th class="text-right">Variance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalEstimated = 0;
                                    $totalActual    = 0;
                                @endphp
                                @forelse($project->tasks as $t)
                                    @php
                                        $tEst    = $t->hasSubtasks() ? ($t->aggregated_quoted_amount ?? 0) : ($t->quoted_amount ?? 0);
                                        $tAct    = $t->hasSubtasks() ? ($t->aggregated_actual_amount ?? 0) : ($t->actual_amount ?? 0);
                                        $tDiff   = $tEst - $tAct;
                                        $tVar    = $tEst > 0 ? round(($tDiff / $tEst) * 100, 1) : null;
                                        $totalEstimated += $tEst;
                                        $totalActual    += $tAct;
                                    @endphp
                                    <tr>
                                        <td><a href="{{ route('tasks.show', $t) }}">{{ $t->code }}</a></td>
                                        <td>{{ $t->name }}</td>
                                        <td class="text-right">₹{{ number_format($tEst, 2) }}</td>
                                        <td class="text-right">₹{{ number_format($tAct, 2) }}</td>
                                        <td class="text-right font-weight-bold text-{{ $tDiff >= 0 ? 'success' : 'danger' }}">
                                            {{ $tDiff >= 0 ? '+' : '' }}₹{{ number_format($tDiff, 2) }}
                                        </td>
                                        <td class="text-right">
                                            @if($tVar !== null)
                                                <span class="badge badge-{{ $tVar >= 0 ? 'success' : 'danger' }}">
                                                    {{ $tVar >= 0 ? '+' : '' }}{{ $tVar }}%
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @foreach($t->subtasks as $st)
                                        @php
                                            $stEst  = $st->quoted_amount ?? 0;
                                            $stAct  = $st->actual_amount ?? 0;
                                            $stDiff = $stEst - $stAct;
                                            $stVar  = $stEst > 0 ? round(($stDiff / $stEst) * 100, 1) : null;
                                        @endphp
                                        <tr class="bg-light text-muted">
                                            <td class="pl-4">└ {{ $st->code }}</td>
                                            <td><small>{{ $st->name }}</small></td>
                                            <td class="text-right"><small>₹{{ number_format($stEst, 2) }}</small></td>
                                            <td class="text-right"><small>₹{{ number_format($stAct, 2) }}</small></td>
                                            <td class="text-right text-{{ $stDiff >= 0 ? 'success' : 'danger' }}">
                                                <small>{{ $stDiff >= 0 ? '+' : '' }}₹{{ number_format($stDiff, 2) }}</small>
                                            </td>
                                            <td class="text-right">
                                                @if($stVar !== null)
                                                    <span class="badge badge-{{ $stVar >= 0 ? 'success' : 'danger' }}" style="font-size:10px;">
                                                        {{ $stVar >= 0 ? '+' : '' }}{{ $stVar }}%
                                                    </span>
                                                @else
                                                    <small class="text-muted">—</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No tasks yet.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="font-weight-bold">
                                @php $totalDiff = $totalEstimated - $totalActual; @endphp
                                <tr class="bg-light">
                                    <th colspan="2">Totals (All Tasks)</th>
                                    <th class="text-right">₹{{ number_format($totalEstimated, 2) }}</th>
                                    <th class="text-right">₹{{ number_format($totalActual, 2) }}</th>
                                    <th class="text-right text-{{ $totalDiff >= 0 ? 'success' : 'danger' }}">
                                        {{ $totalDiff >= 0 ? '+' : '' }}₹{{ number_format($totalDiff, 2) }}
                                    </th>
                                    <th></th>
                                </tr>
                                <tr class="{{ $profitLoss >= 0 ? 'table-success' : 'table-danger' }}">
                                    <th colspan="2">Project P&L (vs Project Estimate)</th>
                                    <th class="text-right">₹{{ number_format($project->quoted_amount, 2) }}</th>
                                    <th class="text-right">₹{{ number_format($actualAmount, 2) }}</th>
                                    <th class="text-right">
                                        {{ $profitLoss >= 0 ? '+' : '' }}₹{{ number_format($profitLoss, 2) }}
                                    </th>
                                    <th class="text-right">{{ $margin }}%</th>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
