<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user ||
            ! $user->isAdmin() ||
            ! $user->adminUser ||
            ! $user->adminUser->is_active) {
            return redirect()->route('dashboard')->with('error', 'Not authorized.');
        }

        return $next($request);
    }
}
