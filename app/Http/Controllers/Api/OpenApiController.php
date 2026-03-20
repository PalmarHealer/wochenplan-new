<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class OpenApiController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('app.name').' API',
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => url('/')],
            ],
            'paths' => [
                '/api' => [
                    'get' => [
                        'summary' => 'API index',
                    ],
                ],
                '/api/v1/ping' => [
                    'get' => [
                        'summary' => 'Authenticated ping endpoint',
                        'security' => [['bearerAuth' => []]],
                    ],
                ],
                '/api/mcp' => [
                    'get' => [
                        'summary' => 'Authenticated MCP capability endpoint',
                        'security' => [['bearerAuth' => []]],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                ],
            ],
        ]);
    }
}
