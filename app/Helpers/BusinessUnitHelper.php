<?php

namespace App\Helpers;

use App\Models\BusinessUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BusinessUnitHelper
{
    /**
     * Get the current business unit from request context.
     */
    public static function getCurrentBusinessUnit(Request $request = null): ?BusinessUnit
    {
        if (!$request) {
            $request = request();
        }

        return $request->attributes->get('current_business_unit');
    }

    /**
     * Get the current business unit ID from session or request.
     */
    public static function getCurrentBusinessUnitId(Request $request = null): ?int
    {
        $businessUnit = self::getCurrentBusinessUnit($request);

        if ($businessUnit) {
            return $businessUnit->id;
        }

        return session('current_business_unit_id');
    }

    /**
     * Get all accessible business unit IDs for the current user.
     */
    public static function getAccessibleBusinessUnitIds(Request $request = null): array
    {
        if (!$request) {
            $request = request();
        }

        $accessibleIds = $request->attributes->get('accessible_business_unit_ids');

        if ($accessibleIds) {
            return $accessibleIds;
        }

        // Fallback to user method if not in request context
        if (Auth::check()) {
            return Auth::user()->getAccessibleBusinessUnitIds();
        }

        return [];
    }

    /**
     * Check if current user can access multiple business units.
     */
    public static function canAccessMultipleBusinessUnits(): bool
    {
        return count(self::getAccessibleBusinessUnitIds()) > 1;
    }

    /**
     * Check if current user is a super admin (can access all BUs).
     */
    public static function isSuperAdmin(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return Auth::user()->canAccessAllBusinessUnits();
    }

    /**
     * Get all accessible business units for the current user.
     */
    public static function getAccessibleBusinessUnits(): \Illuminate\Database\Eloquent\Collection
    {
        // Super admins can access all business units
        if (self::isSuperAdmin()) {
            return BusinessUnit::active()->orderBy('name')->get();
        }

        $accessibleIds = self::getAccessibleBusinessUnitIds();

        if (empty($accessibleIds)) {
            return BusinessUnit::whereRaw('1 = 0')->get();
        }

        return BusinessUnit::whereIn('id', $accessibleIds)->active()->orderBy('name')->get();
    }

    /**
     * Set the current business unit in session.
     */
    public static function setCurrentBusinessUnit(int $businessUnitId): bool
    {
        $accessibleIds = self::getAccessibleBusinessUnitIds();

        if (in_array($businessUnitId, $accessibleIds) || self::isSuperAdmin()) {
            session(['current_business_unit_id' => $businessUnitId]);
            return true;
        }

        return false;
    }

    /**
     * Apply business unit filtering to a query builder.
     */
    public static function filterQueryByBusinessUnit($query, Request $request = null)
    {
        if (self::isSuperAdmin()) {
            return $query; // Super admins see all data
        }

        $currentBuId = self::getCurrentBusinessUnitId($request);

        if ($currentBuId) {
            return $query->where('business_unit_id', $currentBuId);
        }

        // If no current BU, filter by accessible BUs
        $accessibleIds = self::getAccessibleBusinessUnitIds($request);

        if (empty($accessibleIds)) {
            // No access - return empty result
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('business_unit_id', $accessibleIds);
    }
}