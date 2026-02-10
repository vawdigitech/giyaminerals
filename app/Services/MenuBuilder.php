<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class MenuBuilder
{
    protected array $items;

    public function __construct()
    {
        $this->items = config('menu.items', []);
    }

    public function build(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $menuItems = [];
        $pendingHeader = null;

        foreach ($this->items as $item) {
            // Check permission
            if (!$this->hasPermission($user, $item['permission'] ?? null)) {
                continue;
            }

            // Handle headers - store it but don't add yet
            if (isset($item['type']) && $item['type'] === 'header') {
                $pendingHeader = $item;
                continue;
            }

            // Process children if any
            if (isset($item['children'])) {
                $item['children'] = array_filter($item['children'], function ($child) use ($user) {
                    return $this->hasPermission($user, $child['permission'] ?? null);
                });

                // Skip parent if no visible children
                if (empty($item['children'])) {
                    continue;
                }
            }

            // Add pending header before first item in section
            if ($pendingHeader) {
                $menuItems[] = $pendingHeader;
                $pendingHeader = null;
            }

            $menuItems[] = $item;
        }

        return $menuItems;
    }

    protected function hasPermission($user, ?string $permission): bool
    {
        // No permission required
        if (empty($permission)) {
            return true;
        }

        // OR permissions (pipe separated)
        if (str_contains($permission, '|')) {
            $permissions = explode('|', $permission);
            foreach ($permissions as $perm) {
                if ($user->can(trim($perm))) {
                    return true;
                }
            }
            return false;
        }

        // AND permissions (comma separated)
        if (str_contains($permission, ',')) {
            $permissions = explode(',', $permission);
            foreach ($permissions as $perm) {
                if (!$user->can(trim($perm))) {
                    return false;
                }
            }
            return true;
        }

        // Single permission
        return $user->can($permission);
    }
}
