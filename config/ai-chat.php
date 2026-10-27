<?php

return [
    'ollama_base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    'model' => env('AI_CHAT_MODEL', 'qwen3:8b'),
    'max_tokens' => (int) env('AI_CHAT_MAX_TOKENS', 2048),
    'temperature' => 0.2,
];
