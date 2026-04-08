@extends('layouts.app')
@section('page_title', 'Import Tasks')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Tasks</a></li>
    <li class="breadcrumb-item active">Import Tasks</li>
@endsection

@section('content')
<section class="content">
    <div class="container-fluid">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
            @if(session('import_errors') && count(session('import_errors')) > 0)
                <div class="alert alert-warning alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Row errors during import:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach(session('import_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                @foreach($errors->all() as $error)
                    <div><i class="fas fa-exclamation-circle mr-1"></i> {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="row">
            <!-- Import Form -->
            <div class="col-md-7">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-import mr-1"></i> Import Tasks from Excel</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('tasks.import.submit') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="file">Excel File <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="file" name="file"
                                               accept=".xlsx,.xls" required>
                                        <label class="custom-file-label" for="file">Choose .xlsx or .xls file</label>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Maximum file size: 10 MB. Only .xlsx and .xls files are accepted.</small>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="update_existing"
                                           name="update_existing" value="1">
                                    <label class="custom-control-label" for="update_existing">
                                        Update existing tasks (if task code already exists in the project)
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    If unchecked, rows matching an existing task code are skipped.
                                    If checked, the existing task's fields are overwritten with the imported values.
                                </small>
                            </div>

                            <hr>

                            <div class="d-flex align-items-center">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-upload mr-1"></i> Import Tasks
                                </button>
                                <a href="{{ route('tasks.import.template') }}" class="btn btn-success mr-2">
                                    <i class="fas fa-file-excel mr-1"></i> Download Template
                                </a>
                                <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="col-md-5">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> How to Import</h3>
                    </div>
                    <div class="card-body">
                        <ol class="pl-3" style="font-size:13px; line-height:1.8;">
                            <li>Click <strong>Download Template</strong> to get the Excel template.</li>
                            <li>Fill in your tasks in the <code>Tasks</code> sheet. See the <code>Instructions</code> sheet for column details.</li>
                            <li>For <strong>subtasks</strong>, put the parent's Task Code in the <em>Parent Task Code</em> column.</li>
                            <li>
                                Supported statuses: <code>pending</code>, <code>in_progress</code>,
                                <code>completed</code>, <code>on_hold</code>.
                            </li>
                            <li>
                                Supported priorities: <code>low</code>, <code>medium</code>,
                                <code>high</code>, <code>critical</code>.
                            </li>
                            <li>Dates must be in <code>YYYY-MM-DD</code> format.</li>
                            <li>
                                <strong>Labor Cost</strong> and <strong>Material Cost</strong> columns
                                are for <em>historical</em> data only — they set the task's stored cost
                                directly without touching live sessions or stock.
                            </li>
                            <li>After import, project progress and status are automatically recalculated.</li>
                        </ol>
                    </div>
                </div>

                <!-- Column Reference -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Required Columns</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Required</th>
                                </tr>
                            </thead>
                            <tbody style="font-size:12px;">
                                <tr><td>Project Code</td><td><span class="badge badge-danger">Yes</span></td></tr>
                                <tr><td>Task Code</td><td><span class="badge badge-danger">Yes</span></td></tr>
                                <tr><td>Task Name</td><td><span class="badge badge-danger">Yes</span></td></tr>
                                <tr><td>Parent Task Code</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Section</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Priority</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Status</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Progress (0-100)</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Quoted Amount</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Labor Cost</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Material Cost</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Start Date</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Due Date</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Completed Date</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Description</td><td><span class="badge badge-secondary">No</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Show filename in custom file input label
    document.getElementById('file').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : 'Choose .xlsx or .xls file';
        this.nextElementSibling.textContent = fileName;
    });
</script>
@endpush
