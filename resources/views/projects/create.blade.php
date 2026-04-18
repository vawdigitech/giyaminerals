@extends('layouts.app')
@section('page_title', 'Create Project')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
    <li class="breadcrumb-item active">Create Project</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Create New Project</h3>
                    </div>
                    <form method="POST" action="{{ route('projects.store') }}">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="code">Project Code <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                                id="code" name="code"
                                                value="{{ old('code', $nextCode) }}"
                                                placeholder="{{ $nextCode }}" required>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="refreshCode" title="Generate new code">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                        @error('code')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">Auto-generated. You can edit it if needed.</small>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="name">Project Name <span class="text-danger">*</span></label>
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Work Locations - Sites</label>
                                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                            @forelse($sites as $site)
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="site_{{ $site->id }}"
                                                        name="site_ids[]"
                                                        value="{{ $site->id }}"
                                                        {{ (is_array(old('site_ids')) && in_array($site->id, old('site_ids'))) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="site_{{ $site->id }}">
                                                        {{ $site->name }}
                                                    </label>
                                                </div>
                                            @empty
                                                <p class="text-muted">No sites available</p>
                                            @endforelse
                                        </div>
                                        @error('site_ids')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                        @error('location')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Work Locations - Factories</label>
                                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                            @forelse($factories as $factory)
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="factory_{{ $factory->id }}"
                                                        name="factory_ids[]"
                                                        value="{{ $factory->id }}"
                                                        {{ (is_array(old('factory_ids')) && in_array($factory->id, old('factory_ids'))) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="factory_{{ $factory->id }}">
                                                        {{ $factory->name }}
                                                    </label>
                                                </div>
                                            @empty
                                                <p class="text-muted">No factories available</p>
                                            @endforelse
                                        </div>
                                        @error('factory_ids')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">Select one or more sites and/or factories where work will be done</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quoted_amount">Quoted Amount (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0"
                                            class="form-control @error('quoted_amount') is-invalid @enderror"
                                            id="quoted_amount" name="quoted_amount" value="{{ old('quoted_amount', '0.00') }}" required>
                                        @error('quoted_amount')
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
                                        <label for="end_date">End Date</label>
                                        <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                            id="end_date" name="end_date" value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
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
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Create Project</button>
                            <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
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
document.getElementById('refreshCode').addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    btn.querySelector('i').classList.add('fa-spin');

    fetch('{{ route('projects.generate-code') }}')
        .then(function (res) { return res.json(); })
        .then(function (data) {
            document.getElementById('code').value = data.code;
        })
        .catch(function () {
            alert('Could not generate code. Please try again.');
        })
        .finally(function () {
            btn.disabled = false;
            btn.querySelector('i').classList.remove('fa-spin');
        });
});
</script>
@endpush
