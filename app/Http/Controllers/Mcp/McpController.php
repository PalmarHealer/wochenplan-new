<?php

namespace App\Http\Controllers\Mcp;

use Illuminate\Http\JsonResponse;

class McpController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name').' MCP',
            'protocolVersion' => '2025-06-18',
            'capabilities' => [
                'resources' => true,
                'tools' => false,
                'prompts' => false,
            ],
            'resources' => [
                [
                    'uri' => 'wochenplan://api/index',
                    'name' => 'API Index',
                    'description' => 'Machine-readable API entrypoint metadata',
                ],
            ],
        ]);
    }
}
