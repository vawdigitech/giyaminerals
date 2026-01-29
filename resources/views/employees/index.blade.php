@extends('layouts.app')
@section('page_title', 'Employees')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Employees</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('employees.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <select name="site_id" class="form-control">
                            <option value="">All Sites</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                    {{ $site->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="role" class="form-control">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                    {{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Employee List</h3>
                        <div class="card-tools">
                            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Employee
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Phone</th>
                                    <th>Site</th>
                                    <th>Hourly Rate</th>
                                    <th>Status</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                    <tr>
                                        <td>{{ $employee->employee_code }}</td>
                                        <td>{{ $employee->name }}</td>
                                        <td>{{ ucfirst($employee->role) }}</td>
                                        <td>{{ $employee->phone ?? '-' }}</td>
                                        <td>{{ $employee->site->name ?? '-' }}</td>
                                        <td>${{ number_format($employee->hourly_rate, 2) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($employee->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                data-target="#confirmDeleteModal" data-id="{{ $employee->id }}"
                                                data-name="{{ $employee->name }}" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No employees found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $employees->withQueryString()->links() }}
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
                Are you sure you want to delete <strong id="employeeName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteForm">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
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
        $(this).find('#employeeName').text(name);
        $(this).find('#deleteForm').attr('action', '/employees/' + id);
    });

    @if(session('success'))
        toastr.success(@json(session('success')));
    @elseif(session('error'))
        toastr.error(@json(session('error')));
    @endif
</script>
@endpush
