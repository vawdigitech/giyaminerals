@extends('layouts.app')
@section('page_title', 'Give Advance')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('advances.index') }}">Advance Payments</a></li>
    <li class="breadcrumb-item active">Give Advance</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Form Card -->
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Give Advance Payment</h3>
                    </div>
                    <form method="POST" action="{{ route('advances.store') }}" id="advanceForm">
                        @csrf
                        <div class="card-body">
                            <!-- Current Week Info -->
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-calendar-week"></i>
                                <strong>Current Week:</strong> {{ $weekStart->format('M d') }} - {{ $weekEnd->format('M d, Y') }}
                            </div>

                            <!-- Employee Selection -->
                            <div class="form-group">
                                <label for="employee_id">Employee <span class="text-danger">*</span></label>
                                <select name="employee_id" id="employee_id" class="form-control @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            data-site="{{ $employee->site->name ?? '-' }}"
                                            data-designation="{{ $employee->designation->name ?? '-' }}"
                                            {{ old('employee_id', $selectedEmployee?->id) == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->name }} ({{ $employee->employee_code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Amount -->
                            <div class="form-group">
                                <label for="amount">Amount (Rs.) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    value="{{ old('amount') }}" required placeholder="Enter advance amount">
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted" id="maxAmountHint"></small>
                            </div>

                            <!-- Notes -->
                            <div class="form-group">
                                <label for="notes">Notes (Optional)</label>
                                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                                    rows="3" placeholder="Add any notes about this advance...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="fas fa-hand-holding-usd"></i> Give Advance
                            </button>
                            <a href="{{ route('advances.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Eligibility Info Card -->
            <div class="col-md-6">
                <div class="card" id="eligibilityCard">
                    <div class="card-header">
                        <h3 class="card-title">Eligibility Information</h3>
                    </div>
                    <div class="card-body" id="eligibilityContent">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-user-check fa-3x mb-3"></i>
                            <p>Select an employee to check eligibility</p>
                        </div>
                    </div>
                </div>

                <!-- Existing Advances Card (shown after employee selection) -->
                <div class="card" id="existingAdvancesCard" style="display: none;">
                    <div class="card-header">
                        <h3 class="card-title">Advances Given This Week</h3>
                    </div>
                    <div class="card-body p-0" id="existingAdvancesContent">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeSelect = document.getElementById('employee_id');
    const amountInput = document.getElementById('amount');
    const submitBtn = document.getElementById('submitBtn');
    const maxAmountHint = document.getElementById('maxAmountHint');

    let currentEligibility = null;

    employeeSelect.addEventListener('change', function() {
        if (this.value) {
            checkEligibility(this.value);
        } else {
            resetEligibilityDisplay();
        }
    });

    amountInput.addEventListener('input', function() {
        validateAmount();
    });

    function checkEligibility(employeeId) {
        document.getElementById('eligibilityContent').innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Checking eligibility...</p>
            </div>
        `;

        fetch(`{{ route('advances.check-eligibility') }}?employee_id=${employeeId}`)
            .then(response => response.json())
            .then(data => {
                currentEligibility = data.eligibility;
                displayEligibility(data.eligibility, data.existing_advances);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('eligibilityContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error checking eligibility. Please try again.
                    </div>
                `;
            });
    }

    function displayEligibility(eligibility, existingAdvances) {
        let html = '';

        // Eligibility status
        if (eligibility.is_eligible) {
            html += `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Eligible for Advance</strong>
                </div>
            `;
        } else {
            html += `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> <strong>Not Eligible</strong>
                    <br><small>${eligibility.reason}</small>
                </div>
            `;
        }

        // Details
        html += `
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Week Period</span>
                    <strong>${formatDate(eligibility.week_start_date)} - ${formatDate(eligibility.week_end_date)}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Days Worked</span>
                    <strong>${eligibility.days_worked} / ${eligibility.min_days_required} min</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Earned Salary</span>
                    <strong>Rs.${formatNumber(eligibility.earned_salary)}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Already Advanced</span>
                    <strong class="text-danger">Rs.${formatNumber(eligibility.total_advances_given)}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between bg-light">
                    <span><strong>Available for Advance</strong></span>
                    <strong class="text-success">Rs.${formatNumber(eligibility.available_amount)}</strong>
                </li>
            </ul>
        `;

        document.getElementById('eligibilityContent').innerHTML = html;

        // Update max amount hint
        if (eligibility.is_eligible) {
            maxAmountHint.innerHTML = `Maximum available: <strong>Rs.${formatNumber(eligibility.available_amount)}</strong>`;
            amountInput.max = eligibility.available_amount;
        } else {
            maxAmountHint.innerHTML = '';
        }

        // Display existing advances
        displayExistingAdvances(existingAdvances);

        // Enable/disable submit button
        validateAmount();
    }

    function displayExistingAdvances(advances) {
        const card = document.getElementById('existingAdvancesCard');
        const content = document.getElementById('existingAdvancesContent');

        if (advances && advances.length > 0) {
            let html = `
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Given At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            advances.forEach(advance => {
                const statusBadge = advance.status === 'given'
                    ? '<span class="badge badge-warning">Pending</span>'
                    : '<span class="badge badge-success">Deducted</span>';

                html += `
                    <tr>
                        <td>Rs.${formatNumber(advance.amount)}</td>
                        <td>${new Date(advance.given_at).toLocaleDateString()}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            content.innerHTML = html;
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    }

    function validateAmount() {
        if (!currentEligibility) {
            submitBtn.disabled = true;
            return;
        }

        const amount = parseFloat(amountInput.value) || 0;

        if (currentEligibility.is_eligible && amount > 0 && amount <= currentEligibility.available_amount) {
            submitBtn.disabled = false;
            amountInput.classList.remove('is-invalid');
        } else {
            submitBtn.disabled = true;
            if (amount > currentEligibility.available_amount) {
                amountInput.classList.add('is-invalid');
            }
        }
    }

    function resetEligibilityDisplay() {
        currentEligibility = null;
        document.getElementById('eligibilityContent').innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-user-check fa-3x mb-3"></i>
                <p>Select an employee to check eligibility</p>
            </div>
        `;
        document.getElementById('existingAdvancesCard').style.display = 'none';
        maxAmountHint.innerHTML = '';
        submitBtn.disabled = true;
    }

    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
    }

    // If employee is pre-selected, check eligibility on load
    if (employeeSelect.value) {
        checkEligibility(employeeSelect.value);
    }
});
</script>
@endsection
