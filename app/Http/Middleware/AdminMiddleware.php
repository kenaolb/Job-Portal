<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user() && auth()->user()->isAdmin()) {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
    }
}