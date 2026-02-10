@extends('layouts.app')
@section('page_title', 'Roles & Permissions')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Roles & Permissions</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Role List</h3>
                        <div class="card-tools">
                            @can('roles.create')
                            <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Role
                            </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Permissions</th>
                                    <th>Users</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                    <tr>
                                        <td>
                                            <strong>{{ ucfirst($role->name) }}</strong>
                                            @if($role->name === 'admin')
                                                <span class="badge badge-warning ml-1">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ $role->permissions_count }} permissions</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $role->users_count }} users</span>
                                        </td>
                                        <td>
                                            @can('roles.edit')
                                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                            @can('roles.delete')
                                            @if($role->name !== 'admin')
                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                data-target="#confirmDeleteModal" data-id="{{ $role->id }}"
                                                data-name="{{ $role->name }}" data-users="{{ $role->users_count }}" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No roles found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $roles->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the <strong id="roleName"></strong> role?</p>
                <p id="warningMessage" class="text-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> This role has users assigned. You must reassign them first.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteForm">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" id="deleteBtn">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $('#confirmDeleteModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const id = button.data('id');
        const name = button.data('name');
        const users = button.data('users');

        $(this).find('#roleName').text(name);
        $(this).find('#deleteForm').attr('action', '/roles/' + id);

        if (users > 0) {
            $(this).find('#warningMessage').show();
            $(this).find('#deleteBtn').prop('disabled', true);
        } else {
            $(this).find('#warningMessage').hide();
            $(this).find('#deleteBtn').prop('disabled', false);
        }
    });

    @if(session('success'))
        toastr.success(@json(session('success')));
    @elseif(session('error'))
        toastr.error(@json(session('error')));
    @endif
</script>
@endpush
