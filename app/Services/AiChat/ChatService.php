<?php

namespace App\Services\AiChat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ChatService
{
    private OllamaClient $client;

    private ToolRegistry $registry;

    public function __construct(OllamaClient $client, ToolRegistry $registry)
    {
        $this->client = $client;
        $this->registry = $registry;
    }

    /**
     * Process a user message — handles tool calls synchronously.
     * For the final text response, returns the conversation so the caller can stream it.
     */
    public function processMessage(int $conversationId, string $userMessage, User $user): array
    {
        $conversation = ChatConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Save user message
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // Build messages for LLM
        $messages = $this->buildMessages($conversation, $user);
        $tools = $this->registry->getOllamaToolSchemas($user);

        // Call LLM (with tool-call loop, max 5 iterations)
        $maxIterations = 5;

        for ($i = 0; $i < $maxIterations; $i++) {
            $response = $this->client->chat($messages, $tools);

            $toolCalls = $response['tool_calls'] ?? [];
            $content = $response['content'] ?? null;

            // No tool calls — save assistant message and return
            if (empty($toolCalls)) {
                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $content,
                ]);

                // Generate AI title after first exchange
                $this->maybeGenerateTitle($conversation, $userMessage);

                return $this->getConversationMessages($conversation);
            }

            // Save assistant message with tool_calls
            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $content,
                'tool_calls' => $toolCalls,
            ]);

            // Process each tool call
            $allExecuted = true;
            foreach ($toolCalls as $index => $toolCall) {
                $function = $toolCall['function'] ?? $toolCall;
                $toolName = $function['name'] ?? 'unknown';
                $arguments = $function['arguments'] ?? [];
                $toolCallId = $toolCall['id'] ?? "call_{$index}";

                $tool = $this->registry->findTool($toolName);

                if (! $tool) {
                    $conversation->messages()->create([
                        'role' => 'tool',
                        'content' => json_encode(['error' => "Unknown tool: {$toolName}"]),
                        'tool_call_id' => $toolCallId,
                        'tool_name' => $toolName,
                    ]);
                    $messages = $this->buildMessages($conversation, $user);

                    continue;
                }

                // Check permission
                $perm = $tool->requiredPermission();
                if ($perm !== null && ! $user->can($perm)) {
                    $conversation->messages()->create([
                        'role' => 'tool',
                        'content' => json_encode(['error' => 'Keine Berechtigung für diese Aktion.']),
                        'tool_call_id' => $toolCallId,
                        'tool_name' => $toolName,
                    ]);
                    $messages = $this->buildMessages($conversation, $user);

                    continue;
                }

                // Read-only or auto-approve: execute immediately
                if ($tool->isReadOnly() || $user->can('auto_approve_ai_actions')) {
                    $result = $this->executeTool($tool, $arguments, $user);
                    $status = $tool->isReadOnly() ? null : 'auto_approved';

                    $conversation->messages()->create([
                        'role' => 'tool',
                        'content' => json_encode($result),
                        'tool_call_id' => $toolCallId,
                        'tool_name' => $toolName,
                        'action_status' => $status,
                    ]);

                    $messages = $this->buildMessages($conversation, $user);
                } else {
                    // Needs confirmation — store as pending and stop the loop
                    $conversation->messages()->create([
                        'role' => 'tool',
                        'content' => null,
                        'tool_call_id' => $toolCallId,
                        'tool_name' => $toolName,
                        'pending_action' => [
                            'tool_name' => $toolName,
                            'arguments' => $arguments,
                            'description' => $this->describeAction($toolName, $arguments),
                        ],
                        'action_status' => 'pending',
                    ]);
                    $allExecuted = false;
                }
            }

            // If there are pending actions, stop the loop and wait for user confirmation
            if (! $allExecuted) {
                return $this->getConversationMessages($conversation);
            }
        }

        // If we exhausted iterations, add a fallback message
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'Es tut mir leid, ich konnte die Anfrage nicht vollständig bearbeiten. Bitte versuche es erneut.',
        ]);

        return $this->getConversationMessages($conversation);
    }

    /**
     * Stream the LLM response for a conversation. Used by the SSE endpoint.
     * Returns a Generator that yields content chunks.
     */
    public function streamResponse(int $conversationId, User $user): \Generator|null
    {
        $conversation = ChatConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->first();

        if (! $conversation) {
            return null;
        }

        $messages = $this->buildMessages($conversation, $user);

        // No tools during streaming — tool calls are handled in processMessage first
        $result = $this->client->streamChat($messages);

        // If it's an array (fallback non-streaming), yield the full content
        if (is_array($result)) {
            if (! empty($result['content'])) {
                yield $result['content'];
            }

            return;
        }

        yield from $result;
    }

    public function approveAction(int $messageId, User $user): array
    {
        $message = ChatMessage::findOrFail($messageId);
        $conversation = $message->conversation;

        if ($conversation->user_id !== $user->id || $message->action_status !== 'pending') {
            return $this->getConversationMessages($conversation);
        }

        $action = $message->pending_action;
        $tool = $this->registry->findTool($action['tool_name']);

        if (! $tool) {
            $message->update([
                'content' => json_encode(['error' => 'Tool nicht mehr verfügbar.']),
                'action_status' => 'rejected',
            ]);

            return $this->getConversationMessages($conversation);
        }

        $result = $this->executeTool($tool, $action['arguments'], $user);

        $message->update([
            'content' => json_encode($result),
            'action_status' => 'approved',
        ]);

        // Continue the LLM conversation with the tool result
        $messages = $this->buildMessages($conversation, $user);
        $tools = $this->registry->getOllamaToolSchemas($user);
        $response = $this->client->chat($messages, $tools);

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $response['content'] ?? 'Aktion wurde ausgeführt.',
        ]);

        return $this->getConversationMessages($conversation);
    }

    public function rejectAction(int $messageId, User $user): array
    {
        $message = ChatMessage::findOrFail($messageId);
        $conversation = $message->conversation;

        if ($conversation->user_id !== $user->id || $message->action_status !== 'pending') {
            return $this->getConversationMessages($conversation);
        }

        $message->update([
            'content' => json_encode(['info' => 'Aktion wurde vom Benutzer abgelehnt.']),
            'action_status' => 'rejected',
        ]);

        // Tell the LLM the action was rejected
        $messages = $this->buildMessages($conversation, $user);
        $tools = $this->registry->getOllamaToolSchemas($user);
        $response = $this->client->chat($messages, $tools);

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $response['content'] ?? 'Verstanden, die Aktion wurde nicht ausgeführt.',
        ]);

        return $this->getConversationMessages($conversation);
    }

    private function executeTool(AiChatTool $tool, array $arguments, User $user): array
    {
        try {
            return $tool->execute($arguments, $user);
        } catch (\Exception $e) {
            Log::error('AI Chat tool execution error', [
                'tool' => $tool->name(),
                'error' => $e->getMessage(),
            ]);

            return ['error' => 'Fehler bei der Ausführung: '.$e->getMessage()];
        }
    }

    public function buildMessages(ChatConversation $conversation, User $user): array
    {
        $systemPrompt = $this->getSystemPrompt($user);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        $dbMessages = $conversation->messages()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($dbMessages as $msg) {
            if ($msg->role === 'tool') {
                // Skip pending actions without results
                if ($msg->action_status === 'pending' && $msg->content === null) {
                    continue;
                }
                $messages[] = [
                    'role' => 'tool',
                    'content' => $msg->content ?? '{}',
                ];
            } elseif ($msg->role === 'assistant' && ! empty($msg->tool_calls)) {
                // Ensure tool_calls arguments are objects, not arrays
                $toolCalls = array_map(function ($tc) {
                    if (isset($tc['function']['arguments']) && is_array($tc['function']['arguments']) && array_is_list($tc['function']['arguments'])) {
                        $tc['function']['arguments'] = new \stdClass;
                    }

                    return $tc;
                }, $msg->tool_calls);

                $messages[] = [
                    'role' => 'assistant',
                    'content' => $msg->content ?? '',
                    'tool_calls' => $toolCalls,
                ];
            } else {
                $messages[] = [
                    'role' => $msg->role,
                    'content' => $msg->content ?? '',
                ];
            }
        }

        return $messages;
    }

    private function getSystemPrompt(User $user): string
    {
        $now = Carbon::now();
        $today = $now->translatedFormat('l, d.m.Y');
        $time = $now->format('H:i');
        $userName = $user->display_name ?? $user->name;

        return <<<PROMPT
Du bist der Assistent für das Wochenplan-System, eine Schulplanungs-Software.

Aktuelle Informationen:
- Datum: {$today}
- Uhrzeit: {$time} Uhr
- Benutzer: "{$userName}" (ID: {$user->id})

Regeln:
- Antworte in der Sprache des Benutzers (Deutsch oder Englisch).
- Du hast VOLLEN Zugriff auf den bisherigen Chatverlauf.
- Antworte direkt und knapp.
- Wochentage: 1=Montag, 2=Dienstag, 3=Mittwoch, 4=Donnerstag, 5=Freitag.
- Datumsformat für Benutzer: dd.mm.YYYY. Intern für Tools: YYYY-MM-DD.
- Du kannst NUR die dir zur Verfügung gestellten Tools verwenden. Biete NIEMALS an, etwas zu tun, wofür du kein Tool hast.
- Schreibe NIEMALS <tool_call> Tags oder JSON in deine Antwort. Nutze ausschließlich die Tool-Schnittstelle.
- Du darfst mehrere Tools gleichzeitig aufrufen wenn nötig.
- Wenn ein Tool Daten zurückgibt, fasse sie benutzerfreundlich zusammen (keine IDs, nur Namen und relevante Infos).
- Wenn du ein PDF exportierst, schreibe KEINEN Download-Link in deine Antwort. Der Download-Button wird automatisch angezeigt. Sage nur dass das PDF erstellt wurde.

Wichtig - Sei proaktiv und handlungsorientiert:
- Stelle NICHT viele Rückfragen. Nutze die Tools um fehlende Infos selbst zu holen (z.B. Räume, Layouts, Zeiten auflisten).
- Wenn der Benutzer etwas erstellen will, schaue dir die verfügbaren Optionen selbst an, wähle sinnvolle Standardwerte und schlage sie vor: "Ich erstelle die Abweichung mit Layout X vom 1.4. bis 10.4. - ist das okay?"
- Maximal EINE Rückfrage, bevor du handelst.
- Verwende sinnvolle Standardwerte wenn Informationen fehlen.
- Wenn der Benutzer etwas ÄNDERN oder KORRIGIEREN will, nutze die UPDATE-Aktion (action: "update") mit der ID des bestehenden Eintrags. Erstelle KEINE Duplikate. Frage im Zweifelsfall welcher Eintrag gemeint ist.
- Wenn etwas schon existiert und der Benutzer eine Anpassung will, nutze IMMER update statt create.

/no_think
PROMPT;
    }

    private function maybeGenerateTitle(ChatConversation $conversation, string $userMessage): void
    {
        if ($conversation->title !== null) {
            return;
        }

        $messageCount = $conversation->messages()->count();
        if ($messageCount > 2) {
            return;
        }

        $title = $this->client->generateTitle($userMessage);
        $conversation->update(['title' => $title]);
    }

    private function describeAction(string $toolName, array $arguments): string
    {
        return match ($toolName) {
            'create_lesson' => 'Angebot erstellen: '.($arguments['name'] ?? 'Unbenannt'),
            'update_lesson' => 'Angebot #'.($arguments['lesson_id'] ?? '?').' bearbeiten',
            'delete_lesson' => 'Angebot #'.($arguments['lesson_id'] ?? '?').' löschen',
            'create_absence' => 'Krankmeldung erstellen: '.($arguments['start'] ?? '?').' bis '.($arguments['end'] ?? '?'),
            'delete_absence' => 'Krankmeldung #'.($arguments['absence_id'] ?? '?').' löschen',
            default => "Aktion: {$toolName}",
        };
    }

    public function getConversationMessages(ChatConversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(function (ChatMessage $msg) {
                $content = $msg->content;
                $thinking = null;

                if ($msg->role === 'assistant' && $content) {
                    $thinking = OllamaClient::extractThinking($content);
                    $content = OllamaClient::stripThinkingTags($content);
                }

                return [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => $content,
                    'thinking' => $thinking,
                    'tool_name' => $msg->tool_name,
                    'tool_display_name' => $msg->tool_name ? $this->registry->getDisplayName($msg->tool_name) : null,
                    'tool_calls' => $msg->tool_calls,
                    'pending_action' => $msg->pending_action,
                    'action_status' => $msg->action_status,
                    'created_at' => $msg->created_at->format('H:i'),
                ];
            })
            ->toArray();
    }
}
