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
            <ul class="nav nav-pills nav-sidebar flex-column">
                <li class="nav-item">
                    <a href="/dashboard"
                        class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
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
                        <p>Clients</p>
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
            </ul>

        </nav>
    </div>
</aside>
