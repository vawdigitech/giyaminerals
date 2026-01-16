@extends('layouts.app')
@section('page_title', 'All Clients')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Clients</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Client List</h3>
                        <div class="card-tools">
                            <a href="{{ route('sites.create') }}" class="btn btn-primary btn-sm">Add
                                Client</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="sitesTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sites as $site)
                                    <tr>
                                        <td>{{ $site->name }}</td>
                                        <td>{{ $site->location }}</td>
                                        <td>
                                            <a href="{{ route('sites.edit', $site->id) }}"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger delete-site"
                                                data-id="{{ $site->id }}" data-name="{{ $site->name }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteSiteModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirm Delete</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to delete site <strong id="siteName"></strong>?
                                    </div>
                                    <div class="modal-footer">
                                        <form id="deleteSiteForm" method="POST">
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
            $('#sitesTable').DataTable({
                responsive: true,
                autoWidth: false,
                lengthChange: true,
                pageLength: 10,
            }).buttons().container().appendTo('#sitesTable_wrapper .col-md-6:eq(0)');

            let deleteForm = $('#deleteSiteForm');
            let deleteModal = $('#deleteSiteModal');
            let siteNameHolder = $('#siteName');

            $('.delete-site').click(function () {
                let id = $(this).data('id');
                let name = $(this).data('name');
                deleteForm.attr('action', `/sites/${id}`);
                siteNameHolder.text(name);
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
