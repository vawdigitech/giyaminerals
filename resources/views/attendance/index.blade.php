@extends('layouts.app')
@section('page_title', 'Attendance Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Attendance Report</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('success') }}
            </div>
        @endif

        @if(session('import_errors') && count(session('import_errors')))
            <div class="alert alert-warning alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>Some rows were skipped:</strong>
                <ul class="mb-0 mt-1">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                @foreach($errors->all() as $error)
                    <p class="mb-0">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $summary['present_count'] }}</h3>
                        <p>Present</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($summary['total_hours'], 1) }}</h3>
                        <p>Total Hours</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>Rs.{{ number_format($summary['total_salary'], 0) }}</h3>
                        <p>Total Salary</p>
                    </div>
                    <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-purple">
                    <div class="inner">
                        <h3>Rs.{{ number_format($summary['total_overtime_salary'] ?? 0, 0) }}</h3>
                        <p>Overtime Bonus</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $summary['total_records'] }}</h3>
                        <p>Total Records</p>
                    </div>
                    <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                @php
                    $currentRoute = Route::currentRouteName();
                    $indexRoute = str_contains($currentRoute, 'factory') ? 'factory-attendance.index' : 'site-attendance.index';
                @endphp
                <form method="GET" action="{{ route($indexRoute) }}" class="row g-3">
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
                        <select name="factory_id" class="form-control">
                            <option value="">All Factories</option>
                            @foreach($factories as $factory)
                                <option value="{{ $factory->id }}" {{ request('factory_id') == $factory->id ? 'selected' : '' }}>
                                    {{ $factory->name }}
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
                        @php
                            $exportRoute = str_contains($currentRoute, 'factory') ? 'factory-attendance.export' : 'site-attendance.export';
                        @endphp
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route($exportRoute, request()->all()) }}" class="btn btn-success">
                            <i class="fas fa-download"></i> Export
                        </a>
                        @can('attendance.export')
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#importModal">
                            <i class="fas fa-file-upload"></i> Import
                        </button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mb-3">
            @php
                $createRoute = str_contains($currentRoute, 'factory') ? 'factory-attendance.create' : 'site-attendance.create';
                $dailyRoute = str_contains($currentRoute, 'factory') ? 'factory-attendance.daily' : 'site-attendance.daily';
            @endphp
            @can('attendance.create')
            <a href="{{ route($createRoute) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Attendance
            </a>
            @endcan
            <a href="{{ route($dailyRoute) }}" class="btn btn-outline-primary">
                <i class="fas fa-calendar-day"></i> Today's Attendance
            </a>
            <a href="{{ route('salary.index') }}" class="btn btn-outline-success">
                <i class="fas fa-money-bill-wave"></i> Weekly Salary Report
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
                            <th>Location</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th class="text-center">Hours</th>
                            <th class="text-center">OT Hours</th>
                            <th class="text-right">OT Salary</th>
                            <th class="text-right">Daily Salary</th>
                            <th>Status</th>
                            <th>Marked By</th>
                            <th class="text-center">Actions</th>
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
                                <td class="text-center">
                                    @if($attendance->overtime_applicable)
                                        <span class="badge badge-info">{{ number_format($attendance->overtime_hours, 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($attendance->overtime_applicable && $attendance->overtime_salary > 0)
                                        <strong class="text-info">Rs.{{ number_format($attendance->overtime_salary, 2) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($attendance->daily_salary)
                                        <strong class="text-success">Rs.{{ number_format($attendance->daily_salary, 2) }}</strong>
                                        @if($attendance->overtime_applicable)
                                            <br><span class="badge badge-success badge-sm">OT</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColors = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger'];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$attendance->status] ?? 'secondary' }}">
                                        {{ ucfirst($attendance->status) }}
                                    </span>
                                </td>
                                <td>{{ $attendance->markedBy->name ?? '-' }}</td>
                                <td class="text-center">
                                    @php
                                        $editRoute = str_contains(Route::currentRouteName(), 'factory') ? 'factory-attendance.edit' : 'site-attendance.edit';
                                        $destroyRoute = str_contains(Route::currentRouteName(), 'factory') ? 'factory-attendance.destroy' : 'site-attendance.destroy';
                                    @endphp
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#detailsModal{{ $attendance->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @can('attendance.edit')
                                    <a href="{{ route($editRoute, $attendance) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('attendance.delete')
                                    <form action="{{ route($destroyRoute, $attendance) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this attendance record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">No attendance records found.</td>
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

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-upload mr-2"></i>Import Attendance from Excel</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @php
                    $importRoute = str_contains(Route::currentRouteName(), 'factory') ? 'factory-attendance.import' : 'site-attendance.import';
                    $templateRoute = str_contains(Route::currentRouteName(), 'factory') ? 'factory-attendance.template' : 'site-attendance.template';
                @endphp
                <form action="{{ route($importRoute) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            Upload an Excel (.xlsx, .xls) or CSV file to import old attendance records.
                            <br>
                            Required columns: <strong>Date, Employee Code, Status</strong> and either <strong>Site Name</strong> or <strong>Factory Name</strong>
                            <br>
                            Optional columns: <strong>Check In, Check Out, Notes</strong>
                            <br><br>
                            <a href="{{ route($templateRoute) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download mr-1"></i> Download Sample Template
                            </a>
                        </div>
                        <div class="form-group">
                            <label for="importFile">Select File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="importFile" name="file"
                                    accept=".xlsx,.xls,.csv" required>
                                <label class="custom-file-label" for="importFile">Choose file...</label>
                            </div>
                            <small class="text-muted">Accepted formats: .xlsx, .xls, .csv — Max 10MB</small>
                        </div>
                        <div class="form-group mb-0">
                            <p class="mb-1 font-weight-bold text-muted small">Notes:</p>
                            <ul class="small text-muted mb-0">
                                <li>Duplicate records (same employee + date) will be skipped automatically.</li>
                                <li>Employee Code must match exactly as entered in the system.</li>
                                <li>Fill EITHER Site Name OR Factory Name, not both. Both are case-insensitive.</li>
                                <li>Status must be one of: <code>present</code>, <code>late</code>, <code>absent</code></li>
                                <li>Times should be in <code>HH:MM</code> (24-hour) format, e.g. <code>09:00</code></li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-upload mr-1"></i> Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
                                    @if($attendance->overtime_applicable)
                                    <tr>
                                        <th>Overtime:</th>
                                        <td>
                                            <span class="badge badge-success">Applicable</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>OT Hours:</th>
                                        <td>{{ number_format($attendance->overtime_hours, 2) }} hrs</td>
                                    </tr>
                                    <tr>
                                        <th>OT Bonus:</th>
                                        <td class="text-info font-weight-bold">Rs.{{ number_format($attendance->overtime_salary, 2) }}</td>
                                    </tr>
                                    @endif
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

@push('scripts')
<script>
    document.getElementById('importFile').addEventListener('change', function () {
        var fileName = this.files[0] ? this.files[0].name : 'Choose file...';
        this.nextElementSibling.textContent = fileName;
    });
</script>
@endpush
