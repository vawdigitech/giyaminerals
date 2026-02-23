@extends('layouts.app')
@section('page_title', 'Settings')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Settings</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clock mr-2"></i>Overtime Settings
                        </h3>
                    </div>
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="overtime_enabled">Overtime Calculation</label>
                                <div class="custom-control custom-switch">
                                    <input type="hidden" name="overtime_enabled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="overtime_enabled"
                                           name="overtime_enabled" value="1"
                                           {{ ($settings['overtime_enabled']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="overtime_enabled">
                                        Enable overtime calculation for attendance
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    When enabled, employees working beyond their standard hours plus the threshold will receive overtime bonus.
                                </small>
                                @error('overtime_enabled')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="overtime_threshold_hours">Overtime Threshold (Hours)</label>
                                <input type="number" step="0.5" min="0" max="24" class="form-control @error('overtime_threshold_hours') is-invalid @enderror"
                                       id="overtime_threshold_hours" name="overtime_threshold_hours"
                                       value="{{ old('overtime_threshold_hours', $settings['overtime_threshold_hours']->value ?? '2.5') }}">
                                <small class="form-text text-muted">
                                    Number of hours above standard working hours required to qualify for overtime.
                                    <br>Example: If standard is 8 hours and threshold is 2.5, employee must work 10.5+ hours to get overtime.
                                </small>
                                @error('overtime_threshold_hours')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="overtime_bonus_percentage">Overtime Bonus Percentage (%)</label>
                                <div class="input-group">
                                    <input type="number" step="1" min="0" max="200" class="form-control @error('overtime_bonus_percentage') is-invalid @enderror"
                                           id="overtime_bonus_percentage" name="overtime_bonus_percentage"
                                           value="{{ old('overtime_bonus_percentage', $settings['overtime_bonus_percentage']->value ?? '50') }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Bonus percentage of daily rate given when overtime applies.
                                    <br>Example: 50% means if daily rate is Rs.1000, overtime bonus is Rs.500.
                                </small>
                                @error('overtime_bonus_percentage')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            @can('settings.edit')
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>How Overtime Works
                        </h3>
                    </div>
                    <div class="card-body">
                        <h6>Calculation Formula:</h6>
                        <ol class="pl-3">
                            <li>Employee checks out after completing work</li>
                            <li>System calculates total hours worked</li>
                            <li>If <code>total_hours >= standard_hours + threshold</code>:</li>
                            <li>Overtime bonus = <code>daily_rate × bonus_percentage / 100</code></li>
                            <li>Final salary = base salary + overtime bonus</li>
                        </ol>

                        <hr>

                        <h6>Example:</h6>
                        <ul class="pl-3 mb-0">
                            <li>Standard hours: 8</li>
                            <li>Threshold: 2.5</li>
                            <li>Required for OT: 10.5 hours</li>
                            <li>Daily rate: Rs.1,000</li>
                            <li>Bonus (50%): Rs.500</li>
                            <li><strong>Total with OT: Rs.1,500+</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    @if(session('success'))
        toastr.success(@json(session('success')));
    @elseif(session('error'))
        toastr.error(@json(session('error')));
    @endif
</script>
@endpush
