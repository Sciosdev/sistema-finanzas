<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinanceOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isFinanceOwner()) {
            abort(403);
        }

        return $next($request);
    }
}
