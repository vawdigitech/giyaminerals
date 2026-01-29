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
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="code">Task Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                                            id="code" name="code" value="{{ old('code') }}"
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
                                            id="name" name="name" value="{{ old('name') }}" required>
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="parent_id">Parent Task (Optional)</label>
                                        <select class="form-control @error('parent_id') is-invalid @enderror"
                                            id="parent_id" name="parent_id">
                                            <option value="">-- No Parent (Top-level Task) --</option>
                                            @foreach($parentTasks as $task)
                                                <option value="{{ $task->id }}" {{ old('parent_id') == $task->id ? 'selected' : '' }}>
                                                    {{ $task->code }} - {{ $task->name }}
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
    const projectSelect = document.getElementById('project_id');
    const parentSelect = document.getElementById('parent_id');

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
                    const option = document.createElement('option');
                    option.value = task.id;
                    option.textContent = `${task.code} - ${task.name}`;
                    parentSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching tasks:', error));
    });

    // Trigger change if project is already selected (e.g., on page load with old value)
    if (projectSelect.value) {
        projectSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
