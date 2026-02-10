<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Define menu items with their required permissions.
    | Use pipe (|) for OR permissions - user needs at least one.
    | Use comma (,) for AND permissions - user needs all.
    |
    */

    'items' => [
        // Dashboard - visible to all authenticated users
        [
            'title' => 'Dashboard',
            'route' => 'dashboard.index',
            'icon' => 'fas fa-tachometer-alt',
            'active' => 'dashboard',
            'permission' => null, // No permission required
        ],

        // ADMINISTRATION
        [
            'title' => 'ADMINISTRATION',
            'type' => 'header',
            'permission' => 'roles.view|users.view',
        ],
        [
            'title' => 'Roles & Permissions',
            'route' => 'roles.index',
            'icon' => 'fas fa-user-shield',
            'active' => 'roles*',
            'permission' => 'roles.view',
        ],
        [
            'title' => 'User Management',
            'route' => 'users.index',
            'icon' => 'fas fa-user-cog',
            'active' => 'users*',
            'permission' => 'users.view',
        ],

        // HR & WORKFORCE
        [
            'title' => 'HR & WORKFORCE',
            'type' => 'header',
            'permission' => 'employees.view|attendance.view|designations.view',
        ],
        [
            'title' => 'Designations',
            'route' => 'designations.index',
            'icon' => 'fas fa-id-badge',
            'active' => 'designations*',
            'permission' => 'designations.view',
        ],
        [
            'title' => 'Employees',
            'route' => 'employees.index',
            'icon' => 'fas fa-users',
            'active' => 'employees*',
            'permission' => 'employees.view',
        ],
        [
            'title' => 'Attendance',
            'route' => 'attendance.index',
            'icon' => 'fas fa-user-clock',
            'active' => 'attendance*',
            'permission' => 'attendance.view',
        ],

        // PROJECTS & TASKS
        [
            'title' => 'PROJECTS & TASKS',
            'type' => 'header',
            'permission' => 'projects.view|tasks.view|issues.view',
        ],
        [
            'title' => 'Projects',
            'route' => 'projects.index',
            'icon' => 'fas fa-project-diagram',
            'active' => 'projects*',
            'permission' => 'projects.view',
        ],
        [
            'title' => 'Tasks',
            'route' => 'tasks.index',
            'icon' => 'fas fa-tasks',
            'active' => 'tasks*',
            'permission' => 'tasks.view',
        ],
        [
            'title' => 'Site Issues',
            'route' => 'issues.index',
            'icon' => 'fas fa-exclamation-triangle',
            'active' => 'issues*',
            'permission' => 'issues.view',
        ],

        // INVENTORY
        [
            'title' => 'INVENTORY',
            'type' => 'header',
            'permission' => 'warehouses.view|inventory.view|sites.view|transfers.view',
        ],
        [
            'title' => 'Warehouses',
            'route' => 'warehouses.index',
            'icon' => 'fas fa-warehouse',
            'active' => 'warehouses*',
            'permission' => 'warehouses.view',
        ],
        [
            'title' => 'Item Categories',
            'route' => 'categories.index',
            'icon' => 'fas fa-list-alt',
            'active' => 'categories*',
            'permission' => 'inventory.view',
        ],
        [
            'title' => 'Items',
            'route' => 'products.index',
            'icon' => 'fas fa-boxes',
            'active' => 'products*',
            'permission' => 'inventory.view',
        ],
        [
            'title' => 'Sites/Clients',
            'route' => 'sites.index',
            'icon' => 'fas fa-building',
            'active' => 'sites*',
            'permission' => 'sites.view',
        ],
        [
            'title' => 'Stock Entry',
            'route' => 'stocks.entries',
            'icon' => 'fas fa-plus-square',
            'active' => 'stocks-entries',
            'permission' => 'inventory.view',
        ],
        [
            'title' => 'Transfers',
            'route' => 'transfers.index',
            'icon' => 'fas fa-exchange-alt',
            'active' => 'transfers*',
            'permission' => 'transfers.view',
        ],
        [
            'title' => 'Stock Summary',
            'url' => '/stocks',
            'icon' => 'fas fa-clipboard-list',
            'active' => 'stocks',
            'permission' => 'inventory.view',
        ],

        // REPORTS & ANALYTICS
        [
            'title' => 'REPORTS & ANALYTICS',
            'type' => 'header',
            'permission' => 'analytics.view|reports.view',
        ],
        [
            'title' => 'Analytics',
            'icon' => 'fas fa-chart-bar',
            'active' => 'analytics*',
            'permission' => 'analytics.view',
            'children' => [
                [
                    'title' => 'Profit/Loss',
                    'route' => 'analytics.profit-loss',
                    'active' => 'analytics/profit-loss',
                ],
                [
                    'title' => 'Labor Report',
                    'route' => 'analytics.labor-report',
                    'active' => 'analytics/labor-report',
                ],
                [
                    'title' => 'Material Usage',
                    'route' => 'analytics.material-usage',
                    'active' => 'analytics/material-usage',
                ],
                [
                    'title' => 'Work Progress',
                    'route' => 'analytics.work-progress',
                    'active' => 'analytics/work-progress',
                ],
            ],
        ],
        [
            'title' => 'Stock Report',
            'route' => 'reports.stock_summary',
            'icon' => 'fas fa-file-alt',
            'active' => 'reports/stock-summary',
            'permission' => 'reports.view',
        ],
        [
            'title' => 'Transfer Log',
            'route' => 'reports.transfer_log',
            'icon' => 'fas fa-history',
            'active' => 'reports/transfer-log',
            'permission' => 'reports.view',
        ]
    ],
];
