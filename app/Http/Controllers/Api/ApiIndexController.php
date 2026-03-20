<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class ApiIndexController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name').' API',
            'auth' => 'Bearer token (Laravel Sanctum personal access token)',
            'docs' => [
                'openapi' => url('/api/openapi.json'),
                'interactive' => url('/api/docs'),
            ],
            'endpoints' => [
                ['method' => 'GET', 'path' => '/api', 'description' => 'API entrypoint and capability index'],
                ['method' => 'GET', 'path' => '/api/v1/ping', 'description' => 'Authenticated health check'],
                ['method' => 'GET', 'path' => '/api/mcp', 'description' => 'Authenticated MCP capability endpoint'],
            ],
        ]);
    }
}
