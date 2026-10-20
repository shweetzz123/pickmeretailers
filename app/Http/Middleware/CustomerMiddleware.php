<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // Both shoppers and staff (owners/clerks) may shop.
        if (! Auth::user()->isCustomer() && ! Auth::user()->isStaff()) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
