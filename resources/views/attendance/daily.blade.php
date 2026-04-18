@extends('layouts.app')
@section('page_title', "Today's Attendance")
@section('breadcrumb')
    @php
        $currentRoute = Route::currentRouteName();
        $indexRoute = str_contains($currentRoute, 'factory') ? 'factory-attendance.index' : 'site-attendance.index';
        $reportTitle = str_contains($currentRoute, 'factory') ? 'Factory Attendance Report' : 'Site Attendance Report';
    @endphp
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}">{{ $reportTitle }}</a></li>
    <li class="breadcrumb-item active">Daily Attendance</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $summary['total_employees'] }}</h3>
                        <p>Total Employees</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $summary['present'] }}</h3>
                        <p>Present</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $summary['late'] }}</h3>
                        <p>Late</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $summary['absent'] }}</h3>
                        <p>Absent</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-times"></i></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                @php
                    $currentRoute = Route::currentRouteName();
                    $dailyRoute = str_contains($currentRoute, 'factory') ? 'factory-attendance.daily' : 'site-attendance.daily';
                @endphp
                <form method="GET" action="{{ route($dailyRoute) }}" class="row g-3">
                    <div class="col-md-3">
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                    </div>
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
                    <div class="col-md-3">
                        <select name="factory_id" class="form-control">
                            <option value="">All Factories</option>
                            @foreach($factories as $factory)
                                <option value="{{ $factory->id }}" {{ request('factory_id') == $factory->id ? 'selected' : '' }}>
                                    {{ $factory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route($dailyRoute) }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Back Link -->
        <div class="mb-3">
            @php
                $currentRoute = Route::currentRouteName();
                $indexRoute = str_contains($currentRoute, 'factory') ? 'factory-attendance.index' : 'site-attendance.index';
                $reportTitle = str_contains($currentRoute, 'factory') ? 'Factory Attendance Report' : 'Site Attendance Report';
            @endphp
            <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to {{ $reportTitle }}
            </a>
        </div>

        <!-- Present Employees Table -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-user-check mr-2"></i>Present Employees ({{ $attendances->count() }})</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Location</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th class="text-center">Hours</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                <td>
                                    <a href="{{ route('attendance.employee', $attendance->employee) }}">
                                        {{ $attendance->employee->name ?? '-' }}
                                    </a>
                                    <br><small class="text-muted">{{ $attendance->employee->employee_code ?? '' }}</small>
                                </td>
                                <td>
                                    @if($attendance->factory_id)
                                        <span class="badge badge-primary">Factory</span>
                                        {{ $attendance->factory->name ?? '-' }}
                                    @elseif($attendance->site_id)
                                        <span class="badge badge-info">Site</span>
                                        {{ $attendance->site->name ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') : '-' }}</td>
                                <td>{{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') : '-' }}</td>
                                <td class="text-center">{{ $attendance->total_hours ? number_format($attendance->total_hours, 2) : '-' }}</td>
                                <td>
                                    @php
                                        $statusColors = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger'];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$attendance->status] ?? 'secondary' }}">
                                        {{ ucfirst($attendance->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#detailsModal{{ $attendance->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No attendance records for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Absent Employees Table -->
        @if($absentEmployees->count() > 0)
        <div class="card mt-4">
            <div class="card-header bg-danger text-white">
                <h3 class="card-title"><i class="fas fa-user-times mr-2"></i>Absent Employees ({{ $absentEmployees->count() }})</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Employee Code</th>
                            <th>Employee Name</th>
                            <th>Location</th>
                            <th>Position</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($absentEmployees as $employee)
                            <tr>
                                <td>{{ $employee->employee_code }}</td>
                                <td>
                                    <a href="{{ route('attendance.employee', $employee) }}">
                                        {{ $employee->name }}
                                    </a>
                                </td>
                                <td>
                                    @if($employee->factory_id)
                                        <span class="badge badge-primary">Factory</span>
                                        {{ $employee->factory->name ?? '-' }}
                                    @elseif($employee->site_id)
                                        <span class="badge badge-info">Site</span>
                                        {{ $employee->site->name ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $employee->position ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Attendance Details Modals -->
    @foreach($attendances as $attendance)
        @php
            $statusColors = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger'];
        @endphp
        <div class="modal fade" id="detailsModal{{ $attendance->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Attendance Details - {{ $attendance->employee->name ?? 'N/A' }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">Date:</th>
                                        <td>{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Employee:</th>
                                        <td>{{ $attendance->employee->name ?? '-' }} ({{ $attendance->employee->employee_code ?? '' }})</td>
                                    </tr>
                                    <tr>
                                        <th>Location:</th>
                                        <td>
                                            @if($attendance->factory_id)
                                                <span class="badge badge-primary">Factory</span> {{ $attendance->factory->name ?? '-' }}
                                            @elseif($attendance->site_id)
                                                <span class="badge badge-info">Site</span> {{ $attendance->site->name ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Check In:</th>
                                        <td>{{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Check Out:</th>
                                        <td>{{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Hours:</th>
                                        <td>{{ $attendance->total_hours ? number_format($attendance->total_hours, 2) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Daily Salary:</th>
                                        <td>{{ $attendance->daily_salary ? 'Rs.' . number_format($attendance->daily_salary, 2) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge badge-{{ $statusColors[$attendance->status] ?? 'secondary' }}">
                                                {{ ucfirst($attendance->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($attendance->notes)
                                    <tr>
                                        <th>Notes:</th>
                                        <td>{{ $attendance->notes }}</td>
                                    </tr>
                                    @endif
                                    @php
                                        $completedBreaks = $attendance->breaks->filter(fn($b) => !is_null($b->break_in_time));
                                        $totalBreakMins = $completedBreaks->sum(fn($b) =>
                                            abs(\Carbon\Carbon::parse($b->break_out_time)->diffInMinutes(\Carbon\Carbon::parse($b->break_in_time)))
                                        );
                                    @endphp
                                    @if($attendance->breaks->count() > 0)
                                    <tr>
                                        <th>Break Duration:</th>
                                        <td>
                                            @if($totalBreakMins > 0)
                                                @if(floor($totalBreakMins / 60) > 0)
                                                    {{ floor($totalBreakMins / 60) }}h {{ $totalBreakMins % 60 }}m
                                                @else
                                                    {{ $totalBreakMins }}m
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Break Details:</th>
                                        <td>
                                            @foreach($attendance->breaks as $break)
                                                <div class="small {{ !$loop->last ? 'mb-1' : '' }}">
                                                    <span class="text-muted">Break {{ $loop->iteration }}:</span>
                                                    {{ \Carbon\Carbon::parse($break->break_out_time)->format('h:i A') }}
                                                    @if($break->break_in_time)
                                                        &rarr; {{ \Carbon\Carbon::parse($break->break_in_time)->format('h:i A') }}
                                                        <span class="badge badge-secondary">
                                                            {{ abs(\Carbon\Carbon::parse($break->break_out_time)->diffInMinutes(\Carbon\Carbon::parse($break->break_in_time))) }}m
                                                        </span>
                                                    @else
                                                        <span class="badge badge-warning">On break</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-6">
                                        <h6 class="text-center mb-2">Check In Photo</h6>
                                        @if($attendance->check_in_photo)
                                            @php
                                                $checkInPhoto = $attendance->check_in_photo;
                                                $checkInSrc = str_starts_with($checkInPhoto, 'data:') ? $checkInPhoto : 'data:image/jpeg;base64,' . $checkInPhoto;
                                            @endphp
                                            <img src="{{ $checkInSrc }}" class="img-fluid img-thumbnail" alt="Check In Photo">
                                        @else
                                            <div class="text-center text-muted py-4 border rounded bg-light">
                                                <i class="fas fa-image fa-3x mb-2"></i>
                                                <p class="mb-0">No photo</p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-center mb-2">Check Out Photo</h6>
                                        @if($attendance->check_out_photo)
                                            @php
                                                $checkOutPhoto = $attendance->check_out_photo;
                                                $checkOutSrc = str_starts_with($checkOutPhoto, 'data:') ? $checkOutPhoto : 'data:image/jpeg;base64,' . $checkOutPhoto;
                                            @endphp
                                            <img src="{{ $checkOutSrc }}" class="img-fluid img-thumbnail" alt="Check Out Photo">
                                        @else
                                            <div class="text-center text-muted py-4 border rounded bg-light">
                                                <i class="fas fa-image fa-3x mb-2"></i>
                                                <p class="mb-0">No photo</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</section>
@endsection
