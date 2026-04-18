@extends('layouts.app')
@section('page_title', 'New Stock Transfer')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transfers.index') }}">Transfer History</a></li>
    <li class="breadcrumb-item active">New Transfer</li>
@endsection
@section('content')

<form method="POST" action="{{ route('transfers.store') }}">
    @csrf
    <div class="card">
        <div class="card-body">

            <div class="form-group">
                <label>Product</label>
                <select name="product_id" id="product-select" class="form-control" required>
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

            <div class="row">
                <div class="form-group col-md-6">
                    <label>From (Warehouse/Site)</label>
                    <select name="from" id="from-select" class="form-control" required>
                        <option value="">-- Select Source --</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>To (Warehouse/Site)</label>
                    <select name="to" id="to-select" class="form-control" required>
                        <option value="">-- Select Destination --</option>
                    </select>
                </div>
            </div>

            <!-- Task Selection Section (shown only when destination is a Site) -->
            <div id="taskSection" style="display: none;">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> You can optionally assign this transfer to a specific task. This will automatically create a material usage record.
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

                <!-- Work Location Selection (shown after task is selected) -->
                <div class="form-group" id="workLocationGroup" style="display: none;">
                    <label>Work Location <span class="text-danger">*</span></label>
                    <select name="work_location" id="workLocation" class="form-control">
                        <option value="">-- Select Work Location --</option>
                    </select>
                    <small class="text-muted">Select whether this material is for factory or site work</small>
                </div>
            </div>

            <div class="form-group mt-2">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" required value="{{ old('quantity') }}">
            </div>

            <div class="form-group">
                <label>Transfer Date</label>
                <input type="datetime-local" name="transfer_date" class="form-control"
                    value="{{ old('transfer_date', now()->format('Y-m-d\TH:i')) }}" required>
            </div>

        </div>
        <div class="card-footer">
            <button class="btn btn-success">Save Transfer</button>
        </div>
    </div>
</form>
@endsection

@if(session('success'))
    <script>
        window.toastMessage = {
            type: 'success',
            text: @json(session('success'))
        };
    </script>
@elseif(session('error'))
    <script>
        window.toastMessage = {
            type: 'error',
            text: @json(session('error'))
        };
    </script>
@endif

@php
    $allLocationsArray = collect($warehouses)->map(function($w) {
        return [
            'value' => "warehouse:{$w->id}",
            'label' => '[W] ' . $w->name,
        ];
    })->merge(
        collect($sites)->map(function($s) {
            return [
                'value' => "site:{$s->id}",
                'label' => '[S] ' . $s->name,
            ];
        })
    )->values();
@endphp

@push('scripts')
<script>
    const allLocations = @json($allLocationsArray);
    const oldFrom = "{{ old('from') }}";
    const oldTo = "{{ old('to') }}";
    const oldProductId = "{{ old('product_id') }}";

    const fromSelect = document.getElementById('from-select');
    const toSelect = document.getElementById('to-select');
    const productSelect = document.getElementById('product-select');

    function clearSelect(select, placeholder) {
        select.innerHTML = `<option value="">-- ${placeholder} --</option>`;
    }

    function populateToSelect(selectedFrom) {
        clearSelect(toSelect, 'Select Destination');
        allLocations
            .filter(loc => loc.value !== selectedFrom)
            .forEach(loc => {
                const option = new Option(loc.label, loc.value);
                toSelect.add(option);
            });
        if (oldTo) {
            toSelect.value = oldTo;
        }
    }

    productSelect.addEventListener('change', () => {
        const productId = productSelect.value;
        clearSelect(fromSelect, 'Select Source');
        clearSelect(toSelect, 'Select Destination');

        if (!productId) return;

        fetch(`{{ route('transfers.locations') }}?product_id=${productId}`)
            .then(res => res.json())
            .then(locations => {
                locations.forEach(loc => {
                    fromSelect.add(new Option(loc.label, loc.value));
                });

                if (oldFrom) {
                    fromSelect.value = oldFrom;
                    populateToSelect(oldFrom);
                }
            });
    });

    fromSelect.addEventListener('change', () => {
        const selectedFrom = fromSelect.value;
        populateToSelect(selectedFrom);
    });

    // Task and Work Location handling
    const taskSection = document.getElementById('taskSection');
    const masterTaskSelect = document.getElementById('masterTask');
    const subTaskGroup = document.getElementById('subTaskGroup');
    const subTaskSelect = document.getElementById('subTask');
    const taskIdInput = document.getElementById('taskIdInput');
    const workLocationGroup = document.getElementById('workLocationGroup');
    const workLocationSelect = document.getElementById('workLocation');

    // Handle destination change - show task section if destination is a site
    toSelect.addEventListener('change', function() {
        const toValue = toSelect.value;
        if (!toValue) {
            taskSection.style.display = 'none';
            return;
        }

        const [toType, toId] = toValue.split(':');
        if (toType === 'site') {
            taskSection.style.display = 'block';
            fetchTasksBySite(toId);
        } else {
            taskSection.style.display = 'none';
            resetTaskFields();
        }
    });

    // Handle master task change
    masterTaskSelect.addEventListener('change', function() {
        const taskId = masterTaskSelect.value;
        subTaskSelect.innerHTML = '<option value="">-- Select Sub Task --</option>';
        workLocationSelect.innerHTML = '<option value="">-- Select Work Location --</option>';

        if (taskId) {
            subTaskGroup.style.display = 'block';
            fetchSubtasks(taskId);
            taskIdInput.value = taskId;
            fetchTaskLocations(taskId);
        } else {
            subTaskGroup.style.display = 'none';
            workLocationGroup.style.display = 'none';
            taskIdInput.value = '';
        }
    });

    // Handle sub task change
    subTaskSelect.addEventListener('change', function() {
        const subTaskId = subTaskSelect.value;
        const masterTaskId = masterTaskSelect.value;
        const selectedTaskId = subTaskId || masterTaskId;

        taskIdInput.value = selectedTaskId;

        if (selectedTaskId) {
            fetchTaskLocations(selectedTaskId);
        }
    });

    function resetTaskFields() {
        masterTaskSelect.innerHTML = '<option value="">-- No Task (Stock only) --</option>';
        subTaskSelect.innerHTML = '<option value="">-- Select Sub Task --</option>';
        workLocationSelect.innerHTML = '<option value="">-- Select Work Location --</option>';
        subTaskGroup.style.display = 'none';
        workLocationGroup.style.display = 'none';
        taskIdInput.value = '';
    }

    function fetchTasksBySite(siteId) {
        resetTaskFields();
        fetch(`/sites/${siteId}/tasks`)
            .then(res => res.json())
            .then(response => {
                if (response.success && response.data.length > 0) {
                    response.data.forEach(task => {
                        masterTaskSelect.add(new Option(task.display_name, task.id));
                    });
                } else {
                    masterTaskSelect.add(new Option('No tasks found for this site', '', true, false));
                    if (response.message) {
                        toastr.info(response.message);
                    }
                }
            })
            .catch(err => {
                console.error('Task loading error:', err);
                toastr.error('Failed to load tasks');
                masterTaskSelect.add(new Option('Error loading tasks', '', true, false));
            });
    }

    function fetchSubtasks(taskId) {
        fetch(`/tasks/${taskId}/subtasks`)
            .then(res => res.json())
            .then(response => {
                if (response.success && response.data.length > 0) {
                    response.data.forEach(subtask => {
                        subTaskSelect.add(new Option(subtask.display_name, subtask.id));
                    });
                } else {
                    subTaskSelect.add(new Option('No subtasks - will assign to master task', '', true, false));
                }
            })
            .catch(err => {
                console.error('Subtask loading error:', err);
                toastr.error('Failed to load subtasks');
            });
    }

    function fetchTaskLocations(taskId) {
        workLocationSelect.innerHTML = '<option value="">-- Loading... --</option>';
        workLocationGroup.style.display = 'none';

        fetch(`/tasks/${taskId}/locations`)
            .then(res => res.json())
            .then(response => {
                workLocationSelect.innerHTML = '<option value="">-- Select Work Location --</option>';

                if (response.success && response.data.length > 0) {
                    response.data.forEach(location => {
                        workLocationSelect.add(new Option(
                            location.display_name,
                            `${location.location_type}:${location.location_id}`
                        ));
                    });
                    workLocationGroup.style.display = 'block';
                    workLocationSelect.required = true;
                } else {
                    workLocationSelect.add(new Option('No work locations assigned to this task', '', true, false));
                    toastr.warning('This task has no work locations assigned.');
                    workLocationSelect.required = false;
                }
            })
            .catch(err => {
                console.error('Work location loading error:', err);
                workLocationSelect.innerHTML = '<option value="">-- Error loading locations --</option>';
                toastr.error('Failed to load work locations');
                workLocationSelect.required = false;
            });
    }

    if (oldProductId) {
        productSelect.dispatchEvent(new Event('change'));
    }

    toastr.options = {
        "positionClass": "toast-top-right",
        "progressBar": true,
        "timeOut": "3000"
    };
    if (window.toastMessage) {
        toastr[window.toastMessage.type](window.toastMessage.text);
    }
</script>
@endpush
