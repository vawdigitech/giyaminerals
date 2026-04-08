@extends('layouts.app')
@section('page_title', 'Designation Categories')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Designation Categories</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('designation-categories.index') }}" class="row g-3">
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
                        <a href="{{ route('designation-categories.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Designation Category List</h3>
                        <div class="card-tools">
                            @can('designation_categories.create')
                            <a href="{{ route('designation-categories.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Category
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
                                    <th>Designations</th>
                                    <th>Status</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                    <tr>
                                        <td><code>{{ $category->code }}</code></td>
                                        <td>{{ $category->name }}</td>
                                        <td>{{ Str::limit($category->description, 50) ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $category->designations_count }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $category->is_active ? 'success' : 'secondary' }}">
                                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            @can('designation_categories.edit')
                                            <a href="{{ route('designation-categories.edit', $category) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                            @can('designation_categories.delete')
                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                data-target="#confirmDeleteModal" data-id="{{ $category->id }}"
                                                data-name="{{ $category->name }}" data-count="{{ $category->designations_count }}" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No designation categories found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $categories->withQueryString()->links() }}
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
                <p>Are you sure you want to delete <strong id="categoryName"></strong>?</p>
                <p id="warningMessage" class="text-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> This category has designations assigned. You must reassign them first.
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

        $(this).find('#categoryName').text(name);
        $(this).find('#deleteForm').attr('action', '/designation-categories/' + id);

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
