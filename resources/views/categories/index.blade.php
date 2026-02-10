@extends('layouts.app')
@section('page_title', 'Item Categories')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Item Categories</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Item Categories</h3>
        <div class="card-tools">
            @can('inventory.create')
            <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">Add Category</a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <table id="categoryTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>
                            @can('inventory.edit')
                            <a href="{{ route('categories.edit', $category->id) }}"
                                class="btn btn-sm btn-info">Edit</a>
                            @endcan
                            @can('inventory.delete')
                            <form method="POST"
                                action="{{ route('categories.destroy', $category->id) }}"
                                style="display:inline-block">
                                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                    data-target="#confirmDeleteModal" data-id="{{ $category->id }}"
                                    data-name="{{ $category->name }}">
                                    Delete
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="close text-black" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-black">
                Are you sure you want to delete <strong id="categoryName"></strong>?
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteCategoryForm">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
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

@push('scripts')
    <script>
        $(function () {
            $('#categoryTable').DataTable({
                "responsive": true,
                "autoWidth": false,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "paging": true
            });
        });

        $('#confirmDeleteModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const categoryId = button.data('id');
            const categoryName = button.data('name');

            const modal = $(this);
            modal.find('#categoryName').text(categoryName);
            modal.find('#deleteCategoryForm').attr('action', '/categories/' + categoryId);
        });

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
