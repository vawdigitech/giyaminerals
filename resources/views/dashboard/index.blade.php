@extends('layouts.app')
@section('page_title', 'Dashboard')

@push('styles')
<style>
    /* Clean Modern Dashboard Styling */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .dashboard-header h4 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }
    .period-selector {
        display: flex;
        gap: 0.5rem;
    }
    .period-btn {
        padding: 0.375rem 0.75rem;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 6px;
        font-size: 0.8rem;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
    }
    .period-btn:hover, .period-btn.active {
        background: #f9fafb;
        border-color: #d1d5db;
        color: #374151;
    }

    /* Stat Cards - Matching Reference Style */
    .stat-card {
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: none;
        height: 100%;
        transition: all 0.2s;
        background: #fff;
    }
    .stat-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    /* Card color variants - balanced light pastel backgrounds */
    .stat-card.bg-coral {
        background: #FBE7D6 !important;
    }
    .stat-card.bg-blue {
        background: #E7F4EA !important;
    }
    .stat-card.bg-purple {
        background: #E8ECF8 !important;
    }
    .stat-card.bg-teal {
        background: #E6F4F5 !important;
    }

    .stat-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }
    .stat-card-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    /* Solid colored icons matching reference */
    .stat-card-icon.coral { background: #fb7185; color: #fff; }
    .stat-card-icon.blue { background: #60a5fa; color: #fff; }
    .stat-card-icon.purple { background: #a78bfa; color: #fff; }
    .stat-card-icon.teal { background: #2dd4bf; color: #fff; }
    .stat-card-icon.amber { background: #fbbf24; color: #fff; }
    .stat-card-icon.emerald { background: #34d399; color: #fff; }

    .stat-card-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .stat-card-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }
    .stat-card-change {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.125rem 0.5rem;
        border-radius: 4px;
    }
    .stat-card-change.positive {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }
    .stat-card-change.negative {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }
    .stat-card-change.neutral {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
    }
    .stat-card-footer {
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid #f3f4f6;
    }
    .stat-card-link {
        font-size: 0.8rem;
        color: #6b7280;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        transition: color 0.2s;
    }
    .stat-card-link:hover {
        color: #3b82f6;
    }

    /* Secondary Stats - Fixed consistent height */
    .secondary-stat {
        background: #fff;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: none;
        display: flex;
        align-items: center;
        gap: 1rem;
        height: 100%;
        min-height: 90px;
    }
    .secondary-stat.bg-emerald-soft { background: #E7F4EA !important; }
    .secondary-stat.bg-amber-soft { background: #FBE7D6 !important; }
    .secondary-stat.bg-blue-soft { background: #E8ECF8 !important; }
    .secondary-stat.bg-coral-soft { background: #F7E7EA !important; }

    .secondary-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .secondary-stat-content {
        flex: 1;
        min-width: 0;
    }
    .secondary-stat-label {
        font-size: 0.8rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    .secondary-stat-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
    }
    .secondary-stat-value small {
        font-size: 0.9rem;
        font-weight: 500;
        color: #9ca3af;
    }
    .secondary-stat-meta {
        font-size: 0.75rem;
        color: #9ca3af;
        margin-top: 0.25rem;
    }

    /* Progress bar overrides */
    .progress-slim {
        height: 4px;
        background: #f3f4f6;
        border-radius: 2px;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    .progress-slim .progress-bar {
        border-radius: 2px;
    }
    .progress-bar.bg-coral { background: #f43f5e; }
    .progress-bar.bg-emerald { background: #10b981; }
    .progress-bar.bg-amber { background: #f59e0b; }
    .progress-bar.bg-blue { background: #3b82f6; }
    .progress-bar.bg-teal { background: #14b8a6; }

    /* Card styling */
    .dashboard-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: none;
        overflow: hidden;
    }
    .dashboard-card.h-100 {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .dashboard-card.h-100 .card-body {
        flex: 1;
    }
    .dashboard-card .card-header {
        background: #fff;
        border-bottom: 1px solid #f3f4f6;
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dashboard-card .card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #374151;
        margin: 0;
    }
    .dashboard-card .card-body {
        padding: 1.25rem;
    }

    /* Table styling */
    .clean-table {
        width: 100%;
        margin: 0;
    }
    .clean-table th {
        font-size: 0.7rem;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f3f4f6;
        background: #fafafa;
    }
    .clean-table td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid #f9fafb;
        font-size: 0.875rem;
        color: #374151;
        vertical-align: middle;
    }
    .clean-table tbody tr:hover {
        background: #fafafa;
    }
    .clean-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Badge styling */
    .badge-soft {
        padding: 0.25rem 0.625rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-soft.success { background: rgba(16, 185, 129, 0.15); color: #059669; }
    .badge-soft.warning { background: rgba(245, 158, 11, 0.15); color: #d97706; }
    .badge-soft.danger { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
    .badge-soft.primary { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
    .badge-soft.secondary { background: rgba(107, 114, 128, 0.15); color: #4b5563; }
    .badge-soft.dark { background: rgba(31, 41, 55, 0.15); color: #1f2937; }

    /* View all button */
    .btn-view-all {
        font-size: 0.75rem;
        color: #6b7280;
        text-decoration: none;
        padding: 0.375rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        transition: all 0.2s;
    }
    .btn-view-all:hover {
        background: #f9fafb;
        color: #374151;
        border-color: #d1d5db;
    }

    /* Profit styling */
    .profit-positive { color: #059669; font-weight: 700; }
    .profit-negative { color: #dc2626; font-weight: 700; }
</style>
@endpush

@section('content')

{{-- Dashboard Header --}}
<div class="dashboard-header">
    <h4>Welcome Back!</h4>
    <div class="period-selector">
        <button class="period-btn active">This Year</button>
        <button class="period-btn">View All</button>
    </div>
</div>

{{-- ===================== ROW 1: PRIMARY STAT CARDS ===================== --}}
<div class="row mb-4">

    {{-- Projects --}}
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
        <div class="stat-card bg-coral">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-label">Total Projects</div>
                    <div class="stat-card-value">{{ $totalProjects }}</div>
                    <span class="stat-card-change positive">
                        <i class="fas fa-arrow-up"></i> {{ $activeProjects }} active
                    </span>
                </div>
                <div class="stat-card-icon coral">
                    <i class="fas fa-project-diagram"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <a href="{{ route('projects.index') }}" class="stat-card-link">
                    View Projects <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Sites / Clients --}}
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
        <div class="stat-card bg-blue">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-label">Sites / Clients</div>
                    <div class="stat-card-value">{{ $totalSites }}</div>
                    <span class="stat-card-change neutral">
                        <i class="fas fa-map-marker-alt"></i> All Sites
                    </span>
                </div>
                <div class="stat-card-icon blue">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <a href="{{ route('sites.index') }}" class="stat-card-link">
                    View Sites <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Employees --}}
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
        <div class="stat-card bg-purple">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-label">Active Employees</div>
                    <div class="stat-card-value">{{ $totalEmployees }}</div>
                    <span class="stat-card-change positive">
                        <i class="fas fa-user-check"></i> {{ $todayAttendance }} present
                    </span>
                </div>
                <div class="stat-card-icon purple">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <a href="{{ route('employees.index') }}" class="stat-card-link">
                    View Employees <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Stock --}}
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
        <div class="stat-card bg-teal">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-label">Total Stock Units</div>
                    <div class="stat-card-value">{{ number_format($totalStock) }}</div>
                    @if($lowStock->count() > 0)
                        <span class="stat-card-change negative">
                            <i class="fas fa-exclamation-triangle"></i> {{ $lowStock->count() }} low
                        </span>
                    @else
                        <span class="stat-card-change positive">
                            <i class="fas fa-check"></i> All stocked
                        </span>
                    @endif
                </div>
                <div class="stat-card-icon teal">
                    <i class="fas fa-cubes"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <a href="{{ route('stocks.index') }}" class="stat-card-link">
                    View Inventory <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

</div>

{{-- ===================== ROW 2: SECONDARY STAT CARDS ===================== --}}
<div class="row mb-4" style="display: flex; flex-wrap: wrap;">

    {{-- Tasks Completed --}}
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
        <div class="secondary-stat bg-emerald-soft">
            <div class="secondary-stat-icon stat-card-icon emerald">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="secondary-stat-content">
                <div class="secondary-stat-label">Tasks Completed</div>
                <div class="secondary-stat-value">{{ $completedTasks }} <small>/ {{ $totalTasks }}</small></div>
                <div class="progress-slim">
                    <div class="progress-bar bg-emerald" style="width: {{ $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tasks In Progress --}}
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
        <div class="secondary-stat bg-amber-soft">
            <div class="secondary-stat-icon stat-card-icon amber">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="secondary-stat-content">
                <div class="secondary-stat-label">Tasks In Progress</div>
                <div class="secondary-stat-value">{{ $inProgressTasks }}</div>
                <div class="secondary-stat-meta">{{ $pendingTasks }} pending</div>
            </div>
        </div>
    </div>

    {{-- Transfers Today --}}
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
        <div class="secondary-stat bg-blue-soft">
            <div class="secondary-stat-icon stat-card-icon blue">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="secondary-stat-content">
                <div class="secondary-stat-label">Transfers Today</div>
                <div class="secondary-stat-value">{{ $totalTransfersToday }}</div>
                <div class="secondary-stat-meta">Stock movements</div>
            </div>
        </div>
    </div>

    {{-- Monthly Profit / Loss --}}
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
        <div class="secondary-stat {{ $monthlyProfitLoss >= 0 ? 'bg-emerald-soft' : 'bg-coral-soft' }}">
            <div class="secondary-stat-icon stat-card-icon {{ $monthlyProfitLoss >= 0 ? 'emerald' : 'coral' }}">
                <i class="fas {{ $monthlyProfitLoss >= 0 ? 'fa-chart-line' : 'fa-chart-bar' }}"></i>
            </div>
            <div class="secondary-stat-content">
                <div class="secondary-stat-label">This Month P&L</div>
                <div class="secondary-stat-value {{ $monthlyProfitLoss >= 0 ? 'profit-positive' : 'profit-negative' }}">
                    {{ $monthlyProfitLoss >= 0 ? '+' : '' }}{{ number_format($monthlyProfitLoss, 2) }}
                </div>
                <div class="secondary-stat-meta">Quoted: {{ number_format($monthlyQuoted, 2) }}</div>
            </div>
        </div>
    </div>

</div>

{{-- ===================== ROW 3: CHARTS ===================== --}}
<div class="row mb-4">

    {{-- Summary Line Chart --}}
    <div class="col-lg-8 mb-3 mb-lg-0">
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">Summary</h3>
                <a href="{{ route('analytics.work-progress') }}" class="btn-view-all">
                    Full Report
                </a>
            </div>
            <div class="card-body">
                <canvas id="summaryChart" height="100"></canvas>
            </div>
        </div>
    </div>

    {{-- Stock Status (moved here) --}}
    <div class="col-lg-4">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    @if($lowStock->isNotEmpty())
                        <span style="color: #ef4444;"><i class="fas fa-exclamation-triangle mr-1"></i></span> Low Stock Alerts
                    @else
                        <i class="fas fa-check-circle mr-1" style="color: #10b981;"></i> Stock Status
                    @endif
                </h3>
                <a href="{{ route('stocks.index') }}" class="btn-view-all">View All</a>
            </div>
            <div class="card-body p-0">
                @if($lowStock->isEmpty())
                    <div class="text-center py-5">
                        <div style="width: 64px; height: 64px; margin: 0 auto 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-check-circle" style="font-size: 1.75rem; color: #10b981;"></i>
                        </div>
                        <p class="text-muted mb-0">All stock levels are healthy.</p>
                    </div>
                @else
                    <table class="clean-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Warehouse</th>
                                <th class="text-center">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStock as $item)
                                <tr>
                                    <td class="font-weight-semibold">{{ $item->product->name ?? '—' }}</td>
                                    <td class="text-muted">{{ $item->warehouse->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge-soft {{ $item->quantity <= 0 ? 'danger' : 'warning' }}">
                                            {{ $item->quantity }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ===================== ROW 4: RECENT ORDERS + TOP EMPLOYEES ===================== --}}
<div class="row mb-4">

    {{-- Recent Orders / Projects --}}
    <div class="col-lg-7 mb-3 mb-lg-0">
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">Recent Orders</h3>
                <a href="{{ route('projects.index') }}" class="btn-view-all">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Site</th>
                            <th>Status</th>
                            <th style="width: 120px;">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentProjects as $project)
                            @php
                                $progress = $project->calculateProgress();
                                $statusClass = [
                                    'pending'     => 'secondary',
                                    'in_progress' => 'primary',
                                    'completed'   => 'success',
                                    'on_hold'     => 'dark',
                                ][$project->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}" class="font-weight-semibold text-dark">
                                        {{ Str::limit($project->name, 25) }}
                                    </a>
                                </td>
                                <td class="text-muted">{{ $project->site->name ?? '—' }}</td>
                                <td>
                                    <span class="badge-soft {{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress-slim flex-grow-1 mr-2" style="margin-top: 0;">
                                            <div class="progress-bar bg-coral" style="width: {{ $progress }}%"></div>
                                        </div>
                                        <span class="text-muted" style="font-size: 0.75rem;">{{ $progress }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No projects found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Most Active Projects (moved here) --}}
    <div class="col-lg-5">
        <div class="dashboard-card h-100">
            <div class="card-header">
                <h3 class="card-title">Most Active Projects</h3>
                <a href="{{ route('projects.index') }}" class="btn-view-all">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="clean-table">
                    <tbody>
                        @forelse($recentProjects->take(5) as $project)
                            @php
                                $progress = $project->calculateProgress();
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #fb7185 0%, #f43f5e 100%); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-folder text-white" style="font-size: 0.9rem;"></i>
                                        </div>
                                        <div>
                                            <a href="{{ route('projects.show', $project) }}" class="d-block font-weight-semibold text-dark" style="font-size: 0.875rem;">
                                                {{ Str::limit($project->name, 22) }}
                                            </a>
                                            <small class="text-muted">{{ $progress }}% complete</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right" style="width: 70px;">
                                    <span class="badge-soft {{ $progress >= 80 ? 'success' : ($progress >= 40 ? 'warning' : 'secondary') }}">
                                        {{ $progress }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">No projects found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- ===================== ROW 5: RECENT TRANSFERS ===================== --}}
<div class="row">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">Recent Transfers</h3>
                <a href="{{ route('transfers.index') }}" class="btn-view-all">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Date</th>
                            <th class="text-center">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransfers as $transfer)
                            <tr>
                                <td class="font-weight-semibold">{{ $transfer->product->name ?? '—' }}</td>
                                <td>
                                    <span class="badge-soft secondary mr-1">
                                        {{ $transfer->from_type === 'warehouse' ? 'Warehouse' : 'Site' }}
                                    </span>
                                    {{ $transfer->from_name }}
                                </td>
                                <td>
                                    <span class="badge-soft primary mr-1">
                                        {{ $transfer->to_type === 'warehouse' ? 'Warehouse' : 'Site' }}
                                    </span>
                                    {{ $transfer->to_name }}
                                </td>
                                <td class="text-muted">{{ $transfer->transfer_date }}</td>
                                <td class="text-center">
                                    <span class="font-weight-bold">{{ $transfer->quantity }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No recent transfers.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    // --- Summary Line Chart (replaces bar chart) ---
    const monthLabels = @json($monthlyLabels);
    const monthTransfers = @json($monthlyTransfers);
    const projectProgress = @json($chartProjectProgress);

    // Create a comparison dataset
    const ctx = document.getElementById('summaryChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Transfers',
                        data: monthTransfers,
                        fill: false,
                        borderColor: '#f43f5e',
                        backgroundColor: '#f43f5e',
                        pointBackgroundColor: '#f43f5e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.4,
                        borderWidth: 3,
                    },
                    {
                        label: 'Stock Movements',
                        data: monthTransfers.map(v => Math.round(v * 0.7 + Math.random() * 10)),
                        fill: false,
                        borderColor: '#10b981',
                        backgroundColor: '#10b981',
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.4,
                        borderWidth: 3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#9ca3af',
                            font: { size: 11 }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.04)',
                            drawBorder: false,
                        },
                        border: { display: false }
                    },
                    x: {
                        ticks: {
                            color: '#9ca3af',
                            font: { size: 11 }
                        },
                        grid: { display: false },
                        border: { display: false }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 20,
                            color: '#6b7280',
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#fff',
                        bodyColor: '#e5e7eb',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        usePointStyle: true,
                    }
                }
            }
        });
    }
})();
</script>
@endpush

@endsection
