@extends('layouts.app')
@section('page_title', 'Designations')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Designations</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('designations.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or code..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('designations.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Designation List</h3>
                        <div class="card-tools">
                            @can('designations.create')
                            <a href="{{ route('designations.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Designation
                            </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Employees</th>
                                    <th>Status</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($designations as $designation)
                                    <tr>
                                        <td><code>{{ $designation->code }}</code></td>
                                        <td>{{ $designation->name }}</td>
                                        <td>{{ Str::limit($designation->description, 50) ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $designation->employees_count }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $designation->is_active ? 'success' : 'secondary' }}">
                                                {{ $designation->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            @can('designations.edit')
                                            <a href="{{ route('designations.edit', $designation) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                            @can('designations.delete')
                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                data-target="#confirmDeleteModal" data-id="{{ $designation->id }}"
                                                data-name="{{ $designation->name }}" data-count="{{ $designation->employees_count }}" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No designations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $designations->withQueryString()->links() }}
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
                <p>Are you sure you want to delete <strong id="designationName"></strong>?</p>
                <p id="warningMessage" class="text-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> This designation has employees assigned. You must reassign them first.
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
        const count = button.data('count');

        $(this).find('#designationName').text(name);
        $(this).find('#deleteForm').attr('action', '/designations/' + id);

        if (count > 0) {
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
