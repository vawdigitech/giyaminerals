<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="javascript:void(0);" class="brand-link p-0 m-0" style="height: auto;">
        <img src="{{ asset('template/dist/img/logo1.png') }}" alt="Logo"
            style="width: 100%; height: 80px; object-fit: cover; display: block;">
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="/dashboard"
                        class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- HR & Workforce -->
                <li class="nav-header">HR & WORKFORCE</li>
                <li class="nav-item">
                    <a href="{{ route('employees.index') }}"
                        class="nav-link {{ request()->is('employees*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Employees</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('attendance.index') }}"
                        class="nav-link {{ request()->is('attendance*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-clock"></i>
                        <p>Attendance</p>
                    </a>
                </li>

                <!-- Projects & Tasks -->
                <li class="nav-header">PROJECTS & TASKS</li>
                <li class="nav-item">
                    <a href="{{ route('projects.index') }}"
                        class="nav-link {{ request()->is('projects*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-project-diagram"></i>
                        <p>Projects</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('tasks.index') }}"
                        class="nav-link {{ request()->is('tasks*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>Tasks</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('issues.index') }}"
                        class="nav-link {{ request()->is('issues*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-exclamation-triangle"></i>
                        <p>Site Issues</p>
                    </a>
                </li>

                <!-- Inventory -->
                <li class="nav-header">INVENTORY</li>
                <li class="nav-item">
                    <a href="/warehouses"
                        class="nav-link {{ request()->is('warehouses*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-warehouse"></i>
                        <p>Warehouses</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/categories"
                        class="nav-link {{ request()->is('categories*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <p>Item Categories</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/products"
                        class="nav-link {{ request()->is('products*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>Items</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/sites"
                        class="nav-link {{ request()->is('sites*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Sites/Clients</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/stocks-entries"
                        class="nav-link {{ request()->is('stocks-entries') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-plus-square"></i>
                        <p>Stock Entry</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/transfers"
                        class="nav-link {{ request()->is('transfers*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-exchange-alt"></i>
                        <p>Transfers</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/stocks"
                        class="nav-link {{ request()->is('stocks') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>Stock Summary</p>
                    </a>
                </li>

                <!-- Reports & Analytics -->
                <li class="nav-header">REPORTS & ANALYTICS</li>
                <li class="nav-item {{ request()->is('analytics*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->is('analytics*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>
                            Analytics
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('analytics.profit-loss') }}"
                                class="nav-link {{ request()->is('analytics/profit-loss') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Profit/Loss</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('analytics.labor-report') }}"
                                class="nav-link {{ request()->is('analytics/labor-report') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Labor Report</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('analytics.material-usage') }}"
                                class="nav-link {{ request()->is('analytics/material-usage') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Material Usage</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('analytics.work-progress') }}"
                                class="nav-link {{ request()->is('analytics/work-progress') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Work Progress</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.stock_summary') }}"
                        class="nav-link {{ request()->is('reports/stock-summary') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Stock Report</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.transfer_log') }}"
                        class="nav-link {{ request()->is('reports/transfer-log') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Transfer Log</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
