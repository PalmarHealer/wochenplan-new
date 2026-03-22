<x-filament-panels::page>
    @if(!$conversationId)
        {{-- List View --}}
        <x-filament::section>
            <form wire:submit="startChat" class="flex gap-2">
                <x-filament::input.wrapper class="flex-1">
                    <x-filament::input
                        type="text"
                        wire:model="newMessage"
                        placeholder="Wie kann ich dir helfen?"
                        autofocus
                    />
                </x-filament::input.wrapper>
                <x-filament::button type="submit" icon="tabler-send">
                    Senden
                </x-filament::button>
            </form>
        </x-filament::section>

        {{ $this->table }}
    @else
        {{-- Chat View --}}
        <div x-data="aiChat()" x-on:keydown.window="onGlobalKey($event)">
            <div class="mb-4">
                <x-filament::button wire:click="backToList" icon="tabler-arrow-left" color="gray" size="sm">
                    Zurück
                </x-filament::button>
            </div>

            <x-filament::section>
                <div class="space-y-4 overflow-y-auto" style="max-height: calc(100vh - 22rem);" x-ref="chatMessages">
                    @forelse($messages as $index => $msg)
                        @if($msg['role'] === 'user')
                            <div class="flex justify-end">
                                <div class="max-w-[75%] rounded-xl px-4 py-2 bg-primary-500 text-white">
                                    <p class="text-sm whitespace-pre-wrap">{{ $msg['content'] }}</p>
                                    <p class="text-[10px] text-primary-200 mt-0.5">{{ $msg['created_at'] }}</p>
                                </div>
                            </div>
                        @elseif($msg['role'] === 'assistant' && ($msg['content'] || $msg['thinking']))
                            <div class="flex justify-start">
                                <div class="max-w-[75%] rounded-xl px-4 py-2 bg-gray-100 dark:bg-gray-800">
                                    {{-- Show tool results that belong to this assistant message (appear right before it) --}}
                                    @php
                                        $toolResults = [];
                                        for ($i = $index - 1; $i >= 0; $i--) {
                                            if ($messages[$i]['role'] === 'tool' && $messages[$i]['content']) {
                                                $toolResults[] = $messages[$i];
                                            } else {
                                                break;
                                            }
                                        }
                                        $toolResults = array_reverse($toolResults);
                                    @endphp
                                    @foreach($toolResults as $tr)
                                        <details class="mb-2">
                                            <summary class="text-[11px] text-gray-400 dark:text-gray-500 cursor-pointer select-none inline-flex items-center gap-1 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                                <x-filament::icon icon="tabler-tool" class="w-3 h-3" />
                                                {{ $tr['tool_display_name'] ?? $tr['tool_name'] ?? 'Tool' }}
                                                @if($tr['action_status'] === 'approved') <x-filament::badge color="success" size="xs">Genehmigt</x-filament::badge>
                                                @elseif($tr['action_status'] === 'auto_approved') <x-filament::badge color="success" size="xs">Auto</x-filament::badge>
                                                @endif
                                            </summary>
                                            <pre class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-[11px] font-mono text-gray-500 dark:text-gray-400 whitespace-pre-wrap max-h-32 overflow-auto">{{ json_encode(json_decode($tr['content'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @endforeach
                                    @if($msg['content'])
                                        <div class="prose dark:prose-invert prose-sm max-w-none text-sm">
                                            {!! \Illuminate\Support\Str::markdown($msg['content']) !!}
                                        </div>
                                    @endif
                                    {{-- PDF download buttons from tool results --}}
                                    @foreach($toolResults as $tr)
                                        @php $trData = json_decode($tr['content'], true); @endphp
                                        @if(isset($trData['download_url']))
                                            <a href="{{ $trData['download_url'] }}" target="_blank" class="inline-flex items-center gap-1.5 mt-2 px-3 py-1.5 rounded-lg bg-primary-500 text-white text-xs hover:bg-primary-600 transition-colors no-underline">
                                                <x-filament::icon icon="tabler-download" class="w-3.5 h-3.5" />
                                                PDF herunterladen
                                            </a>
                                        @endif
                                    @endforeach
                                    <p class="text-[10px] text-gray-400 mt-1">{{ $msg['created_at'] }}</p>
                                </div>
                            </div>
                        @elseif($msg['role'] === 'tool' && $msg['action_status'] === 'pending')
                            <div class="flex justify-start">
                                <div class="max-w-[75%] rounded-xl px-4 py-3 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800">
                                    <p class="text-sm font-medium text-warning-700 dark:text-warning-300 mb-2">{{ $msg['pending_action']['description'] ?? 'Bestätigung erforderlich' }}</p>
                                    <div class="flex gap-2">
                                        <x-filament::button wire:click="approveAction({{ $msg['id'] }})" size="xs" color="success" icon="tabler-check">Genehmigen</x-filament::button>
                                        <x-filament::button wire:click="rejectAction({{ $msg['id'] }})" size="xs" color="danger" icon="tabler-x">Ablehnen</x-filament::button>
                                    </div>
                                </div>
                            </div>
                        @elseif($msg['role'] === 'tool')
                            {{-- Tool results without a following assistant message are shown standalone --}}
                            @php
                                $nextMsg = $messages[$index + 1] ?? null;
                                $isOrphan = !$nextMsg || $nextMsg['role'] !== 'assistant';
                            @endphp
                            @if($isOrphan && $msg['content'])
                                <div class="flex justify-start">
                                    <div class="max-w-[75%] rounded-xl px-4 py-2 bg-gray-100 dark:bg-gray-800">
                                        <details>
                                            <summary class="text-[11px] text-gray-400 cursor-pointer select-none inline-flex items-center gap-1">
                                                <x-filament::icon icon="tabler-tool" class="w-3 h-3" />
                                                {{ $msg['tool_display_name'] ?? $msg['tool_name'] ?? 'Tool' }}
                                            </summary>
                                            <pre class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-[11px] font-mono text-gray-500 dark:text-gray-400 whitespace-pre-wrap max-h-32 overflow-auto">{{ json_encode(json_decode($msg['content'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @empty
                        <div class="text-center text-sm text-gray-400 py-8">
                            Noch keine Nachrichten.
                        </div>
                    @endforelse

                    {{-- Streaming --}}
                    <template x-if="streaming">
                        <div class="flex justify-start">
                            <div class="max-w-[75%] rounded-xl px-4 py-2 bg-gray-100 dark:bg-gray-800">
                                <template x-if="streamedContent">
                                    <div class="prose dark:prose-invert prose-sm max-w-none text-sm" x-html="renderMarkdown(streamedContent)"></div>
                                </template>
                                <template x-if="!streamedContent">
                                    <div class="flex items-center gap-2">
                                        <x-filament::loading-indicator class="w-5 h-5" />
                                        <span class="text-sm text-gray-500">Denke nach...</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </x-filament::section>

            <div class="mt-6">
                <form x-on:submit.prevent="send()" class="flex gap-2 items-center">
                    <x-filament::input.wrapper class="flex-1">
                        <x-filament::input
                            type="text"
                            x-ref="chatInput"
                            x-model="inputMessage"
                            placeholder="Nachricht eingeben..."
                            autocomplete="off"
                        />
                    </x-filament::input.wrapper>
                    <x-filament::button type="submit" icon="tabler-send" x-bind:disabled="streaming || !inputMessage.trim()">
                        <span x-show="!streaming">Senden</span>
                        <span x-show="streaming" x-cloak>
                            <x-filament::loading-indicator class="w-4 h-4" />
                        </span>
                    </x-filament::button>
                </form>
            </div>
        </div>

        @script
        <script>
            Alpine.data('aiChat', () => ({
                streaming: false,
                streamedContent: '',
                inputMessage: '',

                scrollToBottom() {
                    this.$nextTick(() => {
                        setTimeout(() => {
                            const el = this.$refs.chatMessages;
                            if (el) el.scrollTop = el.scrollHeight;
                        }, 50);
                    });
                },

                onGlobalKey(e) {
                    const tag = e.target.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
                    if (e.ctrlKey || e.altKey || e.metaKey) return;
                    if (e.key.length === 1) this.$refs.chatInput?.focus();
                },

                renderMarkdown(t) {
                    if (!t) return '';
                    return t
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                        .replace(/```([\s\S]*?)```/g, '<pre class="bg-gray-100 dark:bg-gray-800 rounded p-2 my-1 text-xs overflow-x-auto"><code>$1</code></pre>')
                        .replace(/`(.+?)`/g, '<code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">$1</code>')
                        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.+?)\*/g, '<em>$1</em>')
                        .replace(/\n/g, '<br>');
                },

                async startStream(convId) {
                    this.streaming = true;
                    this.streamedContent = '';
                    this.scrollToBottom();

                    try {
                        const resp = await fetch(`/assistant/stream?conversation_id=${convId}`, {
                            headers: { 'Accept': 'text/event-stream' },
                        });
                        const reader = resp.body.getReader();
                        const dec = new TextDecoder();
                        let buf = '', evt = 'content';

                        while (true) {
                            const { done, value } = await reader.read();
                            if (done) break;
                            buf += dec.decode(value, { stream: true });
                            const lines = buf.split('\n');
                            buf = lines.pop();
                            for (const line of lines) {
                                if (line.startsWith('event: ')) evt = line.slice(7).trim();
                                else if (line.startsWith('data: ')) {
                                    try {
                                        const data = JSON.parse(line.slice(6));
                                        if (evt === 'content') {
                                            this.streamedContent += data.text || '';
                                            this.scrollToBottom();
                                        }
                                    } catch {}
                                }
                            }
                        }
                    } catch (e) { console.error('Stream error:', e); }

                    this.streaming = false;
                    this.streamedContent = '';
                    await $wire.refreshMessages();
                    this.scrollToBottom();
                },

                async send() {
                    const text = this.inputMessage.trim();
                    if (!text || this.streaming) return;
                    this.inputMessage = '';
                    const convId = await $wire.saveUserMessage(text);
                    this.scrollToBottom();
                    if (!convId) return;
                    await this.startStream(convId);
                },

                init() {
                    this.$watch('$wire.messages', () => this.scrollToBottom());
                    this.$nextTick(() => {
                        this.$refs.chatInput?.focus();
                        this.scrollToBottom();

                        // Auto-scroll on any DOM changes in the messages container
                        const el = this.$refs.chatMessages;
                        if (el) {
                            new MutationObserver(() => this.scrollToBottom()).observe(el, { childList: true, subtree: true });
                        }

                        if ($wire.pendingStream && $wire.conversationId) {
                            $wire.pendingStream = false;
                            this.startStream($wire.conversationId);
                        }
                    });
                }
            }));
        </script>
        @endscript
    @endif
</x-filament-panels::page>
