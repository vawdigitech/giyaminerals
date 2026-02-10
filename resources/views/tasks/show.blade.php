@extends('layouts.app')
@section('page_title', 'Task Details')
@push('styles')
<link rel="stylesheet" href="{{ asset('template/plugins/ekko-lightbox/ekko-lightbox.css') }}">
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
    <li class="breadcrumb-item active">{{ $task->name }}</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            </div>
        @endif

        <div class="row">
            <!-- Task Info Card -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body">
                        <h4 class="text-primary">{{ $task->code }}</h4>
                        <h3>{{ $task->name }}</h3>
                        <p class="text-muted">{{ $task->project->name ?? '-' }}</p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Status</b>
                                <span class="float-right badge badge-{{ $task->status === 'completed' ? 'success' : ($task->status === 'in_progress' ? 'primary' : ($task->status === 'on_hold' ? 'warning' : 'secondary')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Priority</b>
                                <span class="float-right badge badge-{{ $task->priority === 'critical' ? 'danger' : ($task->priority === 'high' ? 'warning' : ($task->priority === 'medium' ? 'info' : 'secondary')) }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Section</b> <span class="float-right">{{ $task->section ?? '-' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Parent Task</b> <span class="float-right">{{ $task->parent->name ?? 'None (Top-level)' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Progress</b>
                                <div class="float-right" style="width: 100px;">
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-primary" style="width: {{ $task->progress ?? 0 }}%"></div>
                                    </div>
                                    <small>{{ $task->progress ?? 0 }}%</small>
                                </div>
                            </li>
                        </ul>

                        <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary btn-block">
                            <i class="fas fa-edit"></i> Edit Task
                        </a>
                    </div>
                </div>

                <!-- Dates Card -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Timeline</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item">
                                <b>Start Date</b> <span class="float-right">{{ $task->start_date ? $task->start_date->format('M d, Y') : '-' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Due Date</b> <span class="float-right">{{ $task->due_date ? $task->due_date->format('M d, Y') : '-' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Completed</b> <span class="float-right">{{ $task->completed_date ? $task->completed_date->format('M d, Y') : '-' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Costs Card -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Costs @if($task->hasSubtasks())@endif</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item">
                                <b>Quoted Amount</b> <span class="float-right">${{ number_format($task->quoted_amount ?? 0, 2) }}</span>
                            </li>
                            @if($task->hasSubtasks())
                                <li class="list-group-item">
                                    <b>Labor Cost</b> <span class="float-right">${{ number_format($task->aggregated_labor_cost ?? 0, 2) }}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>Material Cost</b> <span class="float-right">${{ number_format($task->aggregated_material_cost ?? 0, 2) }}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>Actual Amount</b> <span class="float-right text-primary"><strong>${{ number_format($task->aggregated_actual_amount ?? 0, 2) }}</strong></span>
                                </li>
                                <li class="list-group-item">
                                    <b>Total Hours</b> <span class="float-right">{{ number_format($task->total_hours_worked ?? 0, 1) }} hrs</span>
                                </li>
                            @else
                                <li class="list-group-item">
                                    <b>Labor Cost</b> <span class="float-right">${{ number_format($task->labor_cost ?? 0, 2) }}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>Material Cost</b> <span class="float-right">${{ number_format($task->material_cost ?? 0, 2) }}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>Actual Amount</b> <span class="float-right text-primary"><strong>${{ number_format($task->actual_amount ?? 0, 2) }}</strong></span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Description -->
                @if($task->description)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Description</h3>
                    </div>
                    <div class="card-body">
                        {{ $task->description }}
                    </div>
                </div>
                @endif

                <!-- Subtasks -->
                @if($task->subtasks->count() > 0)
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title"><i class="fas fa-tasks mr-1"></i> Subtasks ({{ $task->subtasks->count() }})</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Labor Cost</th>
                                    <th>Material Cost</th>
                                    <th>Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($task->subtasks as $subtask)
                                    <tr>
                                        <td><a href="{{ route('tasks.show', $subtask) }}">{{ $subtask->code }}</a></td>
                                        <td>{{ $subtask->name }}</td>
                                        <td>
                                            <span class="badge badge-{{ $subtask->status === 'completed' ? 'success' : ($subtask->status === 'in_progress' ? 'primary' : 'secondary') }}">
                                                {{ ucfirst(str_replace('_', ' ', $subtask->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-primary" style="width: {{ $subtask->progress ?? 0 }}%"></div>
                                            </div>
                                            <small>{{ $subtask->progress ?? 0 }}%</small>
                                        </td>
                                        <td>${{ number_format($subtask->labor_cost ?? 0, 2) }}</td>
                                        <td>${{ number_format($subtask->material_cost ?? 0, 2) }}</td>
                                        <td><strong>${{ number_format($subtask->actual_amount ?? 0, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-right">Totals:</th>
                                    <th>${{ number_format($task->subtasks->sum('labor_cost'), 2) }}</th>
                                    <th>${{ number_format($task->subtasks->sum('material_cost'), 2) }}</th>
                                    <th>${{ number_format($task->subtasks->sum('actual_amount'), 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Employee Work Summary for Master Task -->
                @php
                    // Aggregate employee work from all subtasks
                    $employeeWorkSummary = collect();
                    foreach($task->subtasks as $subtask) {
                        foreach($subtask->assignments as $assignment) {
                            $empId = $assignment->employee_id;
                            if ($employeeWorkSummary->has($empId)) {
                                $existing = $employeeWorkSummary->get($empId);
                                $existing['hours'] += $assignment->hours_worked ?? 0;
                                $existing['cost'] += ($assignment->hours_worked ?? 0) * ($assignment->hourly_rate_at_time ?? 0);
                                $existing['tasks'][] = $subtask->name;
                                $employeeWorkSummary->put($empId, $existing);
                            } else {
                                $employeeWorkSummary->put($empId, [
                                    'employee' => $assignment->employee,
                                    'hours' => $assignment->hours_worked ?? 0,
                                    'rate' => $assignment->hourly_rate_at_time ?? 0,
                                    'cost' => ($assignment->hours_worked ?? 0) * ($assignment->hourly_rate_at_time ?? 0),
                                    'tasks' => [$subtask->name],
                                ]);
                            }
                        }
                    }
                @endphp

                @if($employeeWorkSummary->count() > 0)
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title"><i class="fas fa-users mr-1"></i> Employee Work Summary</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Subtasks</th>
                                    <th>Total Hours</th>
                                    <th>Rate</th>
                                    <th>Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employeeWorkSummary as $summary)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $summary['employee']) }}">
                                                {{ $summary['employee']->name ?? '-' }}
                                            </a>
                                        </td>
                                        <td>
                                            <small>{{ implode(', ', array_unique($summary['tasks'])) }}</small>
                                        </td>
                                        <td>{{ number_format($summary['hours'], 1) }} hrs</td>
                                        <td>${{ number_format($summary['rate'], 2) }}/hr</td>
                                        <td><strong>${{ number_format($summary['cost'], 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="2" class="text-right">Totals ({{ $employeeWorkSummary->count() }} employees):</th>
                                    <th>{{ number_format($employeeWorkSummary->sum('hours'), 1) }} hrs</th>
                                    <th></th>
                                    <th>${{ number_format($employeeWorkSummary->sum('cost'), 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif
                @endif

                @if(!$task->hasSubtasks())
                <!-- Assigned Employees (only for leaf tasks) -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Assigned Employees</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Assigned</th>
                                    <th>Hours Worked</th>
                                    <th>Rate</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($task->assignments as $assignment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $assignment->employee) }}">
                                                {{ $assignment->employee->name ?? '-' }}
                                            </a>
                                        </td>
                                        <td>{{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('M d, Y') : '-' }}</td>
                                        <td>{{ number_format($assignment->hours_worked ?? 0, 1) }}</td>
                                        <td>${{ number_format($assignment->hourly_rate_at_time ?? 0, 2) }}/hr</td>
                                        <td>
                                            @if($assignment->removed_at)
                                                <span class="badge badge-secondary">Removed</span>
                                            @else
                                                <span class="badge badge-success">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No employees assigned to this task.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Work Sessions (only for leaf tasks) -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Work Sessions</h3>
                    </div>
                    <div class="card-body p-0">
                        @php
                            // Collect all sessions from assignments
                            $allSessions = collect();
                            foreach($task->assignments as $assignment) {
                                foreach($assignment->sessions as $session) {
                                    $allSessions->push([
                                        'date' => $session->date,
                                        'employee' => $assignment->employee,
                                        'hours' => $session->hours ?? 0,
                                        'start_time' => $session->start_time,
                                        'end_time' => $session->end_time,
                                        'status' => $session->status,
                                        'end_reason' => $session->end_reason,
                                    ]);
                                }
                            }
                            $allSessions = $allSessions->sortByDesc('date');
                        @endphp
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Time</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allSessions as $session)
                                    <tr>
                                        <td>{{ $session['date'] ? $session['date']->format('M d, Y') : '-' }}</td>
                                        <td>{{ $session['employee']->name ?? '-' }}</td>
                                        <td>
                                            @if($session['start_time'])
                                                {{ \Carbon\Carbon::parse($session['start_time'])->format('h:i A') }}
                                                @if($session['end_time'])
                                                    - {{ \Carbon\Carbon::parse($session['end_time'])->format('h:i A') }}
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ number_format($session['hours'], 2) }} hrs</td>
                                        <td>
                                            @if($session['status'] === 'active')
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($session['end_reason'] ?? 'Completed') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No work sessions recorded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($allSessions->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="3" class="text-right">Total Hours:</th>
                                    <th>{{ number_format($allSessions->sum('hours'), 2) }} hrs</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
                @endif

                @if(!$task->hasSubtasks())
                <!-- Stock/Material Usage (only for leaf tasks) -->
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-boxes mr-1"></i>
                            Material Usage
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#addMaterialModal">
                                <i class="fas fa-plus"></i> Add Material
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped" id="materialsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Cost</th>
                                    <th>Notes</th>
                                    <th>Date</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="materialsTableBody">
                                @forelse($task->stockUsages as $usage)
                                    <tr data-usage-id="{{ $usage->id }}">
                                        <td>
                                            {{ $usage->product->name ?? '-' }}
                                            <small class="text-muted">({{ $usage->product->unit ?? '' }})</small>
                                        </td>
                                        <td>{{ number_format($usage->quantity, 2) }}</td>
                                        <td>${{ number_format($usage->unit_price ?? 0, 2) }}</td>
                                        <td><strong>${{ number_format($usage->total_cost ?? 0, 2) }}</strong></td>
                                        <td>{{ Str::limit($usage->notes, 30) ?? '-' }}</td>
                                        <td>{{ $usage->used_at ? $usage->used_at->format('M d, Y') : '-' }}</td>
                                        <td>
                                            @if($usage->quantity > 0)
                                            <button type="button" class="btn btn-xs btn-info btn-return-material"
                                                data-usage-id="{{ $usage->id }}"
                                                data-product-name="{{ $usage->product->name ?? 'this material' }}"
                                                data-product-unit="{{ $usage->product->unit ?? '' }}"
                                                data-quantity="{{ $usage->quantity }}"
                                                title="Return material">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-xs btn-danger btn-delete-material"
                                                data-usage-id="{{ $usage->id }}"
                                                data-product-name="{{ $usage->product->name ?? 'this material' }}"
                                                title="Remove material">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="noMaterialsRow">
                                        <td colspan="7" class="text-center text-muted">No materials used yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="3" class="text-right">Total Material Cost:</th>
                                    <th id="totalMaterialCost">${{ number_format($task->material_cost ?? 0, 2) }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Add Material Modal -->
                <div class="modal fade" id="addMaterialModal" tabindex="-1" role="dialog" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title" id="addMaterialModalLabel">
                                    <i class="fas fa-plus-circle mr-1"></i>
                                    Add Material to Task
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form id="addMaterialForm">
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Site: <strong>{{ $task->project->site->name ?? 'No site assigned' }}</strong>
                                        - Only stock available at this site will be shown.
                                    </div>

                                    <div class="form-group">
                                        <label for="stockSelect">Select Product <span class="text-danger">*</span></label>
                                        <select class="form-control" id="stockSelect" name="stock_id" required>
                                            <option value="">-- Loading available stock... --</option>
                                        </select>
                                        <small class="form-text text-muted">
                                            <span id="availableQtyHint"></span>
                                        </small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="materialQty">Quantity <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" min="0.01" class="form-control"
                                                        id="materialQty" name="quantity" required placeholder="0.00">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" id="unitLabel">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="unitPrice">Unit Price <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                    <input type="number" step="0.01" min="0" class="form-control"
                                                        id="unitPrice" name="unit_price" required placeholder="0.00">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Total Cost</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                    <input type="text" class="form-control" id="calculatedTotal" readonly value="0.00">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="materialNotes">Notes (Optional)</label>
                                        <textarea class="form-control" id="materialNotes" name="notes" rows="2"
                                            placeholder="Any notes about this material usage..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning" id="saveMaterialBtn">
                                        <i class="fas fa-save mr-1"></i> Add Material
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Material Confirmation Modal -->
                <div class="modal fade" id="deleteMaterialModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Remove Material
                                </h5>
                                <button type="button" class="close text-white" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to remove <strong id="deleteProductName"></strong> from this task?</p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    The stock will be restored to inventory.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                                    <i class="fas fa-trash mr-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Return Material Modal -->
                <div class="modal fade" id="returnMaterialModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-undo mr-1"></i>
                                    Return Material to Inventory
                                </h5>
                                <button type="button" class="close text-white" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <form id="returnMaterialForm">
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Returning <strong id="returnProductName"></strong>
                                        <br><small>Available to return: <span id="returnAvailableQty"></span> <span id="returnProductUnit"></span></small>
                                    </div>

                                    <input type="hidden" id="returnUsageId">

                                    <div class="form-group">
                                        <label for="returnQuantity">Quantity to Return <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0.01" class="form-control"
                                                id="returnQuantity" name="quantity_to_return" required placeholder="0.00">
                                            <div class="input-group-append">
                                                <span class="input-group-text" id="returnUnitLabel">-</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="returnDestination">Return To <span class="text-danger">*</span></label>
                                        <select class="form-control" id="returnDestination" name="destination" required>
                                            <option value="">-- Loading destinations... --</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="returnNotes">Notes (Optional)</label>
                                        <textarea class="form-control" id="returnNotes" name="notes" rows="2"
                                            placeholder="Reason for return..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-info" id="confirmReturnBtn">
                                        <i class="fas fa-undo mr-1"></i> Return Material
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Progress Photos -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-camera mr-1"></i>
                            Progress Photos ({{ $task->progressPhotos->count() }})
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($task->progressPhotos->count() > 0)
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                @foreach($task->progressPhotos as $photo)
                                    @php
                                        $imgSrc = str_starts_with($photo->photo, 'data:') ? $photo->photo : 'data:image/jpeg;base64,' . $photo->photo;
                                        $title = ($photo->captured_date ? $photo->captured_date->format('M d, Y') : '') . ' - ' . ($photo->employee->name ?? 'Unknown');
                                    @endphp
                                    <a href="{{ $imgSrc }}" data-toggle="lightbox" data-gallery="progress-photos" data-title="{{ $title }}">
                                        <img src="{{ $imgSrc }}" class="img-thumbnail" alt="Progress Photo" style="height: 70px; width: 70px; object-fit: cover;">
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-muted mb-0">No progress photos captured yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('template/plugins/ekko-lightbox/ekko-lightbox.min.js') }}"></script>
<script>
    $(function () {
        // Lightbox for photos
        $(document).on('click', '[data-toggle="lightbox"]', function(event) {
            event.preventDefault();
            $(this).ekkoLightbox({
                alwaysShowClose: true
            });
        });

        @if(!$task->hasSubtasks())
        // Material Management (only for leaf tasks)
        const taskId = {{ $task->id }};
        let availableStocks = [];

        // Load available stock when modal opens
        $('#addMaterialModal').on('show.bs.modal', function () {
            loadAvailableStock();
        });

        function loadAvailableStock() {
            const $select = $('#stockSelect');
            $select.html('<option value="">-- Loading... --</option>');

            $.ajax({
                url: `{{ url('tasks') }}/${taskId}/materials/available-stock`,
                method: 'GET',
                success: function(response) {
                    availableStocks = response.data || [];
                    $select.empty();

                    if (availableStocks.length === 0) {
                        $select.html('<option value="">-- No stock available at this site --</option>');
                        return;
                    }

                    $select.append('<option value="">-- Select a product --</option>');
                    availableStocks.forEach(function(stock) {
                        $select.append(`<option value="${stock.id}"
                            data-available="${stock.available_qty}"
                            data-unit="${stock.product_unit}"
                            data-unit-price="${stock.unit_price || 0}"
                            data-name="${stock.product_name}">
                            ${stock.product_name} (${stock.category}) - Available: ${stock.available_qty} ${stock.product_unit}
                        </option>`);
                    });
                },
                error: function(xhr) {
                    console.error('Error loading stock:', xhr);
                    $select.html('<option value="">-- Error loading stock --</option>');
                }
            });
        }

        // Update hints when product is selected
        $('#stockSelect').on('change', function() {
            const $selected = $(this).find(':selected');
            const available = $selected.data('available') || 0;
            const unit = $selected.data('unit') || '';
            const unitPrice = $selected.data('unit-price') || 0;

            $('#unitLabel').text(unit || '-');
            $('#availableQtyHint').html(
                available > 0
                    ? `<i class="fas fa-check text-success"></i> Available: <strong>${available}</strong> ${unit}`
                    : ''
            );
            $('#materialQty').attr('max', available);

            // Auto-fill unit price from product
            if (unitPrice > 0) {
                $('#unitPrice').val(parseFloat(unitPrice).toFixed(2));
                calculateTotal();
            } else {
                $('#unitPrice').val('');
            }
        });

        // Calculate total cost
        function calculateTotal() {
            const qty = parseFloat($('#materialQty').val()) || 0;
            const price = parseFloat($('#unitPrice').val()) || 0;
            const total = (qty * price).toFixed(2);
            $('#calculatedTotal').val(total);
        }

        $('#materialQty, #unitPrice').on('input', calculateTotal);

        // Add material form submission
        $('#addMaterialForm').on('submit', function(e) {
            e.preventDefault();

            const $btn = $('#saveMaterialBtn');
            const originalText = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: `{{ url('tasks') }}/${taskId}/materials`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    stock_id: $('#stockSelect').val(),
                    quantity: $('#materialQty').val(),
                    unit_price: $('#unitPrice').val(),
                    notes: $('#materialNotes').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Add row to table
                        addMaterialRow(response.data);

                        // Update total
                        refreshTotalCost();

                        // Reset form and close modal
                        $('#addMaterialForm')[0].reset();
                        $('#calculatedTotal').val('0.00');
                        $('#addMaterialModal').modal('hide');

                        // Show success toast
                        showToast('success', response.message);
                    } else {
                        showToast('error', response.message || 'Failed to add material');
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || 'An error occurred';
                    showToast('error', msg);
                },
                complete: function() {
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });

        function addMaterialRow(data) {
            // Remove "no materials" row if exists
            $('#noMaterialsRow').remove();

            const row = `
                <tr data-usage-id="${data.id}">
                    <td>
                        ${data.product_name}
                        <small class="text-muted">(${data.product_unit})</small>
                    </td>
                    <td>${parseFloat(data.quantity).toFixed(2)}</td>
                    <td>$${parseFloat(data.unit_price).toFixed(2)}</td>
                    <td><strong>$${parseFloat(data.total_cost).toFixed(2)}</strong></td>
                    <td>${data.notes || '-'}</td>
                    <td>${data.used_at}</td>
                    <td>
                        <button type="button" class="btn btn-xs btn-info btn-return-material"
                            data-usage-id="${data.id}"
                            data-product-name="${data.product_name}"
                            data-product-unit="${data.product_unit}"
                            data-quantity="${data.quantity}"
                            title="Return material">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button type="button" class="btn btn-xs btn-danger btn-delete-material"
                            data-usage-id="${data.id}"
                            data-product-name="${data.product_name}"
                            title="Remove material">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#materialsTableBody').append(row);
        }

        // Delete material - show modal
        let deleteUsageId = null;
        let $deleteBtn = null;

        $(document).on('click', '.btn-delete-material', function() {
            deleteUsageId = $(this).data('usage-id');
            const productName = $(this).data('product-name');
            $deleteBtn = $(this);

            $('#deleteProductName').text(productName);
            $('#deleteMaterialModal').modal('show');
        });

        // Confirm delete
        $('#confirmDeleteBtn').on('click', function() {
            if (!deleteUsageId) return;

            const $btn = $(this);
            $btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Removing...').prop('disabled', true);

            $.ajax({
                url: `{{ url('tasks') }}/${taskId}/materials/${deleteUsageId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#deleteMaterialModal').modal('hide');

                        // Remove row
                        $(`tr[data-usage-id="${deleteUsageId}"]`).fadeOut(300, function() {
                            $(this).remove();

                            // Check if table is empty
                            if ($('#materialsTableBody tr').length === 0) {
                                $('#materialsTableBody').html(
                                    '<tr id="noMaterialsRow"><td colspan="7" class="text-center text-muted">No materials used yet.</td></tr>'
                                );
                            }

                            // Update total
                            refreshTotalCost();
                        });

                        showToast('success', response.message);
                    } else {
                        showToast('error', response.message || 'Failed to remove material');
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || 'An error occurred';
                    showToast('error', msg);
                },
                complete: function() {
                    $btn.html('<i class="fas fa-trash mr-1"></i> Remove').prop('disabled', false);
                    deleteUsageId = null;
                    $deleteBtn = null;
                }
            });
        });

        // Reset modal state when hidden
        $('#deleteMaterialModal').on('hidden.bs.modal', function() {
            $('#confirmDeleteBtn').html('<i class="fas fa-trash mr-1"></i> Remove').prop('disabled', false);
        });

        function refreshTotalCost() {
            $.ajax({
                url: `{{ url('tasks') }}/${taskId}/materials`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const total = parseFloat(response.data.total_material_cost) || 0;
                        $('#totalMaterialCost').text('$' + total.toFixed(2));

                        // Also update the cost card in sidebar
                        $('.card-success .list-group-item b:contains("Material Cost")')
                            .siblings('span')
                            .text('$' + total.toFixed(2));
                    }
                }
            });
        }

        // Return Material functionality
        let returnUsageId = null;
        let returnDestinations = null;

        // Load return destinations when modal opens
        $('#returnMaterialModal').on('show.bs.modal', function() {
            if (!returnDestinations) {
                loadReturnDestinations();
            }
        });

        function loadReturnDestinations() {
            const $select = $('#returnDestination');
            $select.html('<option value="">-- Loading... --</option>');

            $.ajax({
                url: '{{ route("materials.return-destinations") }}',
                method: 'GET',
                success: function(response) {
                    returnDestinations = response.data;
                    $select.empty();
                    $select.append('<option value="">-- Select destination --</option>');

                    if (returnDestinations.warehouses && returnDestinations.warehouses.length > 0) {
                        const warehouseGroup = $('<optgroup label="Warehouses"></optgroup>');
                        returnDestinations.warehouses.forEach(function(w) {
                            warehouseGroup.append(`<option value="warehouse:${w.id}">${w.name}</option>`);
                        });
                        $select.append(warehouseGroup);
                    }

                    if (returnDestinations.sites && returnDestinations.sites.length > 0) {
                        const siteGroup = $('<optgroup label="Sites"></optgroup>');
                        returnDestinations.sites.forEach(function(s) {
                            siteGroup.append(`<option value="site:${s.id}">${s.name}</option>`);
                        });
                        $select.append(siteGroup);
                    }
                },
                error: function() {
                    $select.html('<option value="">-- Error loading destinations --</option>');
                }
            });
        }

        // Show return modal
        $(document).on('click', '.btn-return-material', function() {
            returnUsageId = $(this).data('usage-id');
            const productName = $(this).data('product-name');
            const productUnit = $(this).data('product-unit');
            const quantity = $(this).data('quantity');

            $('#returnUsageId').val(returnUsageId);
            $('#returnProductName').text(productName);
            $('#returnProductUnit').text(productUnit);
            $('#returnAvailableQty').text(parseFloat(quantity).toFixed(2));
            $('#returnUnitLabel').text(productUnit || '-');
            $('#returnQuantity').attr('max', quantity).val('');
            $('#returnNotes').val('');

            $('#returnMaterialModal').modal('show');
        });

        // Submit return form
        $('#returnMaterialForm').on('submit', function(e) {
            e.preventDefault();

            const usageId = $('#returnUsageId').val();
            const destination = $('#returnDestination').val();
            if (!destination) {
                showToast('error', 'Please select a destination');
                return;
            }

            const [destType, destId] = destination.split(':');
            const $btn = $('#confirmReturnBtn');
            const originalText = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

            $.ajax({
                url: `{{ url('tasks') }}/${taskId}/materials/${usageId}/return`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    quantity_to_return: $('#returnQuantity').val(),
                    destination_type: destType,
                    destination_id: destId,
                    notes: $('#returnNotes').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#returnMaterialModal').modal('hide');

                        if (response.data.deleted) {
                            // Remove the row entirely
                            $(`tr[data-usage-id="${usageId}"]`).fadeOut(300, function() {
                                $(this).remove();
                                if ($('#materialsTableBody tr').length === 0) {
                                    $('#materialsTableBody').html(
                                        '<tr id="noMaterialsRow"><td colspan="7" class="text-center text-muted">No materials used yet.</td></tr>'
                                    );
                                }
                            });
                        } else {
                            // Update the quantity in the row
                            const $row = $(`tr[data-usage-id="${usageId}"]`);
                            const newQty = parseFloat(response.data.remaining_quantity);
                            $row.find('td:eq(1)').text(newQty.toFixed(2));
                            // Update the return button's data-quantity
                            $row.find('.btn-return-material').data('quantity', newQty);
                        }

                        refreshTotalCost();
                        showToast('success', response.message);
                    } else {
                        showToast('error', response.message || 'Failed to return material');
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || 'An error occurred';
                    showToast('error', msg);
                },
                complete: function() {
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });

        // Reset return modal state when hidden
        $('#returnMaterialModal').on('hidden.bs.modal', function() {
            returnUsageId = null;
            $('#returnMaterialForm')[0].reset();
        });

        function showToast(type, message) {
            // Using AdminLTE's toastr if available, otherwise alert
            if (typeof toastr !== 'undefined') {
                toastr[type](message);
            } else {
                // Simple fallback notification
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const $alert = $(`
                    <div class="alert ${alertClass} alert-dismissible fade show"
                         style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        ${message}
                    </div>
                `);
                $('body').append($alert);
                setTimeout(() => $alert.alert('close'), 3000);
            }
        }
        @endif
    });
</script>
@endpush
