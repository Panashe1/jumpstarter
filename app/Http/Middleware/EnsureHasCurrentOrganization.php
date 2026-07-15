<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees that every request past this point has a valid current
 * organization: stale selections are cleared, a sensible default is
 * chosen, and users with no organization are sent to the create page.
 */
class EnsureHasCurrentOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $current = $user->currentOrganization;

        if ($current !== null && ! $user->belongsToOrganization($current)) {
            $current = null;
        }

        if ($current === null) {
            $current = $user->organizations()->orderBy('name')->first();
            $user->forceFill(['current_organization_id' => $current?->id])->save();
        }

        if ($current === null) {
            return redirect()->route('organizations.create');
        }

        return $next($request);
    }
}
