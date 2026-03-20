<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRecentLoginForApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->lastLoginExpiredForApi()) {
            return response()->json([
                'message' => 'API access requires a successful web login within the last 6 months.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
