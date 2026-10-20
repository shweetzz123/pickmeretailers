<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Grants access to the admin panel to any staff account (owner or clerk).
 * Finer-grained restrictions (sales, settings, deletes, ...) are enforced
 * by the separate OwnerMiddleware.
 */
class StaffMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isStaff()) {
            return $next($request);
        }
        abort(403, 'Forbidden');
    }
}
