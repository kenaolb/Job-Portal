<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmployerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user() && (auth()->user()->isEmployer() || auth()->user()->isAdmin())) {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized. Employer access required.'], 403);
    }
}