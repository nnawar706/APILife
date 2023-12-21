<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check())
        {
            return response()->json([
                'status' => false,
                'error'  => 'Protected Content'
            ], 403);
        }
        return $next($request);
    }
}
