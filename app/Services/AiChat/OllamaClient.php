<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaClient
{
    private string $baseUrl;

    private string $model;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ai-chat.ollama_base_url'), '/');
        $this->model = config('ai-chat.model');
    }

    /**
     * Non-streaming chat (used for tool-call rounds).
     */
    public function chat(array $messages, array $tools = []): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            'keep_alive' => -1,
            'options' => [
                'temperature' => config('ai-chat.temperature', 0.2),
                'num_predict' => config('ai-chat.max_tokens', 2048),
            ],
        ];

        if (! empty($tools)) {
            $payload['tools'] = $tools;
        }

        try {
            $response = Http::timeout(120)
                ->post("{$this->baseUrl}/api/chat", $payload);

            if (! $response->successful()) {
                Log::error('Ollama API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'content' => 'Es gab einen Fehler bei der Kommunikation mit der KI. Bitte versuche es erneut.',
                    'tool_calls' => [],
                ];
            }

            $data = $response->json();

            return [
                'content' => $data['message']['content'] ?? null,
                'tool_calls' => $data['message']['tool_calls'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Ollama connection error', ['error' => $e->getMessage()]);

            return [
                'content' => 'Die KI ist momentan nicht erreichbar. Bitte versuche es später erneut.',
                'tool_calls' => [],
            ];
        }
    }

    /**
     * Streaming chat via curl — yields raw content chunks.
     *
     * @return \Generator<string>
     */
    public function streamChat(array $messages): \Generator
    {
        $payload = json_encode([
            'model' => $this->model,
            'messages' => $messages,
            'stream' => true,
            'options' => [
                'temperature' => config('ai-chat.temperature', 0.2),
                'num_predict' => config('ai-chat.max_tokens', 2048),
            ],
        ]);

        $url = "{$this->baseUrl}/api/chat";

        // Use a temp file as a pipe for curl streaming
        $tmpFile = tmpfile();
        $tmpPath = stream_get_meta_data($tmpFile)['uri'];
        fclose($tmpFile);

        // Use proc_open to run curl and read its stdout line by line
        $cmd = sprintf(
            'curl -s -N --max-time 120 -H "Content-Type: application/json" -d %s %s',
            escapeshellarg($payload),
            escapeshellarg($url)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'r'],
            2 => ['pipe', 'r'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (! is_resource($process)) {
            Log::error('Failed to start curl for Ollama streaming');
            yield 'Fehler: Streaming konnte nicht gestartet werden.';

            return;
        }

        fclose($pipes[0]); // Close stdin

        stream_set_blocking($pipes[1], false);

        $buffer = '';
        $running = true;

        while ($running) {
            $chunk = fread($pipes[1], 8192);

            if ($chunk === false || ($chunk === '' && feof($pipes[1]))) {
                $running = false;

                break;
            }

            if ($chunk === '') {
                usleep(10000); // 10ms

                continue;
            }

            $buffer .= $chunk;

            while (($nlPos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $nlPos);
                $buffer = substr($buffer, $nlPos + 1);

                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $data = json_decode($line, true);
                if (! $data) {
                    continue;
                }

                $content = $data['message']['content'] ?? '';
                if ($content !== '') {
                    yield $content;
                }

                if (! empty($data['done'])) {
                    $running = false;

                    break;
                }
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }

    /**
     * Generate a short chat title from the user's first message.
     */
    public function generateTitle(string $userMessage): string
    {
        try {
            $response = Http::timeout(20)
                ->post("{$this->baseUrl}/api/chat", [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => '/no_think',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Summarize this user message as a short title (2-5 words, same language as input). Output ONLY the title:\n\n{$userMessage}",
                        ],
                    ],
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.5,
                        'num_predict' => 25,
                    ],
                ]);

            $title = $response->json('message.content') ?? '';
            $title = self::stripThinkingTags($title);
            // Remove any quotes, asterisks, trailing punctuation
            $title = trim($title, " \t\n\r\0\x0B\"'*.");
            // Take only the first line in case of multi-line
            $title = strtok($title, "\n");

            return ($title !== '' && $title !== false) ? mb_substr($title, 0, 80) : mb_substr($userMessage, 0, 60);
        } catch (\Exception $e) {
            Log::warning('Title generation failed', ['error' => $e->getMessage()]);

            return mb_substr($userMessage, 0, 60);
        }
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");

            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Strip <think>...</think> and <tool_call>...</tool_call> blocks from content.
     */
    public static function stripThinkingTags(string $content): string
    {
        $cleaned = preg_replace('/<think>[\s\S]*?<\/think>/u', '', $content);
        $cleaned = preg_replace('/<think>[\s\S]*/u', '', $cleaned);
        $cleaned = preg_replace('/<tool_call>[\s\S]*?<\/tool_call>/u', '', $cleaned);
        $cleaned = preg_replace('/<tool_call>[\s\S]*/u', '', $cleaned);

        return trim($cleaned);
    }

    /**
     * Extract thinking content from <think> tags.
     */
    public static function extractThinking(string $content): ?string
    {
        if (preg_match('/<think>([\s\S]*?)<\/think>/u', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
