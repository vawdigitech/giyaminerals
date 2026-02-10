@extends('layouts.app')
@section('page_title', 'New Stock Entry')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stocks.entries') }}">Stock Entries</a></li>
<li class="breadcrumb-item active">New Stock Entry</li>
@endsection
@section('content')
<form method="POST" action="{{ route('stocks.store') }}"> @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label>Product</label>
                <select name="product_id" class="form-control" required>
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                            {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} @if($product->category) ({{ $product->category->name }})
                    @endif
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Location (Warehouse or Site)</label>
                <select name="location" id="location" class="form-control" required>
                    <option value="">-- Select Location --</option>
                    <optgroup label="Warehouses">
                        @foreach($warehouses as $w)
                            <option value="warehouse:{{ $w->id }}" data-type="warehouse"
                                {{ old('location') == "warehouse:$w->id" ? 'selected' : '' }}>
                                [W] {{ $w->name }}
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Sites">
                        @foreach($sites as $s)
                            <option value="site:{{ $s->id }}" data-type="site" data-site-id="{{ $s->id }}"
                                {{ old('location') == "site:$s->id" ? 'selected' : '' }}>
                                [S] {{ $s->name }}
                            </option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            <!-- Task Selection Section (shown only when Site is selected) -->
            <div id="taskSection" style="display: none;">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Since you selected a Site, you can optionally assign this stock to a specific task. This will automatically create a material usage record.
                </div>
                <div class="form-group">
                    <label>Master Task (Optional)</label>
                    <select id="masterTask" class="form-control">
                        <option value="">-- No Task (Stock only) --</option>
                    </select>
                </div>
                <div class="form-group" id="subTaskGroup" style="display: none;">
                    <label>Sub Task (Optional)</label>
                    <select id="subTask" class="form-control">
                        <option value="">-- Select Sub Task --</option>
                    </select>
                    <small class="text-muted">Leave empty to assign to the master task</small>
                </div>
                <!-- Hidden field to submit the actual task_id -->
                <input type="hidden" name="task_id" id="taskIdInput" value="">
            </div>

            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" required
                    value="{{ old('quantity') }}">
            </div>
            <div class="form-group">
                <label>Entry Date</label>
                <input type="date" name="entry_date" class="form-control" required
                    value="{{ old('entry_date', now()->toDateString()) }}">
            </div>
            <div class="form-group">
                <label>Reference</label>
                <input type="text" name="reference" class="form-control" placeholder="Optional: invoice, GRN, etc."
                    value="{{ old('reference') }}">
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-success">Save Entry</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function() {
    const locationSelect = $('#location');
    const taskSection = $('#taskSection');
    const masterTaskSelect = $('#masterTask');
    const subTaskGroup = $('#subTaskGroup');
    const subTaskSelect = $('#subTask');

    // Handle location change
    locationSelect.on('change', function() {
        const selectedOption = $(this).find(':selected');
        const locationType = selectedOption.data('type');
        const siteId = selectedOption.data('site-id');

        // Reset task fields
        masterTaskSelect.html('<option value="">-- No Task (Stock only) --</option>');
        subTaskSelect.html('<option value="">-- Select Sub Task --</option>');
        subTaskGroup.hide();

        if (locationType === 'site' && siteId) {
            taskSection.show();
            // Fetch tasks for this site
            fetchTasksBySite(siteId);
        } else {
            taskSection.hide();
        }
    });

    const taskIdInput = $('#taskIdInput');

    // Handle master task change
    masterTaskSelect.on('change', function() {
        const taskId = $(this).val();
        subTaskSelect.html('<option value="">-- Select Sub Task --</option>');

        if (taskId) {
            subTaskGroup.show();
            fetchSubtasks(taskId);
            // Set task_id to master task initially
            taskIdInput.val(taskId);
        } else {
            subTaskGroup.hide();
            taskIdInput.val('');
        }
    });

    // Handle sub task change
    subTaskSelect.on('change', function() {
        const subTaskId = $(this).val();
        const masterTaskId = masterTaskSelect.val();
        // If subtask is selected, use it; otherwise use master task
        taskIdInput.val(subTaskId || masterTaskId);
    });

    // Fetch tasks for a site
    function fetchTasksBySite(siteId) {
        $.ajax({
            url: `/sites/${siteId}/tasks`,
            method: 'GET',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    response.data.forEach(function(task) {
                        masterTaskSelect.append(
                            `<option value="${task.id}">${task.display_name}</option>`
                        );
                    });
                } else {
                    masterTaskSelect.append(
                        '<option value="" disabled>No tasks found for this site</option>'
                    );
                }
            },
            error: function() {
                toastr.error('Failed to load tasks');
            }
        });
    }

    // Fetch subtasks for a master task
    function fetchSubtasks(taskId) {
        $.ajax({
            url: `/tasks/${taskId}/subtasks`,
            method: 'GET',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    response.data.forEach(function(subtask) {
                        subTaskSelect.append(
                            `<option value="${subtask.id}">${subtask.display_name}</option>`
                        );
                    });
                } else {
                    subTaskSelect.append(
                        '<option value="" disabled>No subtasks - will assign to master task</option>'
                    );
                }
            },
            error: function() {
                toastr.error('Failed to load subtasks');
            }
        });
    }

    // Trigger change on page load if location is pre-selected
    if (locationSelect.val()) {
        locationSelect.trigger('change');
    }
});
</script>
@endpush
