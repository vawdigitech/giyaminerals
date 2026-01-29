@extends('layouts.app')
@section('page_title', 'Edit Task')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
    <li class="breadcrumb-item active">Edit Task</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Edit Task: {{ $task->name }}</h3>
                    </div>
                    <form method="POST" action="{{ route('tasks.update', $task) }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="code">Task Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                                            id="code" name="code" value="{{ old('code', $task->code) }}"
                                            placeholder="e.g., 1.1" required>
                                        @error('code')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="name">Task Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $task->name) }}" required>
                                        @error('name')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="project_id">Project <span class="text-danger">*</span></label>
                                        <select class="form-control @error('project_id') is-invalid @enderror"
                                            id="project_id" name="project_id" required>
                                            <option value="">-- Select Project --</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}"
                                                    {{ old('project_id', $task->project_id) == $project->id ? 'selected' : '' }}>
                                                    {{ $project->code }} - {{ $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('project_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="parent_id">Parent Task (Optional)</label>
                                        <select class="form-control @error('parent_id') is-invalid @enderror"
                                            id="parent_id" name="parent_id">
                                            <option value="">-- No Parent (Top-level Task) --</option>
                                            @foreach($parentTasks as $parentTask)
                                                <option value="{{ $parentTask->id }}" {{ old('parent_id', $task->parent_id) == $parentTask->id ? 'selected' : '' }}>
                                                    {{ $parentTask->code }} - {{ $parentTask->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('parent_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    id="description" name="description" rows="3">{{ old('description', $task->description) }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="section">Section</label>
                                        <input type="text" class="form-control @error('section') is-invalid @enderror"
                                            id="section" name="section" value="{{ old('section', $task->section) }}"
                                            placeholder="e.g., Electrical, Plumbing">
                                        @error('section')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="quoted_amount">Quoted Amount</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" step="0.01" min="0"
                                                class="form-control @error('quoted_amount') is-invalid @enderror"
                                                id="quoted_amount" name="quoted_amount"
                                                value="{{ old('quoted_amount', $task->quoted_amount) }}" placeholder="0.00">
                                            @error('quoted_amount')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="priority">Priority <span class="text-danger">*</span></label>
                                        <select class="form-control @error('priority') is-invalid @enderror"
                                            id="priority" name="priority" required>
                                            <option value="low" {{ old('priority', $task->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                            <option value="medium" {{ old('priority', $task->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="high" {{ old('priority', $task->priority) == 'high' ? 'selected' : '' }}>High</option>
                                            <option value="critical" {{ old('priority', $task->priority) == 'critical' ? 'selected' : '' }}>Critical</option>
                                        </select>
                                        @error('priority')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="status">Status <span class="text-danger">*</span></label>
                                        <select class="form-control @error('status') is-invalid @enderror"
                                            id="status" name="status" required>
                                            <option value="pending" {{ old('status', $task->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                            <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="on_hold" {{ old('status', $task->status) == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                                        </select>
                                        @error('status')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="progress">Progress (%)</label>
                                        <input type="number" class="form-control @error('progress') is-invalid @enderror"
                                            id="progress" name="progress" value="{{ old('progress', $task->progress ?? 0) }}"
                                            min="0" max="100">
                                        @error('progress')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                            id="start_date" name="start_date"
                                            value="{{ old('start_date', $task->start_date?->format('Y-m-d')) }}">
                                        @error('start_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="due_date">Due Date</label>
                                        <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                            id="due_date" name="due_date"
                                            value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}">
                                        @error('due_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="completed_date">Completed Date</label>
                                        <input type="date" class="form-control @error('completed_date') is-invalid @enderror"
                                            id="completed_date" name="completed_date"
                                            value="{{ old('completed_date', $task->completed_date?->format('Y-m-d')) }}">
                                        @error('completed_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Update Task</button>
                            <a href="{{ route('tasks.show', $task) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Material Usage Sidebar -->
            <div class="col-md-4">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-boxes mr-1"></i>
                            Material Usage
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#addMaterialModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped" id="materialsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Cost</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="materialsTableBody">
                                @forelse($task->stockUsages as $usage)
                                    <tr data-usage-id="{{ $usage->id }}">
                                        <td>
                                            {{ $usage->product->name ?? '-' }}
                                            <br><small class="text-muted">${{ number_format($usage->unit_price ?? 0, 2) }}/{{ $usage->product->unit ?? 'unit' }}</small>
                                        </td>
                                        <td>{{ number_format($usage->quantity, 2) }}</td>
                                        <td><strong>${{ number_format($usage->total_cost ?? 0, 2) }}</strong></td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-danger btn-delete-material"
                                                data-usage-id="{{ $usage->id }}"
                                                data-product-name="{{ $usage->product->name ?? 'this material' }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="noMaterialsRow">
                                        <td colspan="4" class="text-center text-muted">No materials added.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="2" class="text-right">Total:</th>
                                    <th id="totalMaterialCost">${{ number_format($task->material_cost ?? 0, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer text-muted small">
                        <i class="fas fa-info-circle mr-1"></i>
                        Site: {{ $task->project->site->name ?? 'Not assigned' }}
                    </div>
                </div>

                <!-- Cost Summary Card -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> Cost Summary</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>Labor Cost</td>
                                <td class="text-right">${{ number_format($task->labor_cost ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Material Cost</td>
                                <td class="text-right" id="summaryCost">${{ number_format($task->material_cost ?? 0, 2) }}</td>
                            </tr>
                            <tr class="bg-light">
                                <th>Actual Amount</th>
                                <th class="text-right" id="summaryTotal">${{ number_format($task->actual_amount ?? 0, 2) }}</th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle mr-1"></i>
                    Add Material
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addMaterialForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="stockSelect">Product <span class="text-danger">*</span></label>
                        <select class="form-control" id="stockSelect" name="stock_id" required>
                            <option value="">-- Loading... --</option>
                        </select>
                        <small class="form-text text-muted" id="availableQtyHint"></small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
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
                    </div>

                    <div class="form-group">
                        <label>Calculated Total: <strong id="calculatedTotal">$0.00</strong></label>
                    </div>

                    <div class="form-group">
                        <label for="materialNotes">Notes</label>
                        <input type="text" class="form-control" id="materialNotes" name="notes" placeholder="Optional notes...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="saveMaterialBtn">
                        <i class="fas fa-save mr-1"></i> Add
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectSelect = document.getElementById('project_id');
    const parentSelect = document.getElementById('parent_id');
    const currentParentId = '{{ $task->parent_id }}';
    const currentTaskId = '{{ $task->id }}';

    projectSelect.addEventListener('change', function() {
        const projectId = this.value;

        // Clear parent tasks
        parentSelect.innerHTML = '<option value="">-- No Parent (Top-level Task) --</option>';

        if (!projectId) return;

        // Fetch tasks for selected project
        fetch(`{{ url('tasks-by-project') }}/${projectId}`)
            .then(response => response.json())
            .then(tasks => {
                tasks.forEach(task => {
                    // Don't show the current task as a parent option
                    if (task.id != currentTaskId) {
                        const option = document.createElement('option');
                        option.value = task.id;
                        option.textContent = `${task.code} - ${task.name}`;
                        if (task.id == currentParentId) {
                            option.selected = true;
                        }
                        parentSelect.appendChild(option);
                    }
                });
            })
            .catch(error => console.error('Error fetching tasks:', error));
    });

    // Trigger change on page load to populate parent tasks
    if (projectSelect.value) {
        projectSelect.dispatchEvent(new Event('change'));
    }
});

// Material Management (jQuery)
$(function() {
    const taskId = {{ $task->id }};
    let availableStocks = [];

    // Load available stock when modal opens
    $('#addMaterialModal').on('show.bs.modal', function() {
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
                    $select.html('<option value="">-- No stock available --</option>');
                    return;
                }

                $select.append('<option value="">-- Select product --</option>');
                availableStocks.forEach(function(stock) {
                    $select.append(`<option value="${stock.id}"
                        data-available="${stock.available_qty}"
                        data-unit="${stock.product_unit}">
                        ${stock.product_name} - Avail: ${stock.available_qty} ${stock.product_unit}
                    </option>`);
                });
            },
            error: function() {
                $select.html('<option value="">-- Error loading --</option>');
            }
        });
    }

    // Update hints when product selected
    $('#stockSelect').on('change', function() {
        const $selected = $(this).find(':selected');
        const available = $selected.data('available') || 0;
        const unit = $selected.data('unit') || '';

        $('#unitLabel').text(unit || '-');
        $('#availableQtyHint').html(available > 0 ? `Available: <strong>${available}</strong> ${unit}` : '');
        $('#materialQty').attr('max', available);
    });

    // Calculate total
    function calculateTotal() {
        const qty = parseFloat($('#materialQty').val()) || 0;
        const price = parseFloat($('#unitPrice').val()) || 0;
        $('#calculatedTotal').text('$' + (qty * price).toFixed(2));
    }
    $('#materialQty, #unitPrice').on('input', calculateTotal);

    // Add material
    $('#addMaterialForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#saveMaterialBtn');
        $btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

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
                    addMaterialRow(response.data);
                    refreshTotalCost();
                    $('#addMaterialForm')[0].reset();
                    $('#calculatedTotal').text('$0.00');
                    $('#addMaterialModal').modal('hide');
                    showToast('success', response.message);
                } else {
                    showToast('error', response.message);
                }
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Error adding material');
            },
            complete: function() {
                $btn.html('<i class="fas fa-save mr-1"></i> Add').prop('disabled', false);
            }
        });
    });

    function addMaterialRow(data) {
        $('#noMaterialsRow').remove();
        const row = `
            <tr data-usage-id="${data.id}">
                <td>
                    ${data.product_name}
                    <br><small class="text-muted">$${parseFloat(data.unit_price).toFixed(2)}/${data.product_unit}</small>
                </td>
                <td>${parseFloat(data.quantity).toFixed(2)}</td>
                <td><strong>$${parseFloat(data.total_cost).toFixed(2)}</strong></td>
                <td>
                    <button type="button" class="btn btn-xs btn-danger btn-delete-material"
                        data-usage-id="${data.id}" data-product-name="${data.product_name}">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#materialsTableBody').append(row);
    }

    // Delete material - show modal
    let deleteUsageId = null;

    $(document).on('click', '.btn-delete-material', function() {
        deleteUsageId = $(this).data('usage-id');
        const productName = $(this).data('product-name');

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
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteMaterialModal').modal('hide');

                    $(`tr[data-usage-id="${deleteUsageId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        if ($('#materialsTableBody tr').length === 0) {
                            $('#materialsTableBody').html(
                                '<tr id="noMaterialsRow"><td colspan="4" class="text-center text-muted">No materials added.</td></tr>'
                            );
                        }
                        refreshTotalCost();
                    });
                    showToast('success', response.message);
                } else {
                    showToast('error', response.message || 'Failed to remove material');
                }
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Error');
            },
            complete: function() {
                $btn.html('<i class="fas fa-trash mr-1"></i> Remove').prop('disabled', false);
                deleteUsageId = null;
            }
        });
    });

    // Reset modal state when hidden
    $('#deleteMaterialModal').on('hidden.bs.modal', function() {
        $('#confirmDeleteBtn').html('<i class="fas fa-trash mr-1"></i> Remove').prop('disabled', false);
    });

    function refreshTotalCost() {
        $.get(`{{ url('tasks') }}/${taskId}/materials`, function(response) {
            if (response.success) {
                const total = parseFloat(response.data.total_material_cost) || 0;
                $('#totalMaterialCost').text('$' + total.toFixed(2));
                $('#summaryCost').text('$' + total.toFixed(2));
                // Update actual amount (labor + material)
                const labor = {{ $task->labor_cost ?? 0 }};
                $('#summaryTotal').text('$' + (labor + total).toFixed(2));
            }
        });
    }

    function showToast(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const $alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show"
                 style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                ${message}
            </div>
        `);
        $('body').append($alert);
        setTimeout(() => $alert.alert('close'), 3000);
    }
});
</script>
@endpush
