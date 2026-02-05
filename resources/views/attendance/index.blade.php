@extends('layouts.app')
@section('page_title', 'Attendance Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Attendance Report</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <!-- <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $summary['present_count'] }}</h3>
                        <p>Present</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $summary['late_count'] }}</h3>
                        <p>Late</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $summary['absent_count'] }}</h3>
                        <p>Absent</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-times"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($summary['total_hours'], 1) }}</h3>
                        <p>Total Hours</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
        </div> -->

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('attendance.index') }}" class="row g-3">
                    <div class="col-md-2">
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-2">
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
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                            <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                            <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('attendance.export', request()->all()) }}" class="btn btn-success">
                            <i class="fas fa-download"></i> Export
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mb-3">
            <a href="{{ route('attendance.daily') }}" class="btn btn-outline-primary">
                <i class="fas fa-calendar-day"></i> Today's Attendance
            </a>
        </div>

        <!-- Attendance Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Attendance Records</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Site</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('attendance.employee', $attendance->employee) }}">
                                        {{ $attendance->employee->name ?? '-' }}
                                    </a>
                                    <br><small class="text-muted">{{ $attendance->employee->employee_code ?? '' }}</small>
                                </td>
                                <td>{{ $attendance->site->name ?? '-' }}</td>
                                <td>{{ $attendance->check_in_time ?? '-' }}</td>
                                <td>{{ $attendance->check_out_time ?? '-' }}</td>
                                <td>{{ $attendance->total_hours ? number_format($attendance->total_hours, 2) : '-' }}</td>
                                <td>
                                    @php
                                        $statusColors = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger'];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$attendance->status] ?? 'secondary' }}">
                                        {{ ucfirst($attendance->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $attendances->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
