@extends('layouts.app')
@section('page_title', 'Create Role')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles & Permissions</a></li>
    <li class="breadcrumb-item active">Create Role</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Create New Role</h3>
                    </div>
                    <form method="POST" action="{{ route('roles.store') }}">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Role Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name') }}"
                                            placeholder="e.g., manager, supervisor" required>
                                        @error('name')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5>Permissions</h5>
                            <p class="text-muted">Select the permissions this role should have:</p>

                            <div class="row">
                                @foreach($permissions as $module => $modulePermissions)
                                <div class="col-md-4 mb-3">
                                    <div class="card card-outline card-secondary h-100">
                                        <div class="card-header py-2">
                                            <h6 class="card-title mb-0">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input module-toggle" id="module_{{ $module }}" data-module="{{ $module }}">
                                                    <label class="custom-control-label" for="module_{{ $module }}">{{ ucfirst($module) }}</label>
                                                </div>
                                            </h6>
                                        </div>
                                        <div class="card-body py-2">
                                            @foreach($modulePermissions as $permission)
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input permission-checkbox permission-{{ $module }}"
                                                    id="perm_{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}"
                                                    {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="perm_{{ $permission->id }}">
                                                    {{ ucfirst(explode('.', $permission->name)[1]) }}
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Create Role</button>
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
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
    // Module toggle - select/deselect all permissions in module
    $('.module-toggle').on('change', function() {
        const module = $(this).data('module');
        const isChecked = $(this).is(':checked');
        $('.permission-' + module).prop('checked', isChecked);
    });

    // Update module toggle when individual permissions change
    $('.permission-checkbox').on('change', function() {
        const classes = $(this).attr('class').split(' ');
        const moduleClass = classes.find(c => c.startsWith('permission-') && c !== 'permission-checkbox');
        if (moduleClass) {
            const module = moduleClass.replace('permission-', '');
            const total = $('.permission-' + module).length;
            const checked = $('.permission-' + module + ':checked').length;
            $('#module_' + module).prop('checked', total === checked);
        }
    });

    // Initialize module toggles on page load
    $('.module-toggle').each(function() {
        const module = $(this).data('module');
        const total = $('.permission-' + module).length;
        const checked = $('.permission-' + module + ':checked').length;
        $(this).prop('checked', total === checked && total > 0);
    });
</script>
@endpush
