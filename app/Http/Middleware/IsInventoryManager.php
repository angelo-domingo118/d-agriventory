<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsInventoryManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Authentication required.');
        }

        // Eager load the divisionInventoryManager relationship
        if (! $user->relationLoaded('divisionInventoryManager')) {
            $user->load('divisionInventoryManager');
        }

        // Use the isDivisionInventoryManager method for consistency
        if (! $user->isDivisionInventoryManager()) {
            return redirect()->route('dashboard')->with('error', 'You must be an inventory manager to access this area.');
        }

        return $next($request);
    }
}
