@extends('layouts.app')
@section('page_title', 'Employee Details')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active">{{ $employee->name }}</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Employee Info Card -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center"
                                style="width: 80px; height: 80px;">
                                <span class="text-white" style="font-size: 2rem;">
                                    {{ strtoupper(substr($employee->name, 0, 2)) }}
                                </span>
                            </div>
                        </div>
                        <h3 class="profile-username text-center mt-3">{{ $employee->name }}</h3>
                        <p class="text-muted text-center">{{ ucfirst($employee->role) }}</p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Employee Code</b> <a class="float-right">{{ $employee->employee_code }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Phone</b> <a class="float-right">{{ $employee->phone ?? '-' }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Hourly Rate</b> <a class="float-right">${{ number_format($employee->hourly_rate, 2) }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Site</b>
                                <span class="float-right">
                                    @if($derivedSite)
                                        {{ $derivedSite->name }}
                                        @if(!$employee->site)
                                            <small class="text-muted">(from project)</small>
                                        @endif
                                    @else
                                        Not Assigned
                                    @endif
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Status</b>
                                <span class="float-right badge badge-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($employee->status) }}
                                </span>
                            </li>
                        </ul>

                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-block">
                            <i class="fas fa-edit"></i> Edit Employee
                        </a>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 text-center border-right">
                                <h4 class="text-primary">{{ number_format($totalHoursWorked, 1) }}</h4>
                                <small class="text-muted">Total Hours</small>
                            </div>
                            <div class="col-6 text-center">
                                <h4 class="text-success">${{ number_format($totalEarnings, 2) }}</h4>
                                <small class="text-muted">Total Earnings</small>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <h4 class="text-info">{{ $activeTasks }}</h4>
                            <small class="text-muted">Active Task Assignments</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Recent Attendance -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Attendance</h3>
                        <div class="card-tools">
                            <a href="{{ route('attendance.employee', $employee) }}" class="btn btn-sm btn-primary">
                                View Full Report
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->attendances as $attendance)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                                        <td>{{ $attendance->check_in_time ?? '-' }}</td>
                                        <td>{{ $attendance->check_out_time ?? '-' }}</td>
                                        <td>{{ $attendance->total_hours ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $attendance->status === 'present' ? 'success' : ($attendance->status === 'late' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($attendance->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No attendance records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Task Assignments -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Task Assignments</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Project</th>
                                    <th>Assigned</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->taskAssignments as $assignment)
                                    <tr>
                                        <td>{{ $assignment->task->name ?? '-' }}</td>
                                        <td>{{ $assignment->task->project->name ?? '-' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($assignment->assigned_at)->format('M d, Y') }}</td>
                                        <td>
                                            @if($assignment->removed_at)
                                                <span class="badge badge-secondary">Removed</span>
                                            @else
                                                <span class="badge badge-success">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No task assignments found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
