@extends('layouts.app')
@section('page_title', 'All Items')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Item</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Item List</h3>
                        <div class="card-tools">
                            <a href="{{ route('products.create') }}"
                                class="btn btn-primary btn-sm">Add Item</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="productsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Unit</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->category->name ?? '-' }}</td>
                                        <td>{{ $product->unit }}</td>
                                        <td>
                                            <a href="{{ route('products.edit', $product->id) }}"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                    data-target="#confirmDeleteModal" data-id="{{ $product->id }}"
                                                    data-name="{{ $product->name }}">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>

                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
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
                Are you sure you want to delete <strong id="productName"></strong>?
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteProductForm">
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
            $('#productsTable').DataTable({
                "responsive": true,
                "autoWidth": false,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "paging": true
            });

            $('#confirmDeleteModal').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                const productId = button.data('id');
                const productName = button.data('name');
                const modal = $(this);

                modal.find('#productName').text(productName);
                modal.find('#deleteProductForm').attr('action', '/products/' + productId);
            });
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
