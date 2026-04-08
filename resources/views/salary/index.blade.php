@extends('layouts.app')
@section('page_title', 'Weekly Salary Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Weekly Salary Report</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Week Navigation -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('salary.index') }}" class="d-flex gap-2">
                            <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
                            <select name="site_id" class="form-control" onchange="this.form.submit()">
                                <option value="">All Sites</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="col-md-4 text-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-week"></i>
                            Week: {{ $weekStart->format('M d') }} - {{ $weekEnd->format('M d, Y') }}
                        </h5>
                        <small class="text-muted">
                            @if($weekEnd->isFuture())
                                <span class="badge badge-info">Current Week</span>
                            @elseif($weekEnd->isToday() || $weekStart->diffInDays($weekEnd) <= 7)
                                <span class="badge badge-warning">Saturday is Salary Day</span>
                            @else
                                <span class="badge badge-secondary">Past Week</span>
                            @endif
                        </small>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="{{ route('salary.index', ['date' => $weekStart->copy()->subWeek()->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-chevron-left"></i> Previous Week
                        </a>
                        <a href="{{ route('salary.index', ['date' => $weekStart->copy()->addWeek()->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
                            Next Week <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ count($report) }}</h3>
                        <p>Employees with Work</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($grandTotalHours, 1) }}</h3>
                        <p>Total Hours</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>Rs.{{ number_format($grandTotalSalary, 2) }}</h3>
                        <p>Total Salary</p>
                    </div>
                    <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-{{ $pendingCount > 0 ? 'danger' : 'success' }}">
                    <div class="inner">
                        <h3>{{ $paidCount }} / {{ $paidCount + $pendingCount }}</h3>
                        <p>Paid / Total Payments</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mb-3">
            <form method="POST" action="{{ route('salary.generate') }}" class="d-inline" id="generatePaymentForm">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <button type="button" class="btn btn-primary" onclick="confirmGenerate()">
                    <i class="fas fa-file-invoice-dollar"></i> Generate Payment Records
                </button>
            </form>
            <a href="{{ route('salary.payments') }}" class="btn btn-outline-secondary">
                <i class="fas fa-history"></i> View Payment History
            </a>
            <a href="{{ route('advances.index') }}" class="btn btn-outline-info">
                <i class="fas fa-hand-holding-usd"></i> Advance Payments
            </a>
        </div>

        <!-- Weekly Report Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Weekly Salary Details</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <form method="POST" action="{{ route('salary.mark-multiple-paid') }}" id="bulkPaymentForm">
                    @csrf
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAll" onclick="toggleAll(this)">
                                </th>
                                <th>Employee</th>
                                <th>Designation</th>
                                <th>Site</th>
                                <th class="text-center">Days</th>
                                <th class="text-center">Hours</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Advance</th>
                                <th class="text-right">Net Payable</th>
                                <th class="text-center">Status</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report as $item)
                                <tr>
                                    <td>
                                        @if($item['payment'] && $item['payment']->status === 'pending')
                                            <input type="checkbox" name="payment_ids[]" value="{{ $item['payment']->id }}" class="payment-checkbox">
                                        @elseif(!$item['payment'])
                                            <input type="checkbox" class="employee-checkbox" value="{{ $item['employee']->id }}">
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('salary.employee', ['employee' => $item['employee']->id, 'date' => $date]) }}">
                                            <strong>{{ $item['employee']->name }}</strong>
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $item['employee']->employee_code }}</small>
                                    </td>
                                    <td>{{ $item['employee']->designation->name ?? '-' }}</td>
                                    <td>{{ $item['employee']->site->name ?? '-' }}</td>
                                    <td class="text-center">{{ $item['total_days'] }}</td>
                                    <td class="text-center">{{ number_format($item['total_hours'], 2) }}</td>
                                    <td class="text-right font-weight-bold">Rs.{{ number_format($item['total_salary'], 2) }}</td>
                                    <td class="text-right">
                                        @php
                                            $advanceAmount = $item['payment'] ? ($item['payment']->advance_deducted ?? 0) : \App\Models\AdvancePayment::getTotalAdvancesForWeek($item['employee']->id, $weekStart);
                                        @endphp
                                        @if($advanceAmount > 0)
                                            <span class="text-danger">Rs.{{ number_format($advanceAmount, 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        @php
                                            $netPayable = $item['total_salary'] - $advanceAmount;
                                        @endphp
                                        <span class="{{ $netPayable < $item['total_salary'] ? 'text-success' : '' }}">
                                            Rs.{{ number_format($netPayable, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($item['payment'])
                                            @if($item['payment']->status === 'paid')
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Paid
                                                </span>
                                                <br>
                                                <small class="text-muted">{{ $item['payment']->paid_at->format('M d, H:i') }}</small>
                                            @elseif($item['payment']->status === 'pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($item['payment']->status) }}</span>
                                            @endif
                                        @else
                                            <span class="badge badge-light">No Record</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item['payment'] && $item['payment']->status === 'pending')
                                            <form method="POST" action="{{ route('salary.mark-paid', $item['payment']) }}" class="d-inline mark-paid-form">
                                                @csrf
                                                <button type="button" class="btn btn-sm btn-success" title="Mark as Paid" onclick="confirmMarkPaid(this)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('salary.employee', ['employee' => $item['employee']->id, 'date' => $date]) }}" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        No attendance records found for this week.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($report) > 0)
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="4" class="text-right">Total:</td>
                                <td class="text-center">{{ collect($report)->sum('total_days') }}</td>
                                <td class="text-center">{{ number_format($grandTotalHours, 2) }}</td>
                                <td class="text-right">Rs.{{ number_format($grandTotalSalary, 2) }}</td>
                                <td class="text-right text-danger">
                                    @php
                                        $totalAdvances = \App\Models\AdvancePayment::forWeek($weekStart)->whereIn('status', ['given', 'deducted'])->sum('amount');
                                    @endphp
                                    Rs.{{ number_format($totalAdvances, 2) }}
                                </td>
                                <td class="text-right">Rs.{{ number_format($grandTotalSalary - $totalAdvances, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>

                    @if($pendingCount > 0)
                    <div class="card-footer">
                        <button type="button" class="btn btn-success" onclick="confirmMarkSelectedPaid()">
                            <i class="fas fa-check-double"></i> Mark Selected as Paid
                        </button>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.payment-checkbox, .employee-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = source.checked);
}

function confirmGenerate() {
    const selectedEmployees = document.querySelectorAll('.employee-checkbox:checked');
    const form = document.getElementById('generatePaymentForm');

    // Remove any previously added employee_ids hidden inputs
    form.querySelectorAll('input[name="employee_ids[]"]').forEach(el => el.remove());

    let text, title;
    if (selectedEmployees.length > 0) {
        title = 'Generate for Selected Employees?';
        text = `This will generate payment records for ${selectedEmployees.length} selected employee(s).`;
        selectedEmployees.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'employee_ids[]';
            input.value = cb.value;
            form.appendChild(input);
        });
    } else {
        title = 'Generate Payment Records?';
        text = 'This will generate payment records for all employees with attendance this week.';
    }

    Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Generate',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        } else {
            // Clean up if cancelled
            form.querySelectorAll('input[name="employee_ids[]"]').forEach(el => el.remove());
        }
    });
}

function confirmMarkPaid(btn) {
    Swal.fire({
        title: 'Mark as Paid?',
        text: 'Are you sure you want to mark this payment as paid?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Mark as Paid',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            btn.closest('form').submit();
        }
    });
}

function confirmMarkSelectedPaid() {
    const checkedBoxes = document.querySelectorAll('.payment-checkbox:checked');
    if (checkedBoxes.length === 0) {
        Swal.fire({
            title: 'No Selection',
            text: 'Please select at least one payment to mark as paid.',
            icon: 'warning',
            confirmButtonColor: '#007bff'
        });
        return;
    }

    Swal.fire({
        title: 'Mark Selected as Paid?',
        text: `Are you sure you want to mark ${checkedBoxes.length} payment(s) as paid?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Mark as Paid',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('bulkPaymentForm').submit();
        }
    });
}
</script>
@endsection
