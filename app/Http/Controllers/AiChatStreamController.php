<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Services\AiChat\ChatService;
use App\Services\AiChat\OllamaClient;
use App\Services\AiChat\ToolRegistry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatStreamController
{
    public function stream(Request $request, ChatService $chatService, OllamaClient $client, ToolRegistry $registry): StreamedResponse
    {
        $request->validate(['conversation_id' => 'required|integer']);

        $user = $request->user();
        $conversationId = (int) $request->input('conversation_id');

        $conversation = ChatConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->first();

        if (! $conversation) {
            return new StreamedResponse(function () {
                $this->sse('error', ['message' => 'Not found']);
                $this->sse('done', []);
            }, 200, $this->sseHeaders());
        }

        return new StreamedResponse(function () use ($conversation, $user, $chatService, $client, $registry) {
            // Ignore client disconnect — finish the AI response regardless
            ignore_user_abort(true);

            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            ob_implicit_flush(true);

            $tools = $registry->getOllamaToolSchemas($user);

            // Phase 1: Tool call loop (non-streaming)
            for ($round = 0; $round < 5; $round++) {
                $messages = $chatService->buildMessages($conversation, $user);
                $response = $client->chat($messages, $tools);
                $toolCalls = $response['tool_calls'] ?? [];

                if (empty($toolCalls)) {
                    break;
                }

                // Save assistant message with tool calls
                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => OllamaClient::stripThinkingTags($response['content'] ?? ''),
                    'tool_calls' => $toolCalls,
                ]);

                $allExecuted = true;
                foreach ($toolCalls as $index => $toolCall) {
                    $function = $toolCall['function'] ?? $toolCall;
                    $toolName = $function['name'] ?? 'unknown';
                    $arguments = $function['arguments'] ?? [];
                    $toolCallId = $toolCall['id'] ?? $function['id'] ?? "round_{$round}_call_{$index}";

                    $tool = $registry->findTool($toolName);
                    if (! $tool) {
                        $conversation->messages()->create([
                            'role' => 'tool', 'content' => json_encode(['error' => "Unknown tool: {$toolName}"]),
                            'tool_call_id' => $toolCallId, 'tool_name' => $toolName,
                        ]);

                        continue;
                    }

                    $perm = $tool->requiredPermission();
                    if ($perm !== null && ! $user->can($perm)) {
                        $conversation->messages()->create([
                            'role' => 'tool', 'content' => json_encode(['error' => 'Keine Berechtigung.']),
                            'tool_call_id' => $toolCallId, 'tool_name' => $toolName,
                        ]);

                        continue;
                    }

                    $effectivelyReadOnly = $tool->isReadOnly() || ($arguments['action'] ?? null) === 'list';
                    if ($effectivelyReadOnly) {
                        // Read-only tools execute immediately
                        try {
                            $result = $tool->execute($arguments, $user);
                        } catch (\Exception $e) {
                            $result = ['error' => $e->getMessage()];
                        }
                        $conversation->messages()->create([
                            'role' => 'tool', 'content' => json_encode($result),
                            'tool_call_id' => $toolCallId, 'tool_name' => $toolName,
                        ]);
                        $this->sse('tool', ['name' => $toolName, 'status' => 'executed']);
                    } elseif ($user->can('auto_approve_ai_actions')) {
                        // Auto-approve permission skips confirmation
                        try {
                            $result = $tool->execute($arguments, $user);
                        } catch (\Exception $e) {
                            $result = ['error' => $e->getMessage()];
                        }
                        $conversation->messages()->create([
                            'role' => 'tool', 'content' => json_encode($result),
                            'tool_call_id' => $toolCallId, 'tool_name' => $toolName,
                            'action_status' => 'auto_approved',
                        ]);
                        $this->sse('tool', ['name' => $toolName, 'status' => 'executed']);
                    } else {
                        // Mutating tool — requires user approval
                        $conversation->messages()->create([
                            'role' => 'tool', 'content' => null,
                            'tool_call_id' => $toolCallId, 'tool_name' => $toolName,
                            'pending_action' => [
                                'tool_name' => $toolName,
                                'arguments' => $arguments,
                                'description' => $this->describeAction($toolName, $arguments),
                            ],
                            'action_status' => 'pending',
                        ]);
                        $allExecuted = false;
                        $this->sse('pending_action', ['description' => $this->describeAction($toolName, $arguments)]);
                    }
                }

                if (! $allExecuted) {
                    $this->sse('done', []);

                    return;
                }
            }

            // Stream the final response (works for both post-tool and direct)
            $messages = $chatService->buildMessages($conversation, $user);
            $this->streamFromOllama($messages, $conversation);

            // Generate title
            if ($conversation->title === null) {
                $userMsg = $conversation->messages()->where('role', 'user')->orderBy('created_at')->first();
                if ($userMsg) {
                    $conversation->update(['title' => $client->generateTitle($userMsg->content)]);
                }
            }

            $this->sse('done', []);
        }, 200, $this->sseHeaders());
    }

    private function streamFromOllama(array $messages, ChatConversation $conversation): void
    {
        $ollamaUrl = rtrim(config('ai-chat.ollama_base_url'), '/').'/api/chat';
        $payload = json_encode([
            'model' => config('ai-chat.model'),
            'messages' => $messages,
            'stream' => true,
            'keep_alive' => -1,
            'options' => [
                'temperature' => config('ai-chat.temperature', 0.2),
                'num_predict' => config('ai-chat.max_tokens', 2048),
            ],
        ]);

        $rawFull = '';
        $insideThink = false;
        $ctrl = $this;

        $ch = curl_init($ollamaUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$rawFull, &$insideThink, $ctrl) {
                foreach (explode("\n", $data) as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    $json = json_decode($line, true);
                    if (! $json) {
                        continue;
                    }

                    $chunk = $json['message']['content'] ?? '';
                    if ($chunk === '') {
                        continue;
                    }

                    $rawFull .= $chunk;

                    // Filter out <think>...</think> blocks during streaming
                    if (str_contains($chunk, '<think>')) {
                        $insideThink = true;
                    }
                    if ($insideThink) {
                        if (str_contains($chunk, '</think>')) {
                            $insideThink = false;
                            // Send anything after </think>, stripping tool_call tags
                            $after = substr($chunk, strpos($chunk, '</think>') + 8);
                            $after = preg_replace('/<tool_call>[\s\S]*?<\/tool_call>/u', '', $after);
                            if (trim($after) !== '') {
                                $ctrl->sse('content', ['text' => $after]);
                            }
                        }
                        // Skip think content — don't send to client
                    } else {
                        // Strip any <tool_call> tags before sending
                        $cleaned = preg_replace('/<tool_call>[\s\S]*?<\/tool_call>/u', '', $chunk);
                        if (trim($cleaned) !== '') {
                            $ctrl->sse('content', ['text' => $cleaned]);
                        }
                    }
                }

                return strlen($data);
            },
        ]);

        $success = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (! $success || $curlError) {
            $ctrl->sse('error', ['message' => 'Verbindung zur KI fehlgeschlagen.']);

            return;
        }

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $rawFull,
        ]);
    }

    public function sse(string $event, array $data): void
    {
        echo "event: {$event}\ndata: ".json_encode($data)."\n\n";
        flush();
    }

    private function describeAction(string $toolName, array $arguments): string
    {
        $registry = app(ToolRegistry::class);
        $displayName = $registry->getDisplayName($toolName);
        $action = $arguments['action'] ?? null;

        return match (true) {
            $toolName === 'manage_lessons' && $action === 'create' => "{$displayName}: ".($arguments['name'] ?? 'Unbenannt').' erstellen',
            $toolName === 'manage_lessons' && $action === 'update' => "{$displayName} #".($arguments['lesson_id'] ?? '?').' bearbeiten',
            $toolName === 'manage_lessons' && $action === 'delete' => "{$displayName} #".($arguments['lesson_id'] ?? '?').' löschen',
            $toolName === 'manage_absences' && $action === 'create' => "{$displayName}: ".($arguments['start'] ?? '?').' bis '.($arguments['end'] ?? '?'),
            $toolName === 'manage_absences' && $action === 'delete' => "{$displayName} #".($arguments['absence_id'] ?? '?').' löschen',
            $action !== null => "{$displayName}: {$action}",
            default => $displayName,
        };
    }

    private function sseHeaders(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ];
    }
}
