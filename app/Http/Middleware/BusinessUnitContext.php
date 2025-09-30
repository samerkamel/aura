<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessUnit;
use Symfony\Component\HttpFoundation\Response;

class BusinessUnitContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Super admins can access all business units without restrictions
        if ($user->canAccessAllBusinessUnits()) {
            return $next($request);
        }

        // Get user's accessible business unit IDs
        $accessibleBuIds = $user->getAccessibleBusinessUnitIds();

        if (empty($accessibleBuIds)) {
            // User has no BU access - deny access
            abort(403, 'You do not have access to any business units.');
        }

        // Check if user is trying to access a specific BU via route parameter
        $requestedBuId = $request->route('business_unit_id') ??
                        $request->input('business_unit_id') ??
                        $request->header('X-Business-Unit-ID');

        if ($requestedBuId && !in_array($requestedBuId, $accessibleBuIds)) {
            abort(403, 'You do not have access to this business unit.');
        }

        // Set current business unit context
        if ($requestedBuId) {
            $currentBu = BusinessUnit::find($requestedBuId);
            if ($currentBu) {
                $request->attributes->set('current_business_unit', $currentBu);
                session(['current_business_unit_id' => $requestedBuId]);
            }
        } else {
            // If no specific BU requested, use session or default to first accessible BU
            $currentBuId = session('current_business_unit_id');

            if (!$currentBuId || !in_array($currentBuId, $accessibleBuIds)) {
                $currentBuId = $accessibleBuIds[0];
                session(['current_business_unit_id' => $currentBuId]);
            }

            $currentBu = BusinessUnit::find($currentBuId);
            if ($currentBu) {
                $request->attributes->set('current_business_unit', $currentBu);
            }
        }

        // Make accessible BU IDs available to the request
        $request->attributes->set('accessible_business_unit_ids', $accessibleBuIds);

        return $next($request);
    }
}