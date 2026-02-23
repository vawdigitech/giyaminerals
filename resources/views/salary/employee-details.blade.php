@extends('layouts.app')
@section('page_title', 'Employee Salary Details')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('salary.index') }}">Weekly Salary</a></li>
    <li class="breadcrumb-item active">{{ $employee->name }}</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Week Navigation -->
        <div class="row mb-3">
            <div class="col-md-6">
                <a href="{{ route('salary.employee', ['employee' => $employee->id, 'date' => $weekStart->copy()->subWeek()->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-left"></i> Previous Week
                </a>
                <a href="{{ route('salary.employee', ['employee' => $employee->id, 'date' => $weekStart->copy()->addWeek()->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
                    Next Week <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{ route('salary.index', ['date' => $date]) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Weekly Report
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Employee Info -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            @if($employee->photo)
                                <img class="profile-user-img img-fluid img-circle" src="{{ $employee->photo }}" alt="Employee Photo" style="width: 100px; height: 100px; object-fit: cover;">
                            @else
                                <div class="profile-user-img img-fluid img-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                                    <span class="text-white h3 mb-0">{{ strtoupper(substr($employee->name, 0, 2)) }}</span>
                                </div>
                            @endif
                        </div>

                        <h3 class="profile-username text-center">{{ $employee->name }}</h3>
                        <p class="text-muted text-center">{{ $employee->designation->name ?? '-' }}</p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Employee Code</b> <a class="float-right">{{ $employee->employee_code }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Site</b> <a class="float-right">{{ $employee->site->name ?? '-' }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Daily Rate</b> <a class="float-right">Rs.{{ number_format($employee->daily_rate, 2) }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Working Hours</b> <a class="float-right">{{ $employee->working_hours ?? 8 }} hrs</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Payment History</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($paymentHistory as $history)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">{{ $history->week_start_date->format('M d') }} - {{ $history->week_end_date->format('M d') }}</small>
                                        <br>
                                        <strong>Rs.{{ number_format($history->total_salary, 2) }}</strong>
                                    </div>
                                    @if($history->status === 'paid')
                                        <span class="badge badge-success">Paid</span>
                                    @elseif($history->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($history->status) }}</span>
                                    @endif
                                </li>
                            @empty
                                <li class="list-group-item text-center text-muted">No payment history</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Weekly Details -->
            <div class="col-md-8">
                <!-- Week Summary Card -->
                @php
                    $weeklyAdvances = \App\Models\AdvancePayment::forEmployee($employee->id)
                        ->forWeek($weekStart)
                        ->whereIn('status', ['given', 'deducted'])
                        ->with('givenBy')
                        ->orderBy('given_at', 'desc')
                        ->get();
                    $totalAdvance = $weeklyAdvances->sum('amount');
                    $netPayable = $summary['total_salary'] - $totalAdvance;
                @endphp
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-week"></i>
                            Week: {{ $weekStart->format('M d') }} - {{ $weekEnd->format('M d, Y') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center border-right">
                                <h2 class="mb-0 text-primary">{{ $summary['total_days'] }}</h2>
                                <small class="text-muted">Days Worked</small>
                            </div>
                            <div class="col-md-3 text-center border-right">
                                <h2 class="mb-0 text-info">{{ number_format($summary['total_hours'], 2) }}</h2>
                                <small class="text-muted">Total Hours</small>
                            </div>
                            <div class="col-md-3 text-center border-right">
                                <h2 class="mb-0 text-success">Rs.{{ number_format($summary['total_salary'], 2) }}</h2>
                                <small class="text-muted">Earned Salary</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h2 class="mb-0 {{ $totalAdvance > 0 ? 'text-warning' : 'text-success' }}">Rs.{{ number_format($netPayable, 2) }}</h2>
                                <small class="text-muted">Net Payable</small>
                            </div>
                        </div>
                        @if($totalAdvance > 0)
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Earned Salary:</span>
                                    <strong>Rs.{{ number_format($summary['total_salary'], 2) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-danger">
                                    <span>Advance Taken:</span>
                                    <strong>- Rs.{{ number_format($totalAdvance, 2) }}</strong>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Net Payable:</strong>
                                    <strong class="text-success">Rs.{{ number_format($netPayable, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        @if($payment)
                            @if($payment->status === 'paid')
                                <span class="badge badge-success p-2">
                                    <i class="fas fa-check-circle"></i> Payment Completed
                                </span>
                                <span class="text-muted ml-2">
                                    Paid on {{ $payment->paid_at->format('M d, Y H:i') }}
                                    by {{ $payment->paidBy->name ?? 'Unknown' }}
                                </span>
                                @if($payment->advance_deducted > 0)
                                    <span class="badge badge-info ml-2">
                                        Rs.{{ number_format($payment->advance_deducted, 2) }} Advance Deducted
                                    </span>
                                @endif
                            @elseif($payment->status === 'pending')
                                <form method="POST" action="{{ route('salary.mark-paid', $payment) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Mark this payment as paid?{{ $totalAdvance > 0 ? ' Rs.' . number_format($totalAdvance, 2) . ' advance will be deducted.' : '' }}')">
                                        <i class="fas fa-check"></i> Mark as Paid
                                    </button>
                                </form>
                                <span class="badge badge-warning ml-2">Payment Pending</span>
                                @can('salary.manage')
                                <a href="{{ route('advances.create', ['employee_id' => $employee->id]) }}" class="btn btn-info ml-2">
                                    <i class="fas fa-hand-holding-usd"></i> Give Advance
                                </a>
                                @endcan
                            @endif
                        @else
                            <span class="badge badge-light p-2">No payment record generated yet</span>
                            @can('salary.manage')
                            <a href="{{ route('advances.create', ['employee_id' => $employee->id]) }}" class="btn btn-info ml-2">
                                <i class="fas fa-hand-holding-usd"></i> Give Advance
                            </a>
                            @endcan
                        @endif
                    </div>
                </div>

                <!-- Daily Attendance Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daily Attendance Breakdown</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th class="text-center">Hours</th>
                                    <th class="text-right">Daily Rate</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $attendance)
                                    <tr>
                                        <td>
                                            <strong>{{ $attendance->date->format('D') }}</strong>
                                            <br>
                                            <small>{{ $attendance->date->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            {{ $attendance->check_in_time ? $attendance->check_in_time->format('h:i A') : '-' }}
                                        </td>
                                        <td>
                                            {{ $attendance->check_out_time ? $attendance->check_out_time->format('h:i A') : '-' }}
                                        </td>
                                        <td class="text-center">{{ number_format($attendance->total_hours, 2) }}</td>
                                        <td class="text-right">
                                            Rs.{{ number_format($attendance->daily_rate_at_time ?? $employee->daily_rate, 2) }}
                                            <br>
                                            <small class="text-muted">/ {{ $attendance->working_hours_at_time ?? 8 }} hrs</small>
                                        </td>
                                        <td class="text-right font-weight-bold">Rs.{{ number_format($attendance->daily_salary, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                            No attendance records for this week.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($attendances) > 0)
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="3" class="text-right">Total:</td>
                                    <td class="text-center">{{ number_format($summary['total_hours'], 2) }}</td>
                                    <td class="text-right">-</td>
                                    <td class="text-right">Rs.{{ number_format($summary['total_salary'], 2) }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Advances Section -->
                @if($weeklyAdvances->count() > 0)
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-hand-holding-usd"></i>
                            Advances Given This Week
                        </h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date Given</th>
                                    <th class="text-right">Amount</th>
                                    <th>Given By</th>
                                    <th class="text-center">Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($weeklyAdvances as $advance)
                                    <tr>
                                        <td>{{ $advance->given_at->format('M d, Y h:i A') }}</td>
                                        <td class="text-right font-weight-bold">Rs.{{ number_format($advance->amount, 2) }}</td>
                                        <td>{{ $advance->givenBy->name ?? '-' }}</td>
                                        <td class="text-center">
                                            @if($advance->status === 'given')
                                                <span class="badge badge-warning">Pending Deduction</span>
                                            @elseif($advance->status === 'deducted')
                                                <span class="badge badge-success">Deducted</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($advance->status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $advance->notes ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td class="text-right">Total Advance:</td>
                                    <td class="text-right text-danger">Rs.{{ number_format($totalAdvance, 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
