<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermissionTo('api.access')) {
            return response()->json([
                'message' => 'You are not authorized to access the API.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
