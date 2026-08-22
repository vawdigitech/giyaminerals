@extends('layouts.app')
@section('page_title', 'Stock Entries')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
  <li class="breadcrumb-item active">Stock Entries</li>
@endsection
@section('content')
<div class="card">
  <div class="card-header">
        <h3 class="card-title">Stock Entry History</h3>
        <div class="card-tools">
            @can('inventory.create')
            <a href="{{ route('stocks.entry') }}" class="btn btn-primary btn-sm">New Stock Entry</a>
            @endcan
        </div>
    </div>
  <div class="card-body">
    <table id="entryTable" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>Location</th>
          <th>Qty</th>
          <th>Reference</th>
          <th>Entry Date</th>
          <th>Entered By</th>
          @if(auth()->user()->can('inventory.edit') || auth()->user()->can('inventory.delete'))
          <th style="width: 100px;">Actions</th>
          @endif
        </tr>
      </thead>
      <tbody>
        @foreach($entries as $e)
        <tr>
          <td>{{ $e->product->name }}</td>
          <td>{{ $e->product->category->name ?? '-' }}</td>
          <td>{{ strtoupper($e->location_type[0]) }} - {{ $e->location_name }}</td>
          <td>{{ $e->quantity }}</td>
          <td>{{ $e->reference ?? '-' }}</td>
          <td>{{ \Carbon\Carbon::parse($e->entry_date)->format('Y-m-d') }}</td>
          <td>{{ $e->user->name ?? 'N/A' }}</td>
          @if(auth()->user()->can('inventory.edit') || auth()->user()->can('inventory.delete'))
          <td>
            @can('inventory.edit')
            <a href="{{ route('stocks.entries.edit', $e) }}" class="btn btn-sm btn-info">
              <i class="fas fa-edit"></i>
            </a>
            @endcan
            @can('inventory.delete')
            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                data-target="#confirmDeleteModal" data-id="{{ $e->id }}"
                data-name="{{ $e->product->name }} ({{ $e->quantity }} units)">
              <i class="fas fa-trash-alt"></i>
            </button>
            @endcan
          </td>
          @endif
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
                Are you sure you want to delete the stock entry for <strong id="entryName"></strong>?
                This will reduce the received quantity in the related stock record.
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteEntryForm">
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
    $('#entryTable').DataTable();

    $('#confirmDeleteModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const entryId = button.data('id');
        const entryName = button.data('name');
        const modal = $(this);

        modal.find('#entryName').text(entryName);
        modal.find('#deleteEntryForm').attr('action', '/stocks/entries/' + entryId);
    });

    toastr.options = {
        "positionClass": "toast-top-right",
        "progressBar": true,
        "timeOut": "3000"
    };
    if (window.toastMessage) {
        toastr[window.toastMessage.type](window.toastMessage.text);
    }
  });
</script>
@endpush
