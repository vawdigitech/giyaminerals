@extends('layouts.app')
@section('page_title', 'Advance Payments')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Advance Payments</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>Rs.{{ number_format($summary['total_given_this_week'], 2) }}</h3>
                        <p>Given This Week</p>
                    </div>
                    <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>Rs.{{ number_format($summary['total_pending_deduction'], 2) }}</h3>
                        <p>Pending Deduction</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $summary['given_count_this_week'] }}</h3>
                        <p>Advances This Week</p>
                    </div>
                    <div class="icon"><i class="fas fa-receipt"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $summary['pending_count'] }}</h3>
                        <p>Pending Count</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
        </div>

        <!-- Current Week Info -->
        <div class="alert alert-info">
            <i class="fas fa-calendar-week"></i>
            <strong>Current Week:</strong> {{ $currentWeekStart->format('M d') }} - {{ $currentWeekEnd->format('M d, Y') }}
        </div>

        <!-- Actions -->
        <div class="mb-3">
            @can('salary.manage')
            <a href="{{ route('advances.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Give Advance
            </a>
            @endcan
            <a href="{{ route('salary.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-money-bill-wave"></i> Weekly Salary
            </a>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('advances.index') }}" class="row">
                    <div class="col-md-2">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="given" {{ request('status') == 'given' ? 'selected' : '' }}>Pending</option>
                            <option value="deducted" {{ request('status') == 'deducted' ? 'selected' : '' }}>Deducted</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="employee_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="site_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Sites</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                    {{ $site->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="From Date" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="To Date" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('advances.index') }}" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Advances Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Advance Payments</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Employee</th>
                            <th>Site</th>
                            <th>Week</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center">Days Worked</th>
                            <th class="text-right">Earned at Time</th>
                            <th class="text-center">Status</th>
                            <th>Given By</th>
                            <th>Given At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($advances as $advance)
                            <tr>
                                <td>
                                    <strong>{{ $advance->employee->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $advance->employee->designation->name ?? '-' }}</small>
                                </td>
                                <td>{{ $advance->employee->site->name ?? '-' }}</td>
                                <td>
                                    <small>{{ $advance->week_start_date->format('M d') }} - {{ $advance->week_end_date->format('M d') }}</small>
                                </td>
                                <td class="text-right font-weight-bold">Rs.{{ number_format($advance->amount, 2) }}</td>
                                <td class="text-center">{{ $advance->days_worked_at_time }}</td>
                                <td class="text-right">Rs.{{ number_format($advance->earned_salary_at_time, 2) }}</td>
                                <td class="text-center">
                                    @if($advance->status === 'given')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($advance->status === 'deducted')
                                        <span class="badge badge-success">Deducted</span>
                                        @if($advance->deducted_at)
                                            <br><small class="text-muted">{{ $advance->deducted_at->format('M d, H:i') }}</small>
                                        @endif
                                    @else
                                        <span class="badge badge-secondary">Cancelled</span>
                                    @endif
                                </td>
                                <td>{{ $advance->givenBy->name ?? '-' }}</td>
                                <td>
                                    {{ $advance->given_at->format('M d, Y') }}
                                    <br>
                                    <small class="text-muted">{{ $advance->given_at->format('h:i A') }}</small>
                                </td>
                                <td>
                                    @if($advance->status === 'given')
                                        @can('salary.manage')
                                        <form method="POST" action="{{ route('advances.cancel', $advance) }}" class="d-inline">
                                            @csrf
                                            <button type="button" class="btn btn-sm btn-danger" title="Cancel Advance" onclick="confirmCancel(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    @endif
                                    @if($advance->notes)
                                        <button type="button" class="btn btn-sm btn-info" title="View Notes" onclick="showNotes('{{ addslashes($advance->notes) }}')">
                                            <i class="fas fa-sticky-note"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                    No advance payments found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($advances->hasPages())
            <div class="card-footer">
                {{ $advances->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>
</section>

<script>
function confirmCancel(btn) {
    Swal.fire({
        title: 'Cancel Advance?',
        text: 'Are you sure you want to cancel this advance payment? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Cancel it',
        cancelButtonText: 'No, Keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            btn.closest('form').submit();
        }
    });
}

function showNotes(notes) {
    Swal.fire({
        title: 'Notes',
        text: notes,
        icon: 'info',
        confirmButtonColor: '#007bff'
    });
}
</script>
@endsection
