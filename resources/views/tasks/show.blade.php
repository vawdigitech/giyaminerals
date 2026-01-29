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
                        <h3 class="card-title">Costs</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item">
                                <b>Quoted Amount</b> <span class="float-right">${{ number_format($task->quoted_amount ?? 0, 2) }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Labor Cost</b> <span class="float-right">${{ number_format($task->labor_cost ?? 0, 2) }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Material Cost</b> <span class="float-right">${{ number_format($task->material_cost ?? 0, 2) }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Actual Amount</b> <span class="float-right text-primary"><strong>${{ number_format($task->actual_amount ?? 0, 2) }}</strong></span>
                            </li>
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
                    <div class="card-header">
                        <h3 class="card-title">Subtasks ({{ $task->subtasks->count() }})</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Progress</th>
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
                                        <td>{{ $subtask->progress ?? 0 }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Assigned Employees -->
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

                <!-- Work Logs -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Work Logs</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Hours</th>
                                    <th>Time</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($task->workLogs as $workLog)
                                    <tr>
                                        <td>{{ $workLog->date ? $workLog->date->format('M d, Y') : '-' }}</td>
                                        <td>{{ $workLog->assignment->employee->name ?? '-' }}</td>
                                        <td>{{ number_format($workLog->hours, 1) }}</td>
                                        <td>
                                            @if($workLog->start_time && $workLog->end_time)
                                                {{ $workLog->start_time }} - {{ $workLog->end_time }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($workLog->notes, 50) ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No work logs recorded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Stock/Material Usage -->
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
                                    <th width="50"></th>
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

                <!-- Progress Photos -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-camera mr-1"></i>
                            Progress Photos ({{ $task->progressPhotos->count() }})
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($task->progressPhotos->count() > 0)
                            @php
                                $photosByDate = $task->progressPhotos->groupBy(function($photo) {
                                    return $photo->captured_date ? $photo->captured_date->format('Y-m-d') : 'unknown';
                                });
                            @endphp
                            @foreach($photosByDate as $date => $photos)
                                <h5 class="mb-3">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    {{ $date !== 'unknown' ? \Carbon\Carbon::parse($date)->format('M d, Y') : 'Unknown Date' }}
                                </h5>
                                <div class="row mb-4">
                                    @foreach($photos as $photo)
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="card card-outline card-secondary h-100">
                                                @php
                                                    $imgSrc = str_starts_with($photo->photo, 'data:') ? $photo->photo : 'data:image/jpeg;base64,' . $photo->photo;
                                                @endphp
                                                <a href="{{ $imgSrc }}" data-toggle="lightbox" data-gallery="progress-photos">
                                                    <img src="{{ $imgSrc }}" class="card-img-top" alt="Progress Photo" style="height: 200px; object-fit: cover;">
                                                </a>
                                                <div class="card-body p-2">
                                                    @if($photo->caption)
                                                        <p class="card-text small mb-1">{{ $photo->caption }}</p>
                                                    @endif
                                                    <small class="text-muted">
                                                        <i class="fas fa-user mr-1"></i>
                                                        {{ $photo->employee->name ?? 'Unknown' }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
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

        // Material Management
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

            $('#unitLabel').text(unit || '-');
            $('#availableQtyHint').html(
                available > 0
                    ? `<i class="fas fa-check text-success"></i> Available: <strong>${available}</strong> ${unit}`
                    : ''
            );
            $('#materialQty').attr('max', available);
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
    });
</script>
@endpush
