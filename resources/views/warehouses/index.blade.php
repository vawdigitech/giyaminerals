@extends('layouts.app')
@section('page_title', 'Warehouses')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Warehouses</h3>
        <div class="card-tools">
            @can('warehouses.create')
            <a href="{{ route('warehouses.create') }}" class="btn btn-primary btn-sm">Add
                Warehouse</a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <table id="warehouseTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($warehouses as $warehouse)
                    <tr>
                        <td>{{ $warehouse->name }}</td>
                        <td>{{ $warehouse->location }}</td>
                        <td>
                            @can('warehouses.edit')
                            <a href="{{ route('warehouses.edit', $warehouse->id) }}"
                                class="btn btn-sm btn-info">Edit</a>
                            @endcan
                            @can('warehouses.delete')
                            <form method="POST"
                                action="{{ route('warehouses.destroy', $warehouse->id) }}"
                                style="display:inline-block">
                                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                    data-target="#confirmDeleteModal" data-id="{{ $warehouse->id }}"
                                    data-name="{{ $warehouse->name }}">
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
                Are you sure you want to delete <strong id="warehouseName"></strong>?
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteWarehouseForm">
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
            $('#warehouseTable').DataTable({
                responsive: true,
                autoWidth: false,
                lengthChange: true,
                searching: true,
                ordering: true,
                paging: true
            });

            $('#confirmDeleteModal').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                const warehouseId = button.data('id');
                const warehouseName = button.data('name');

                const modal = $(this);
                modal.find('#warehouseName').text(warehouseName);
                modal.find('#deleteWarehouseForm').attr('action', '/warehouses/' + warehouseId);
            });

            toastr.options = {
                positionClass: 'toast-top-right',
                progressBar: true,
                timeOut: 3000
            };

            if (window.toastMessage) {
                toastr[window.toastMessage.type](window.toastMessage.text);
            }
        });

    </script>
@endpush
