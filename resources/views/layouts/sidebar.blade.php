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
                @php
                    $menuBuilder = new \App\Services\MenuBuilder();
                    $menuItems = $menuBuilder->build();
                @endphp

                @foreach($menuItems as $item)
                    @if(isset($item['type']) && $item['type'] === 'header')
                        <!-- Header -->
                        <li class="nav-header">{{ $item['title'] }}</li>
                    @elseif(isset($item['children']) && count($item['children']) > 0)
                        <!-- Dropdown Menu -->
                        @php
                            $isOpen = false;
                            foreach($item['children'] as $child) {
                                if(request()->is($child['active'] ?? '')) {
                                    $isOpen = true;
                                    break;
                                }
                            }
                        @endphp
                        <li class="nav-item {{ $isOpen ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ $isOpen ? 'active' : '' }}">
                                <i class="nav-icon {{ $item['icon'] ?? 'fas fa-circle' }}"></i>
                                <p>
                                    {{ $item['title'] }}
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @foreach($item['children'] as $child)
                                    <li class="nav-item">
                                        <a href="{{ isset($child['route']) ? route($child['route']) : ($child['url'] ?? '#') }}"
                                            class="nav-link {{ request()->is($child['active'] ?? '') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>{{ $child['title'] }}</p>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <!-- Regular Menu Item -->
                        <li class="nav-item">
                            <a href="{{ isset($item['route']) ? route($item['route']) : ($item['url'] ?? '#') }}"
                                class="nav-link {{ request()->is($item['active'] ?? '') ? 'active' : '' }}">
                                <i class="nav-icon {{ $item['icon'] ?? 'fas fa-circle' }}"></i>
                                <p>{{ $item['title'] }}</p>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
