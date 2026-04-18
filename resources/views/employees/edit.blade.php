@extends('layouts.app')
@section('page_title', 'Edit Employee')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active">Edit Employee</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Edit Employee: {{ $employee->name }}</h3>
                    </div>
                    <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="employment_type" value="temporary">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employee_code">Employee Code</label>
                                        <input type="text" class="form-control" id="employee_code"
                                            value="{{ $employee->employee_code }}" readonly disabled>
                                        <small class="text-muted">Employee code cannot be changed</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $employee->name) }}" required>
                                        @error('name')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                            id="phone" name="phone" value="{{ old('phone', $employee->phone) }}">
                                        @error('phone')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id">Designation Category <span class="text-danger">*</span></label>
                                        <select class="form-control" id="category_id" required>
                                            <option value="">-- Select Category --</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ old('category_id', $employee->designation->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="designation_id">Designation <span class="text-danger">*</span></label>
                                        <select class="form-control @error('designation_id') is-invalid @enderror"
                                            id="designation_id" name="designation_id" required>
                                            <option value="">-- Select Designation --</option>
                                        </select>
                                        @error('designation_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="photo">Photo</label>
                                        @if($employee->photo)
                                            <div class="mb-2">
                                                <img src="{{ $employee->photo }}" alt="Current Photo" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
                                                <br><small class="text-muted">Current photo</small>
                                            </div>
                                        @endif
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input @error('photo') is-invalid @enderror"
                                                id="photo" name="photo" accept="image/*">
                                            <label class="custom-file-label" for="photo">{{ $employee->photo ? 'Change photo' : 'Choose file' }}</label>
                                        </div>
                                        @error('photo')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                        <div id="photo-preview" class="mt-2" style="display: none;">
                                            <img id="preview-image" src="" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                                            <br><small class="text-muted">New photo preview</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="site_id">Assigned Site</label>
                                        <select class="form-control @error('site_id') is-invalid @enderror"
                                            id="site_id" name="site_id">
                                            <option value="">-- Not Assigned --</option>
                                            @foreach($sites as $site)
                                                <option value="{{ $site->id }}"
                                                    {{ old('site_id', $employee->site_id) == $site->id ? 'selected' : '' }}>
                                                    {{ $site->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('site_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted">Assign to either a Site OR Factory, not both</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="factory_id">Assigned Factory</label>
                                        <select class="form-control @error('factory_id') is-invalid @enderror"
                                            id="factory_id" name="factory_id">
                                            <option value="">-- Not Assigned --</option>
                                            @foreach($factories as $factory)
                                                <option value="{{ $factory->id }}"
                                                    {{ old('factory_id', $employee->factory_id) == $factory->id ? 'selected' : '' }}>
                                                    {{ $factory->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('factory_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted">Assign to either a Site OR Factory, not both</small>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">
                            <h5 class="mb-3">Site/Regular Wage Rates</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="daily_rate">Daily Rate (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0"
                                            class="form-control @error('daily_rate') is-invalid @enderror"
                                            id="daily_rate" name="daily_rate"
                                            value="{{ old('daily_rate', $employee->daily_rate) }}" required>
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
                                            id="working_hours" name="working_hours"
                                            value="{{ old('working_hours', $employee->working_hours ?? 8) }}" required>
                                        @error('working_hours')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted">Standard working hours per day (used for salary calculation)</small>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">
                            <h5 class="mb-3">Factory Wage Rates (Optional)</h5>
                            <p class="text-muted small">If employee works at a factory, these rates will be used instead of the regular rates above.</p>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="factory_daily_rate">Factory Daily Rate (₹)</label>
                                        <input type="number" step="0.01" min="0"
                                            class="form-control @error('factory_daily_rate') is-invalid @enderror"
                                            id="factory_daily_rate" name="factory_daily_rate"
                                            value="{{ old('factory_daily_rate', $employee->factory_daily_rate) }}">
                                        @error('factory_daily_rate')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted">Daily wage when working at factory</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="factory_working_hours">Factory Working Hours</label>
                                        <input type="number" step="0.5" min="1" max="24"
                                            class="form-control @error('factory_working_hours') is-invalid @enderror"
                                            id="factory_working_hours" name="factory_working_hours"
                                            value="{{ old('factory_working_hours', $employee->factory_working_hours) }}">
                                        @error('factory_working_hours')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted">Standard hours per day at factory</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Status <span class="text-danger">*</span></label>
                                        <select class="form-control @error('status') is-invalid @enderror"
                                            id="status" name="status" required>
                                            <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('status')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Update Employee</button>
                            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
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

    const categorySelect = document.getElementById('category_id');
    const designationSelect = document.getElementById('designation_id');
    const currentDesignationId = "{{ old('designation_id', $employee->designation_id) }}";
    const currentCategoryId = "{{ old('category_id', $employee->designation->category_id ?? '') }}";

    function loadDesignations(categoryId, selectDesignationId = null) {
        designationSelect.innerHTML = '<option value="">-- Loading... --</option>';
        designationSelect.disabled = true;

        fetch(`/designations/by-category/${categoryId}`)
            .then(response => response.json())
            .then(function(designations) {
                designationSelect.innerHTML = '<option value="">-- Select Designation --</option>';
                designations.forEach(function(d) {
                    const option = document.createElement('option');
                    option.value = d.id;
                    option.textContent = d.name;
                    if (selectDesignationId && selectDesignationId == d.id) {
                        option.selected = true;
                    }
                    designationSelect.appendChild(option);
                });
                designationSelect.disabled = false;
            })
            .catch(function() {
                designationSelect.innerHTML = '<option value="">-- Error loading --</option>';
                designationSelect.disabled = false;
            });
    }

    categorySelect.addEventListener('change', function() {
        if (this.value) {
            loadDesignations(this.value);
        } else {
            designationSelect.innerHTML = '<option value="">-- Select Designation --</option>';
        }
    });

    // On page load, load designations for current category and select current designation
    if (currentCategoryId) {
        categorySelect.value = currentCategoryId;
        loadDesignations(currentCategoryId, currentDesignationId);
    }
</script>
@endpush
