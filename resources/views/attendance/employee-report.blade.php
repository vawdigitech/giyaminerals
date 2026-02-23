@extends('layouts.app')
@section('page_title', 'Employee Attendance Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Attendance Report</a></li>
    <li class="breadcrumb-item active">{{ $employee->name }}</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Employee Info Card -->
        <div class="card card-primary card-outline mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>{{ $employee->name }}</h4>
                        <p class="text-muted mb-1">
                            <strong>Employee Code:</strong> {{ $employee->employee_code ?? '-' }}
                        </p>
                        <p class="text-muted mb-1">
                            <strong>Designation:</strong> {{ $employee->designation->name ?? '-' }}
                        </p>
                        <p class="text-muted mb-0">
                            <strong>Site:</strong> {{ $employee->site->name ?? '-' }}
                        </p>
                    </div>
                    <div class="col-md-6 text-md-right">
                        <span class="badge badge-{{ $employee->status == 'active' ? 'success' : 'secondary' }} badge-lg">
                            {{ ucfirst($employee->status ?? 'Unknown') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $summary['working_days'] }}</h3>
                        <p>Working Days</p>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $summary['present'] }}</h3>
                        <p>Present</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $summary['late'] }}</h3>
                        <p>Late</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $summary['absent'] }}</h3>
                        <p>Absent</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-times"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ number_format($summary['total_hours'], 1) }}</h3>
                        <p>Total Hours</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $summary['average_hours'] }}</h3>
                        <p>Avg Hours/Day</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>

        <!-- Overtime Summary -->
        @if(($summary['overtime_count'] ?? 0) > 0)
        <div class="row">
            <div class="col-lg-4 col-6">
                <div class="small-box bg-purple">
                    <div class="inner">
                        <h3>{{ $summary['overtime_count'] }}</h3>
                        <p>Days with Overtime</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="small-box bg-teal">
                    <div class="inner">
                        <h3>{{ number_format($summary['total_overtime_hours'] ?? 0, 1) }}</h3>
                        <p>Total OT Hours</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="small-box bg-olive">
                    <div class="inner">
                        <h3>Rs.{{ number_format($summary['total_overtime_salary'] ?? 0, 0) }}</h3>
                        <p>Total OT Bonus</p>
                    </div>
                    <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
        </div>
        @endif

        <!-- Date Filter -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('attendance.employee', $employee) }}" class="row g-3">
                    <div class="col-md-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">Filter</button>
                        <a href="{{ route('attendance.employee', $employee) }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Back Link -->
        <div class="mb-3">
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Attendance Report
            </a>
        </div>

        <!-- Attendance Records Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>Attendance Records
                    <small class="text-muted">({{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }})</small>
                </h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Site</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th class="text-center">Hours</th>
                            <th class="text-center">OT Hours</th>
                            <th class="text-center">OT Salary</th>
                            <th class="text-center">Daily Salary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            @php
                                $statusColors = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger'];
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y (D)') }}</td>
                                <td>{{ $attendance->site->name ?? '-' }}</td>
                                <td>{{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') : '-' }}</td>
                                <td>{{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') : '-' }}</td>
                                <td class="text-center">{{ $attendance->total_hours ? number_format($attendance->total_hours, 2) : '-' }}</td>
                                <td class="text-center">
                                    @if($attendance->overtime_applicable)
                                        <span class="badge badge-info">{{ number_format($attendance->overtime_hours, 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($attendance->overtime_applicable && $attendance->overtime_salary > 0)
                                        <strong class="text-info">Rs.{{ number_format($attendance->overtime_salary, 2) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($attendance->daily_salary)
                                        Rs.{{ number_format($attendance->daily_salary, 2) }}
                                        @if($attendance->overtime_applicable)
                                            <span class="badge badge-success badge-sm">OT</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $statusColors[$attendance->status] ?? 'secondary' }}">
                                        {{ ucfirst($attendance->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No attendance records found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($attendances->count() > 0)
                    <tfoot>
                        <tr class="bg-light font-weight-bold">
                            <td colspan="4" class="text-right">Total:</td>
                            <td class="text-center">{{ number_format($summary['total_hours'], 2) }}</td>
                            <td class="text-center">{{ number_format($summary['total_overtime_hours'] ?? 0, 2) }}</td>
                            <td class="text-center">Rs.{{ number_format($summary['total_overtime_salary'] ?? 0, 2) }}</td>
                            <td class="text-center">Rs.{{ number_format($attendances->sum('daily_salary'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
