@extends('layouts.app')
@section('page_title', 'Tasks')

@push('styles')
<style>
    .project-card { margin-bottom: 1rem; border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden; }

    .project-header {
        background: linear-gradient(135deg, #1a3c6e 0%, #2c5f9e 100%);
        color: #fff;
        padding: 12px 16px;
        cursor: pointer;
        user-select: none;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .project-header:hover { background: linear-gradient(135deg, #14305a 0%, #254e87 100%); }
    .project-header .toggle-icon { transition: transform .25s; font-size: 13px; }
    .project-header.collapsed .toggle-icon { transform: rotate(-90deg); }

    .project-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto; }
    .project-meta .badge { font-size: 11px; }

    .task-table { margin: 0; }
    .task-table thead th { background: #f4f6f9; font-size: 12px; text-transform: uppercase; letter-spacing: .4px; padding: 8px 10px; border-bottom: 2px solid #dee2e6; }

    /* Master task row */
    .master-row { background: #f8fafd; border-left: 4px solid #2c5f9e; }
    .master-row td { font-weight: 600; padding: 9px 10px; }

    /* Subtask row */
    .sub-row { background: #fff; border-left: 4px solid #e0e8f5; }
    .sub-row td { padding: 7px 10px; font-size: 13px; }
    .sub-indent { padding-left: 28px !important; color: #495057; }
    .sub-connector { color: #adb5bd; font-family: monospace; margin-right: 4px; }

    /* Multi-level nesting styles */
    .sub-row[data-level="2"] { background: #f8f9fa; border-left-color: #d0daea; }
    .sub-row[data-level="3"] { background: #f1f3f5; border-left-color: #c0cadf; }
    .sub-row[data-level="4"] { background: #ebeef2; border-left-color: #b0bad4; }
    .sub-row[data-level]:hover { background: #e3f2fd !important; }

    .progress-col { min-width: 90px; }
    .cost-col { white-space: nowrap; }

    .section-tag { background: #e9ecef; color: #495057; border-radius: 3px; padding: 1px 6px; font-size: 11px; }

    .empty-project { text-align: center; color: #adb5bd; padding: 18px; font-style: italic; }
</style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Tasks</li>
@endsection

@section('content')
<section class="content">
    <div class="container-fluid">

        @if(session('import_errors') && count(session('import_errors')) > 0)
            <div class="alert alert-warning alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>Import row errors ({{ count(session('import_errors')) }}):</strong>
                <ul class="mb-0 mt-1" style="font-size:13px;">
                    @foreach(session('import_errors') as $err)<li>{{ $err }}</li>@endforeach
                </ul>
            </div>
        @endif

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('tasks.index') }}" class="form-inline flex-wrap" style="gap:8px;">
                    <select name="project_id" class="form-control form-control-sm">
                        <option value="">All Projects</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->code }} — {{ $p->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="pending"     {{ request('status') == 'pending'     ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed"   {{ request('status') == 'completed'   ? 'selected' : '' }}>Completed</option>
                        <option value="on_hold"     {{ request('status') == 'on_hold'     ? 'selected' : '' }}>On Hold</option>
                    </select>

                    <select name="priority" class="form-control form-control-sm">
                        <option value="">All Priority</option>
                        <option value="low"      {{ request('priority') == 'low'      ? 'selected' : '' }}>Low</option>
                        <option value="medium"   {{ request('priority') == 'medium'   ? 'selected' : '' }}>Medium</option>
                        <option value="high"     {{ request('priority') == 'high'     ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>

                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search tasks..." value="{{ request('search') }}" style="min-width:160px;">

                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
                    <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-secondary">Reset</a>

                    @can('tasks.create')
                        <a href="{{ route('tasks.create') }}" class="btn btn-sm btn-success ml-auto">
                            <i class="fas fa-plus mr-1"></i>New Task
                        </a>
                        <a href="{{ route('tasks.import') }}" class="btn btn-sm btn-info">
                            <i class="fas fa-file-import mr-1"></i>Import Tasks
                        </a>
                    @endcan
                </form>
            </div>
        </div>

        <!-- Collapse / Expand All -->
        @if($grouped->count() > 1)
        <div class="mb-2 text-right">
            <button class="btn btn-xs btn-outline-secondary" id="expandAll">
                <i class="fas fa-expand-alt mr-1"></i>Expand All
            </button>
            <button class="btn btn-xs btn-outline-secondary ml-1" id="collapseAll">
                <i class="fas fa-compress-alt mr-1"></i>Collapse All
            </button>
        </div>
        @endif

        @forelse($grouped as $project)
        @php
            $totalTasks     = $project->tasks->count();
            $completedCount = $project->tasks->where('status', 'completed')->count();
            $inProgCount    = $project->tasks->where('status', 'in_progress')->count();
            $pendingCount   = $project->tasks->where('status', 'pending')->count();
            $pProgress      = $project->progress ?? 0;
            $statusColors   = ['pending'=>'secondary','in_progress'=>'primary','completed'=>'success','on_hold'=>'warning'];
            $pColor         = $statusColors[$project->status] ?? 'secondary';
            $isFiltered     = request()->hasAny(['project_id','status','priority','search']);
            $collapseId     = 'proj-' . $project->id;
        @endphp

        <div class="project-card">
            <!-- Project Header -->
            <div class="project-header {{ $isFiltered ? '' : '' }}"
                 data-toggle="collapse" data-target="#{{ $collapseId }}" aria-expanded="true">

                <i class="fas fa-chevron-down toggle-icon"></i>

                <div>
                    <span style="font-size:15px; font-weight:700;">{{ $project->name }}</span>
                    <span style="font-size:12px; opacity:.8; margin-left:8px;">{{ $project->code }}</span>
                </div>

                @if($project->sites->count() > 0 || $project->factories->count() > 0)
                    <span style="font-size:12px; opacity:.75;">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        @foreach($project->sites as $site)
                            {{ $site->name }}{{ !$loop->last || $project->factories->count() > 0 ? ', ' : '' }}
                        @endforeach
                        @foreach($project->factories as $factory)
                            {{ $factory->name }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </span>
                @endif

                <!-- Progress bar -->
                <div style="flex:1; max-width:160px; min-width:80px;">
                    <div class="progress" style="height:7px; background:rgba(255,255,255,.25);">
                        <div class="progress-bar bg-{{ $pProgress >= 100 ? 'success' : 'warning' }}"
                             style="width:{{ $pProgress }}%;"></div>
                    </div>
                    <div style="font-size:11px; opacity:.8; margin-top:2px;">{{ $pProgress }}% complete</div>
                </div>

                <div class="project-meta">
                    <span class="badge badge-light text-dark">{{ $totalTasks }} task{{ $totalTasks != 1 ? 's' : '' }}</span>
                    @if($completedCount) <span class="badge badge-success">{{ $completedCount }} done</span> @endif
                    @if($inProgCount)    <span class="badge badge-primary">{{ $inProgCount }} active</span> @endif
                    @if($pendingCount)   <span class="badge badge-secondary">{{ $pendingCount }} pending</span> @endif
                    <span class="badge badge-{{ $pColor }} text-uppercase" style="font-size:10px;">
                        {{ str_replace('_',' ', $project->status) }}
                    </span>
                    <a href="{{ route('projects.show', $project) }}"
                       class="btn btn-xs btn-light text-dark" style="font-size:11px;"
                       onclick="event.stopPropagation();">
                        <i class="fas fa-external-link-alt"></i> Project
                    </a>
                </div>
            </div>

            <!-- Tasks Body -->
            <div id="{{ $collapseId }}" class="collapse show">
                @if($project->tasks->isEmpty())
                    <div class="empty-project">No tasks match the current filters.</div>
                @else
                <div class="table-responsive">
                <table class="table task-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:110px;">Code</th>
                            <th>Task Name</th>
                            <th>Section</th>
                            <th class="cost-col">Quoted Amount</th>
                            <th class="progress-col">Progress</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th class="cost-col">Labor Cost</th>
                            <th class="cost-col">Material Cost</th>
                            <th style="width:90px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($project->tasks as $task)
                        @php
                            $priorityColors = ['low'=>'info','medium'=>'warning','high'=>'danger','critical'=>'dark'];
                            $laborCost    = $task->subtasks_count > 0 ? ($task->aggregated_labor_cost ?? 0)    : ($task->labor_cost ?? 0);
                            $materialCost = $task->subtasks_count > 0 ? ($task->aggregated_material_cost ?? 0) : ($task->material_cost ?? 0);
                        @endphp
                        {{-- Master Task --}}
                        <tr class="master-row">
                            <td>
                                @if($task->subtasks_count > 0)
                                    <i class="fas fa-folder text-warning mr-1"></i>
                                @else
                                    <i class="fas fa-circle text-primary mr-1" style="font-size:8px;vertical-align:middle;"></i>
                                @endif
                                <a href="{{ route('tasks.show', $task) }}" class="text-dark">{{ $task->code }}</a>
                            </td>
                            <td>
                                <a href="{{ route('tasks.show', $task) }}" class="text-dark">{{ $task->name }}</a>
                                @if($task->subtasks_count > 0)
                                    <small class="text-muted ml-1">({{ $task->subtasks_count }} sub)</small>
                                @endif
                            </td>
                            <td>
                                @if($task->section)
                                    <span class="section-tag">{{ $task->section }}</span>
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="cost-col">
                                @php
                                    $displayQuoted = $task->subtasks_count > 0 ? $task->aggregated_quoted_amount : $task->quoted_amount;
                                @endphp
                                @if($displayQuoted)
                                    ₹{{ number_format($displayQuoted, 2) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="progress-col">
                                <div class="progress progress-sm mb-1">
                                    <div class="progress-bar bg-{{ $task->progress >= 100 ? 'success' : 'primary' }}"
                                         style="width:{{ $task->progress }}%"></div>
                                </div>
                                <small class="text-muted">{{ $task->progress }}%</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $priorityColors[$task->priority] ?? 'secondary' }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $statusColors[$task->status] ?? 'secondary' }}">
                                    {{ ucwords(str_replace('_',' ', $task->status)) }}
                                </span>
                            </td>
                            <td class="cost-col">₹{{ number_format($laborCost, 2) }}</td>
                            <td class="cost-col">₹{{ number_format($materialCost, 2) }}</td>
                            <td>
                                <a href="{{ route('tasks.show', $task) }}" class="btn btn-xs btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('tasks.edit')
                                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-xs btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>

                        {{-- Subtasks (recursive) --}}
                        @if($task->relationLoaded('allSubtasks') && $task->allSubtasks->count() > 0)
                            @include('tasks.partials.subtask-row', [
                                'subtasks' => $task->allSubtasks,
                                'level' => 1,
                                'priorityColors' => $priorityColors,
                                'statusColors' => $statusColors
                            ])
                        @endif

                    @endforeach
                    </tbody>

                    {{-- Project totals footer --}}
                    @php
                        // Sum only top-level tasks' aggregated amounts to avoid double counting
                        $topLevelTasks     = $project->tasks->whereNull('parent_id');
                        $projQuotedTotal   = $topLevelTasks->sum('aggregated_quoted_amount');
                        $projLaborTotal    = $topLevelTasks->sum('aggregated_labor_cost');
                        $projMaterialTotal = $topLevelTasks->sum('aggregated_material_cost');
                    @endphp
                    <tfoot>
                        <tr style="background:#f4f6f9; font-weight:600; font-size:12px;">
                            <td colspan="3" class="text-right text-muted">Project Totals:</td>
                            <td class="cost-col">₹{{ number_format($projQuotedTotal, 2) }}</td>
                            <td colspan="3"></td>
                            <td class="cost-col">₹{{ number_format($projLaborTotal, 2) }}</td>
                            <td class="cost-col">₹{{ number_format($projMaterialTotal, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
                @endif
            </div>
        </div>
        @empty
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-tasks fa-3x mb-3 d-block" style="opacity:.3;"></i>
                    No tasks found. <a href="{{ route('tasks.create') }}">Create one</a> or
                    <a href="{{ route('tasks.import') }}">import from Excel</a>.
                </div>
            </div>
        @endforelse

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

    // Toggle icon direction on collapse
    document.querySelectorAll('[data-toggle="collapse"]').forEach(function (header) {
        var target = document.querySelector(header.dataset.target);
        if (!target) return;

        // Sync icon on load
        function syncIcon() {
            if (target.classList.contains('show')) {
                header.classList.remove('collapsed');
            } else {
                header.classList.add('collapsed');
            }
        }
        syncIcon();

        target.addEventListener('shown.bs.collapse',  syncIcon);
        target.addEventListener('hidden.bs.collapse', syncIcon);
    });

    // Expand / Collapse all
    var expandBtn   = document.getElementById('expandAll');
    var collapseBtn = document.getElementById('collapseAll');
    if (expandBtn) {
        expandBtn.addEventListener('click', function () {
            document.querySelectorAll('.collapse').forEach(function (el) {
                $(el).collapse('show');
            });
        });
    }
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            document.querySelectorAll('.collapse').forEach(function (el) {
                $(el).collapse('hide');
            });
        });
    }
</script>
@endpush
