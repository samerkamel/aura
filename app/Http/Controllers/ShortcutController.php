<?php

namespace App\Http\Controllers;

use App\Models\UserShortcut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ShortcutController extends Controller
{
    /**
     * Get the current user's shortcuts.
     */
    public function index(): JsonResponse
    {
        $shortcuts = Auth::user()->shortcuts()
            ->get()
            ->filter(fn($shortcut) => $shortcut->isAccessible())
            ->values();

        return response()->json([
            'success' => true,
            'shortcuts' => $shortcuts,
        ]);
    }

    /**
     * Get available menu items that the user can add as shortcuts.
     * Filters based on user's roles.
     */
    public function available(): JsonResponse
    {
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();
        $isSuperAdmin = in_array('super-admin', $userRoles);

        // Get existing shortcut URLs to exclude
        $existingUrls = $user->shortcuts()->pluck('url')->toArray();

        // Read the menu configuration
        $menuPath = resource_path('menu/verticalMenu.json');
        $menuData = json_decode(File::get($menuPath), true);

        $availableItems = [];

        foreach ($menuData['menu'] as $item) {
            // Skip menu headers
            if (isset($item['menuHeader'])) {
                continue;
            }

            // Check role access
            $itemRoles = $item['roles'] ?? [];
            $hasAccess = $isSuperAdmin ||
                         in_array('all', $itemRoles) ||
                         !empty(array_intersect($itemRoles, $userRoles));

            if (!$hasAccess) {
                continue;
            }

            // If item has a direct URL (no submenu)
            if (isset($item['url']) && !isset($item['submenu'])) {
                if (!in_array($item['url'], $existingUrls)) {
                    $availableItems[] = $this->formatMenuItem($item, $itemRoles);
                }
            }

            // Process submenu items
            if (isset($item['submenu'])) {
                foreach ($item['submenu'] as $subitem) {
                    if (isset($subitem['url']) && !in_array($subitem['url'], $existingUrls)) {
                        $availableItems[] = $this->formatMenuItem($subitem, $itemRoles, $item);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'items' => $availableItems,
        ]);
    }

    /**
     * Format a menu item for the response.
     */
    private function formatMenuItem(array $item, array $roles, ?array $parent = null): array
    {
        // Determine the icon
        $icon = $item['icon'] ?? ($parent['icon'] ?? 'menu-icon tf-icons ti ti-link');

        // Extract just the ti-xxx class for the shortcut
        preg_match('/ti ti-[\w-]+/', $icon, $matches);
        $iconClass = $matches[0] ?? 'ti ti-link';

        // Build subtitle from parent name or slug
        $subtitle = $parent ? $parent['name'] : ($item['slug'] ?? '');

        return [
            'name' => $item['name'],
            'url' => $item['url'],
            'icon' => $iconClass,
            'subtitle' => $subtitle,
            'slug' => $item['slug'] ?? null,
            'required_roles' => $roles,
        ];
    }

    /**
     * Store a new shortcut for the user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'required_roles' => 'nullable|array',
        ]);

        $user = Auth::user();

        // Check if this shortcut already exists for the user
        $exists = $user->shortcuts()->where('url', $validated['url'])->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This shortcut already exists.',
            ], 422);
        }

        // Verify user has access to this menu item
        if (!$this->canAccessMenuItem($user, $validated['required_roles'] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this menu item.',
            ], 403);
        }

        // Get the max sort order and add 1
        $maxOrder = $user->shortcuts()->max('sort_order') ?? -1;
        $validated['sort_order'] = $maxOrder + 1;

        $shortcut = $user->shortcuts()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Shortcut added successfully.',
            'shortcut' => $shortcut,
        ]);
    }

    /**
     * Remove a shortcut.
     */
    public function destroy(UserShortcut $shortcut): JsonResponse
    {
        // Ensure the shortcut belongs to the current user
        if ($shortcut->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $shortcut->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shortcut removed successfully.',
        ]);
    }

    /**
     * Reorder shortcuts.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:user_shortcuts,id',
        ]);

        $user = Auth::user();

        foreach ($validated['order'] as $index => $shortcutId) {
            $user->shortcuts()
                ->where('id', $shortcutId)
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shortcuts reordered successfully.',
        ]);
    }

    /**
     * Check if user can access a menu item based on roles.
     */
    private function canAccessMenuItem($user, array $requiredRoles): bool
    {
        if (empty($requiredRoles)) {
            return true;
        }

        $userRoles = $user->roles->pluck('name')->toArray();

        // Super admin has access to everything
        if (in_array('super-admin', $userRoles)) {
            return true;
        }

        // Check if 'all' is in required roles
        if (in_array('all', $requiredRoles)) {
            return true;
        }

        // Check if user has any of the required roles
        return !empty(array_intersect($requiredRoles, $userRoles));
    }
}
