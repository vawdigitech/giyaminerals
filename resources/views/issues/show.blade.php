@extends('layouts.app')
@section('page_title', 'Issue Details')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('issues.index') }}">Issues</a></li>
    <li class="breadcrumb-item active">{{ $issue->title }}</li>
@endsection
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Issue Details -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $issue->title }}</h3>
                        <div class="card-tools">
                            @php
                                $priorityColors = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'purple'];
                                $statusColors = ['open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'secondary'];
                            @endphp
                            <span class="badge badge-{{ $priorityColors[$issue->priority] ?? 'secondary' }} mr-2">
                                {{ ucfirst($issue->priority) }} Priority
                            </span>
                            <span class="badge badge-{{ $statusColors[$issue->status] ?? 'secondary' }}">
                                {{ ucwords(str_replace('_', ' ', $issue->status)) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Site:</dt>
                            <dd class="col-sm-9">{{ $issue->site->name ?? '-' }}</dd>

                            <dt class="col-sm-3">Category:</dt>
                            <dd class="col-sm-9">{{ ucfirst($issue->category) }}</dd>

                            @if($issue->task)
                                <dt class="col-sm-3">Related Task:</dt>
                                <dd class="col-sm-9">
                                    <a href="{{ route('tasks.show', $issue->task) }}">
                                        {{ $issue->task->code }} - {{ $issue->task->name }}
                                    </a>
                                    @if($issue->task->project)
                                        <br><small class="text-muted">Project: {{ $issue->task->project->name }}</small>
                                    @endif
                                </dd>
                            @endif

                            <dt class="col-sm-3">Reported By:</dt>
                            <dd class="col-sm-9">{{ $issue->reportedBy->name ?? 'Unknown' }}</dd>

                            <dt class="col-sm-3">Assigned To:</dt>
                            <dd class="col-sm-9">{{ $issue->assignedTo->name ?? 'Unassigned' }}</dd>

                            <dt class="col-sm-3">Created:</dt>
                            <dd class="col-sm-9">{{ $issue->created_at->format('M d, Y H:i') }}</dd>

                            @if($issue->resolved_at)
                                <dt class="col-sm-3">Resolved:</dt>
                                <dd class="col-sm-9">{{ \Carbon\Carbon::parse($issue->resolved_at)->format('M d, Y H:i') }}</dd>
                            @endif
                        </dl>

                        <hr>
                        <h5>Description</h5>
                        <p>{{ $issue->description ?? 'No description provided.' }}</p>

                        @if($issue->resolution_notes)
                            <hr>
                            <h5>Resolution Notes</h5>
                            <p>{{ $issue->resolution_notes }}</p>
                        @endif

                        @if($issue->photos && count($issue->photos) > 0)
                            <hr>
                            <h5>Photos</h5>
                            <div class="row">
                                @foreach($issue->photos as $photo)
                                    <div class="col-md-4 mb-2">
                                        <img src="{{ $photo }}" alt="Issue Photo" class="img-fluid img-thumbnail">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="col-md-4">
                <!-- Update Status -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Update Status</h3>
                    </div>
                    <form method="POST" action="{{ route('issues.update-status', $issue) }}">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="open" {{ $issue->status == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ $issue->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="resolved" {{ $issue->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ $issue->status == 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="resolution_notes">Resolution Notes</label>
                                <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="3">{{ $issue->resolution_notes }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-block">Update Status</button>
                        </div>
                    </form>
                </div>

                <!-- Assign -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Assign Issue</h3>
                    </div>
                    <form method="POST" action="{{ route('issues.assign', $issue) }}">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="assigned_to">Assign To</label>
                                <select class="form-control" id="assigned_to" name="assigned_to" required>
                                    <option value="">-- Select User --</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}"
                                            {{ $issue->assigned_to == $supervisor->id ? 'selected' : '' }}>
                                            {{ $supervisor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-info btn-block">Assign</button>
                        </div>
                    </form>
                </div>

                <!-- Delete -->
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Danger Zone</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('issues.destroy', $issue) }}"
                            onsubmit="return confirm('Are you sure you want to delete this issue?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-trash"></i> Delete Issue
                            </button>
                        </form>
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
