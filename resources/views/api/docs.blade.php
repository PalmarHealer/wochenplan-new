<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} API Dokumentation</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; margin: 2rem; line-height: 1.5; }
        code { background: #f4f4f5; padding: .15rem .35rem; border-radius: .25rem; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} API</h1>
    <p>Diese Schnittstelle nutzt <strong>Bearer Tokens (Sanctum)</strong> und dieselbe Authentifizierung für API und MCP.</p>
    <ul>
        <li>Index: <code>{{ url('/api') }}</code></li>
        <li>OpenAPI JSON: <code>{{ url('/api/openapi.json') }}</code></li>
        <li>Auth Test: <code>{{ url('/api/v1/ping') }}</code></li>
        <li>MCP: <code>{{ url('/api/mcp') }}</code></li>
    </ul>
</body>
</html>
