@extends('layouts.app')
@section('page_title', 'Create Task')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
    <li class="breadcrumb-item active">Create Task</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Create New Task</h3>
                    </div>
                    <form method="POST" action="{{ route('tasks.store') }}">
                        @csrf
                        <div class="card-body">
                            {{-- Step 1: Project --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="project_id">Project <span class="text-danger">*</span></label>
                                        <select class="form-control @error('project_id') is-invalid @enderror"
                                            id="project_id" name="project_id" required>
                                            <option value="">-- Select Project --</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}"
                                                    {{ old('project_id', request('project_id')) == $project->id ? 'selected' : '' }}>
                                                    {{ $project->code }} - {{ $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('project_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- Step 2: Parent Task (only relevant after project selected) --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="parent_id">Parent Task <small class="text-muted">(leave blank for a master task)</small></label>
                                        <select class="form-control @error('parent_id') is-invalid @enderror"
                                            id="parent_id" name="parent_id">
                                            <option value="">-- No Parent (Master Task) --</option>
                                            @foreach($parentTasks as $task)
                                                @php
                                                    $level = $task->getNestingLevel();
                                                    $indent = str_repeat('  ', $level);
                                                    $prefix = $level > 0 ? '└─ ' : '';
                                                    $displayName = $indent . $prefix . $task->code . ' - ' . $task->name;
                                                @endphp
                                                <option value="{{ $task->id }}" {{ old('parent_id') == $task->id ? 'selected' : '' }}>
                                                    {{ $displayName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('parent_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Step 2.5: Work Locations --}}
                            <div class="row" id="locations-section" style="display: none;">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Work Locations <span class="text-danger">*</span></label>
                                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                            <div id="locations-list">
                                                <p class="text-muted">Please select a project first</p>
                                            </div>
                                        </div>
                                        @error('location_ids')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">Select one or more locations where this task will be performed</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Step 3: Task Code (auto-generated) + Task Name --}}
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="code">
                                            Task Code <span class="text-danger">*</span>
                                            <small class="text-muted" id="code-hint"></small>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                                id="code" name="code" value="{{ old('code') }}"
                                                placeholder="Select a project first" required>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="refresh-code"
                                                    title="Re-generate code" style="display:none;">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                        @error('code')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="name">Task Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="section">Section</label>
                                        <input type="text" class="form-control @error('section') is-invalid @enderror"
                                            id="section" name="section" value="{{ old('section') }}"
                                            placeholder="e.g., Electrical, Plumbing">
                                        @error('section')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="quoted_amount">Quoted Amount</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" step="0.01" min="0"
                                                class="form-control @error('quoted_amount') is-invalid @enderror"
                                                id="quoted_amount" name="quoted_amount"
                                                value="{{ old('quoted_amount') }}" placeholder="0.00">
                                            @error('quoted_amount')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="priority">Priority <span class="text-danger">*</span></label>
                                        <select class="form-control @error('priority') is-invalid @enderror"
                                            id="priority" name="priority" required>
                                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                            <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                            <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                                        </select>
                                        @error('priority')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="status">Status <span class="text-danger">*</span></label>
                                        <select class="form-control @error('status') is-invalid @enderror"
                                            id="status" name="status" required>
                                            <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                                        </select>
                                        @error('status')
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
                                            id="start_date" name="start_date" value="{{ old('start_date') }}">
                                        @error('start_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="due_date">Due Date</label>
                                        <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                            id="due_date" name="due_date" value="{{ old('due_date') }}">
                                        @error('due_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Note:</strong> After creating the task, you can add materials/stock usage from the task details page.
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Create Task</button>
                            <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectSelect  = document.getElementById('project_id');
    const parentSelect   = document.getElementById('parent_id');
    const codeInput      = document.getElementById('code');
    const codeHint       = document.getElementById('code-hint');
    const refreshBtn     = document.getElementById('refresh-code');
    const locationsSection = document.getElementById('locations-section');
    const locationsList = document.getElementById('locations-list');

    // Project locations data passed from controller
    const projectLocationsData = @json($projectLocations ?? []);

    function suggestCode() {
        const projectId = projectSelect.value;
        const parentId  = parentSelect.value;

        if (!projectId) return;

        let url = `{{ route('tasks.suggest-code') }}?project_id=${projectId}`;
        if (parentId) url += `&parent_id=${parentId}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                codeInput.value = data.code;
                codeHint.textContent = parentId ? '(sub-task, auto)' : '(master task, auto)';
                refreshBtn.style.display = 'inline-block';
            })
            .catch(err => console.error('Error suggesting code:', err));
    }

    function loadParentTasks(projectId, callback) {
        parentSelect.innerHTML = '<option value="">-- No Parent (Master Task) --</option>';
        if (!projectId) {
            codeInput.value = '';
            codeInput.placeholder = 'Select a project first';
            codeHint.textContent = '';
            refreshBtn.style.display = 'none';
            locationsSection.style.display = 'none';
            return;
        }

        fetch(`{{ url('tasks-by-project') }}/${projectId}`)
            .then(r => r.json())
            .then(tasks => {
                tasks.forEach(task => {
                    const opt = document.createElement('option');
                    opt.value = task.id;
                    // Use display_name if available (includes hierarchy), otherwise fallback
                    opt.textContent = task.display_name || `${task.code} - ${task.name}`;
                    parentSelect.appendChild(opt);
                });
                if (callback) callback();
            })
            .catch(err => console.error('Error fetching parent tasks:', err));
    }

    function loadProjectLocations(projectId) {
        if (!projectId) {
            locationsSection.style.display = 'none';
            return;
        }

        // Fetch project locations
        fetch(`/projects/${projectId}/locations`)
            .then(r => r.json())
            .then(data => {
                locationsList.innerHTML = '';

                if (data.locations && data.locations.length > 0) {
                    data.locations.forEach(location => {
                        const checkbox = document.createElement('div');
                        checkbox.className = 'custom-control custom-checkbox';
                        checkbox.innerHTML = `
                            <input type="checkbox" class="custom-control-input location-checkbox"
                                id="location_${location.type}_${location.id}"
                                name="location_ids[]"
                                value="${location.type}:${location.id}"
                                checked>
                            <label class="custom-control-label" for="location_${location.type}_${location.id}">
                                ${location.name}
                            </label>
                        `;
                        locationsList.appendChild(checkbox);
                    });
                    locationsSection.style.display = 'block';
                } else {
                    locationsList.innerHTML = '<p class="text-warning">No locations assigned to this project yet.</p>';
                    locationsSection.style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Error fetching locations:', err);
                locationsList.innerHTML = '<p class="text-danger">Error loading locations</p>';
            });
    }

    projectSelect.addEventListener('change', function() {
        loadParentTasks(this.value, () => suggestCode());
        loadProjectLocations(this.value);
    });

    parentSelect.addEventListener('change', function() {
        if (projectSelect.value) suggestCode();
    });

    refreshBtn.addEventListener('click', function() {
        suggestCode();
    });

    // On page load: restore state if old() values are present
    if (projectSelect.value) {
        const oldParent = "{{ old('parent_id') }}";
        loadParentTasks(projectSelect.value, () => {
            if (oldParent) {
                parentSelect.value = oldParent;
            }
            // Only auto-fill code if no old code value exists
            if (!codeInput.value) {
                suggestCode();
            } else {
                refreshBtn.style.display = 'inline-block';
            }
        });
        loadProjectLocations(projectSelect.value);
    } else if (projectLocationsData.length > 0) {
        // If we have locations data from the server (from old input), show them
        locationsList.innerHTML = '';
        projectLocationsData.forEach(location => {
            const checkbox = document.createElement('div');
            checkbox.className = 'custom-control custom-checkbox';
            checkbox.innerHTML = `
                <input type="checkbox" class="custom-control-input location-checkbox"
                    id="location_${location.type}_${location.id}"
                    name="location_ids[]"
                    value="${location.type}:${location.id}"
                    checked>
                <label class="custom-control-label" for="location_${location.type}_${location.id}">
                    ${location.name}
                </label>
            `;
            locationsList.appendChild(checkbox);
        });
        locationsSection.style.display = 'block';
    }
});
</script>
@endpush
