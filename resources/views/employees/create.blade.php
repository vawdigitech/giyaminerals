@extends('layouts.app')
@section('page_title', 'Add Employee')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active">Add Employee</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Add New Employee</h3>
                    </div>
                    <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="designation_id">Designation <span class="text-danger">*</span></label>
                                        <select class="form-control @error('designation_id') is-invalid @enderror"
                                            id="designation_id" name="designation_id" required>
                                            <option value="">-- Select Designation --</option>
                                            @foreach($designations as $designation)
                                                <option value="{{ $designation->id }}" data-code="{{ $designation->code }}" {{ old('designation_id') == $designation->id ? 'selected' : '' }}>
                                                    {{ $designation->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('designation_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted">Employee code will be auto-generated based on designation (e.g., MS0001 for Mason)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                            id="phone" name="phone" value="{{ old('phone') }}">
                                        @error('phone')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="photo">Photo</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input @error('photo') is-invalid @enderror"
                                                id="photo" name="photo" accept="image/*">
                                            <label class="custom-file-label" for="photo">Choose file</label>
                                        </div>
                                        @error('photo')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                        <div id="photo-preview" class="mt-2" style="display: none;">
                                            <img id="preview-image" src="" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employment_type">Employment Type <span class="text-danger">*</span></label>
                                        <select class="form-control @error('employment_type') is-invalid @enderror"
                                            id="employment_type" name="employment_type" required>
                                            <option value="permanent" {{ old('employment_type', 'permanent') == 'permanent' ? 'selected' : '' }}>Permanent</option>
                                            <option value="contract" {{ old('employment_type') == 'contract' ? 'selected' : '' }}>Contract</option>
                                            <option value="temporary" {{ old('employment_type') == 'temporary' ? 'selected' : '' }}>Temporary</option>
                                        </select>
                                        @error('employment_type')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="site_id">Assigned Site</label>
                                        <select class="form-control @error('site_id') is-invalid @enderror"
                                            id="site_id" name="site_id">
                                            <option value="">-- Not Assigned --</option>
                                            @foreach($sites as $site)
                                                <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                                    {{ $site->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('site_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="daily_rate">Daily Rate (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0"
                                            class="form-control @error('daily_rate') is-invalid @enderror"
                                            id="daily_rate" name="daily_rate" value="{{ old('daily_rate', '0.00') }}" required>
                                        @error('daily_rate')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="working_hours">Working Hours <span class="text-danger">*</span></label>
                                        <input type="number" step="0.5" min="1" max="24"
                                            class="form-control @error('working_hours') is-invalid @enderror"
                                            id="working_hours" name="working_hours" value="{{ old('working_hours', '8') }}" required>
                                        @error('working_hours')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted">Standard working hours per day (used for salary calculation)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-control @error('status') is-invalid @enderror"
                                    id="status" name="status" required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Save Employee</button>
                            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    // File input label update
    document.getElementById('photo').addEventListener('change', function(e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : 'Choose file';
        var label = this.nextElementSibling;
        label.textContent = fileName;

        // Show preview
        if (e.target.files[0]) {
            var reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('preview-image').src = event.target.result;
                document.getElementById('photo-preview').style.display = 'block';
            };
            reader.readAsDataURL(e.target.files[0]);
        } else {
            document.getElementById('photo-preview').style.display = 'none';
        }
    });
</script>
@endsection
