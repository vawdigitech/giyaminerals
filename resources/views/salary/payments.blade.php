@extends('layouts.app')
@section('page_title', 'Salary Payment History')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('salary.index') }}">Weekly Salary</a></li>
    <li class="breadcrumb-item active">Payment History</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>Rs.{{ number_format($summary['total_pending'], 2) }}</h3>
                        <p>Pending Payments</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>Rs.{{ number_format($summary['total_paid'], 2) }}</h3>
                        <p>Total Paid</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $summary['pending_count'] }}</h3>
                        <p>Pending Records</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $summary['paid_count'] }}</h3>
                        <p>Paid Records</p>
                    </div>
                    <div class="icon"><i class="fas fa-receipt"></i></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('salary.payments') }}" class="row g-3">
                    <div class="col-md-2">
                        <label class="small">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small">Employee</label>
                        <select name="employee_id" class="form-control">
                            <option value="">All Employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small">Site</label>
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
                        <label class="small">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="small">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('salary.payments') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payment Records</h3>
                <div class="card-tools">
                    <a href="{{ route('salary.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-calendar-week"></i> Weekly Report
                    </a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Week Period</th>
                            <th>Employee</th>
                            <th>Site</th>
                            <th class="text-center">Days</th>
                            <th class="text-center">Hours</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center">Status</th>
                            <th>Paid Info</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>
                                    <strong>{{ $payment->week_start_date->format('M d') }} - {{ $payment->week_end_date->format('M d, Y') }}</strong>
                                </td>
                                <td>
                                    <a href="{{ route('salary.employee', ['employee' => $payment->employee->id, 'date' => $payment->week_start_date->format('Y-m-d')]) }}">
                                        {{ $payment->employee->name }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $payment->employee->employee_code }}</small>
                                </td>
                                <td>{{ $payment->employee->site->name ?? '-' }}</td>
                                <td class="text-center">{{ $payment->total_days }}</td>
                                <td class="text-center">{{ number_format($payment->total_hours, 2) }}</td>
                                <td class="text-right font-weight-bold">Rs.{{ number_format($payment->total_salary, 2) }}</td>
                                <td class="text-center">
                                    @if($payment->status === 'paid')
                                        <span class="badge badge-success">Paid</span>
                                    @elseif($payment->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->paid_at)
                                        <small>
                                            {{ $payment->paid_at->format('M d, Y H:i') }}
                                            <br>
                                            by {{ $payment->paidBy->name ?? 'Unknown' }}
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->status === 'pending')
                                        <form method="POST" action="{{ route('salary.mark-paid', $payment) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Mark as Paid" onclick="return confirm('Mark this payment as paid?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('salary.employee', ['employee' => $payment->employee->id, 'date' => $payment->week_start_date->format('Y-m-d')]) }}" class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                    No payment records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $payments->withQueryString()->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
