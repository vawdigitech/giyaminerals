@extends('layouts.app')
@section('page_title', 'All Factories')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Factories</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Factory List</h3>
                        <div class="card-tools">
                            @can('factories.create')
                            <a href="{{ route('factories.create') }}" class="btn btn-primary btn-sm">Add
                                Factory</a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="factoriesTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($factories as $factory)
                                    <tr>
                                        <td>{{ $factory->name }}</td>
                                        <td>{{ $factory->location }}</td>
                                        <td>
                                            @can('factories.edit')
                                            <a href="{{ route('factories.edit', $factory->id) }}"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                            @can('factories.delete')
                                            <button type="button" class="btn btn-sm btn-danger delete-factory"
                                                data-id="{{ $factory->id }}" data-name="{{ $factory->name }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endcan
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteFactoryModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirm Delete</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to delete factory <strong id="factoryName"></strong>?
                                    </div>
                                    <div class="modal-footer">
                                        <form id="deleteFactoryForm" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Cancel</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.modal -->

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
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
            $('#factoriesTable').DataTable({
                responsive: true,
                autoWidth: false,
                lengthChange: true,
                pageLength: 10,
            }).buttons().container().appendTo('#factoriesTable_wrapper .col-md-6:eq(0)');

            let deleteForm = $('#deleteFactoryForm');
            let deleteModal = $('#deleteFactoryModal');
            let factoryNameHolder = $('#factoryName');

            $('.delete-factory').click(function () {
                let id = $(this).data('id');
                let name = $(this).data('name');
                deleteForm.attr('action', `/factories/${id}`);
                factoryNameHolder.text(name);
                deleteModal.modal('show');
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
