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
                <div id="chat-scroll" class="space-y-4 overflow-y-auto pr-3" style="max-height: calc(100vh - 26rem);">
                    @php
                        $toolGroups = [];
                        $currentTools = [];
                        foreach ($messages as $i => $msg) {
                            if ($msg['role'] === 'tool' && $msg['action_status'] !== 'pending') {
                                $currentTools[] = $msg;
                            } else {
                                if (!empty($currentTools)) {
                                    $toolGroups[$i] = $currentTools;
                                    $currentTools = [];
                                }
                            }
                        }
                    @endphp

                    @forelse($messages as $index => $msg)
                        @if($msg['role'] === 'user')
                            <div class="flex justify-end">
                                <div class="max-w-[75%] rounded-xl px-4 py-2 bg-primary-500 text-white">
                                    <p class="text-sm whitespace-pre-wrap">{{ $msg['content'] }}</p>
                                    <p class="text-[10px] text-primary-200 mt-0.5">{{ $msg['created_at'] }}</p>
                                </div>
                            </div>
                        @elseif($msg['role'] === 'assistant' && $msg['content'])
                            <div class="flex justify-start">
                                <div class="max-w-[75%] rounded-xl px-4 py-2 bg-gray-100 dark:bg-gray-800 flex flex-col gap-0.5">
                                    @if(isset($toolGroups[$index]) && count($toolGroups[$index]) > 0)
                                        <details>
                                            <summary class="text-[11px] text-gray-400 dark:text-gray-500 cursor-pointer select-none inline-flex items-center gap-1 leading-none hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                                <x-filament::icon icon="tabler-tool" class="w-3 h-3" />
                                                {{ count($toolGroups[$index]) }} Tool-Aufrufe
                                            </summary>
                                            <div class="mt-1.5 space-y-1">
                                                @foreach($toolGroups[$index] as $tr)
                                                    <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                                        <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $tr['tool_display_name'] ?? $tr['tool_name'] ?? 'Tool' }}</p>
                                                        <pre class="text-[10px] font-mono text-gray-400 whitespace-pre-wrap max-h-20 overflow-auto">{{ json_encode(json_decode($tr['content'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                    <div class="prose dark:prose-invert prose-sm max-w-none text-sm [&>*]:my-0 [&>*+*]:mt-1.5">
                                        {!! \Illuminate\Support\Str::markdown($msg['content'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                    </div>
                                    @if(isset($toolGroups[$index]))
                                        @foreach($toolGroups[$index] as $tr)
                                            @php $trData = json_decode($tr['content'], true); @endphp
                                            @if(isset($trData['download_url']))
                                                <a href="{{ $trData['download_url'] }}" target="_blank" class="inline-flex items-center gap-1.5 mt-2 px-3 py-1.5 rounded-lg bg-primary-500 text-white text-xs hover:bg-primary-600 transition-colors no-underline">
                                                    <x-filament::icon icon="tabler-download" class="w-3.5 h-3.5" />
                                                    PDF herunterladen
                                                </a>
                                            @endif
                                        @endforeach
                                    @endif
                                    <p class="text-[10px] text-gray-400 mt-1">{{ $msg['created_at'] }}</p>
                                </div>
                            </div>
                        @elseif($msg['role'] === 'tool' && $msg['action_status'] === 'pending')
                            {{-- Pending approval card --}}
                            <div class="flex justify-start">
                                <div class="max-w-[75%] rounded-xl px-4 py-3 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <x-filament::icon icon="tabler-alert-triangle" class="w-4 h-4 text-warning-500" />
                                        <p class="text-sm font-medium text-warning-700 dark:text-warning-300">Bestätigung erforderlich</p>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">{{ $msg['pending_action']['description'] ?? '' }}</p>
                                    {{-- Preview of what will change --}}
                                    @if(!empty($msg['pending_action']['arguments']))
                                        <details class="mb-2">
                                            <summary class="text-[11px] text-gray-400 cursor-pointer select-none">Vorschau der Änderungen</summary>
                                            <div class="mt-1 p-2 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs space-y-0.5">
                                                @foreach($msg['pending_action']['arguments'] as $key => $val)
                                                    <div class="flex gap-2">
                                                        <span class="text-gray-400 font-medium">{{ $key }}:</span>
                                                        <span class="text-gray-600 dark:text-gray-300">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                    <div class="flex gap-2">
                                        <x-filament::button wire:click="approveAction({{ $msg['id'] }})" size="xs" color="success" icon="tabler-check">Genehmigen</x-filament::button>
                                        <x-filament::button wire:click="rejectAction({{ $msg['id'] }})" size="xs" color="danger" icon="tabler-x">Ablehnen</x-filament::button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center text-sm text-gray-400 py-8">
                            Noch keine Nachrichten.
                        </div>
                    @endforelse

                    {{-- Streaming bubble: stays visible until Livewire replaces it --}}
                    <div class="flex justify-start" x-show="streaming || streamedContent" x-cloak>
                        <div class="max-w-[75%] rounded-xl px-4 py-2 bg-gray-100 dark:bg-gray-800 flex flex-col gap-0.5">
                            {{-- Tool call progress (before content arrives) --}}
                            <div x-show="toolCallCount > 0 && !streamedContent" class="flex items-center gap-2">
                                <x-filament::loading-indicator class="w-4 h-4" />
                                <span class="text-xs text-gray-400" x-text="toolCallCount + ' Tool-Aufruf' + (toolCallCount > 1 ? 'e' : '') + '...'"></span>
                            </div>
                            {{-- Tool calls label above content --}}
                            <span x-show="toolCallCount > 0 && streamedContent" class="text-[11px] text-gray-400 inline-flex items-center gap-1 leading-none">
                                <x-filament::icon icon="tabler-tool" class="w-3 h-3" />
                                <span x-text="toolCallCount + ' Tool-Aufruf' + (toolCallCount > 1 ? 'e' : '')"></span>
                            </span>
                            {{-- Streamed content --}}
                            <div x-show="streamedContent" class="prose dark:prose-invert prose-sm max-w-none text-sm [&>*]:my-0 [&>*+*]:mt-1.5" x-html="renderMarkdown(streamedContent)"></div>
                            {{-- Waiting indicator (only when nothing is happening yet) --}}
                            <div x-show="!streamedContent && toolCallCount === 0 && streaming" class="flex items-center gap-2">
                                <x-filament::loading-indicator class="w-5 h-5" />
                                <span class="text-sm text-gray-500">Denke nach...</span>
                            </div>
                        </div>
                    </div>
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
                toolCallCount: 0,
                inputMessage: '',

                scrollToBottom() {
                    setTimeout(() => {
                        const el = document.getElementById('chat-scroll');
                        if (el) el.scrollTop = el.scrollHeight;
                    }, 30);
                },

                onGlobalKey(e) {
                    const tag = e.target.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
                    if (e.ctrlKey || e.altKey || e.metaKey) return;
                    if (e.key.length === 1) this.$refs.chatInput?.focus();
                },

                renderMarkdown(t) {
                    if (!t) return '';
                    // Escape HTML
                    let h = t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    // Code blocks
                    h = h.replace(/```([\s\S]*?)```/g, '<pre class="bg-gray-100 dark:bg-gray-800 rounded p-2 my-1 text-xs overflow-x-auto"><code>$1</code></pre>');
                    h = h.replace(/`(.+?)`/g, '<code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">$1</code>');
                    // Bold/italic
                    h = h.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
                    h = h.replace(/\*(.+?)\*/g, '<em>$1</em>');
                    // Process line by line for lists
                    const lines = h.split('\n');
                    let out = '', inOl = false, inUl = false;
                    for (const line of lines) {
                        const olMatch = line.match(/^\s*(\d+)\.\s+(.*)/);
                        const ulMatch = line.match(/^\s*[-*]\s+(.*)/);
                        if (olMatch) {
                            if (!inOl) { out += '<ol class="list-decimal pl-5 my-1">'; inOl = true; }
                            if (inUl) { out += '</ul>'; inUl = false; }
                            out += '<li>' + olMatch[2] + '</li>';
                        } else if (ulMatch) {
                            if (!inUl) { out += '<ul class="list-disc pl-5 my-1">'; inUl = true; }
                            if (inOl) { out += '</ol>'; inOl = false; }
                            out += '<li>' + ulMatch[1] + '</li>';
                        } else {
                            if (inOl) { out += '</ol>'; inOl = false; }
                            if (inUl) { out += '</ul>'; inUl = false; }
                            out += (line === '' ? '<br>' : line + '<br>');
                        }
                    }
                    if (inOl) out += '</ol>';
                    if (inUl) out += '</ul>';
                    // Remove leading/trailing <br>
                    out = out.replace(/^(<br>)+/, '').replace(/(<br>)+$/, '');
                    return out;
                },

                async startStream(convId) {
                    this.streaming = true;
                    this.streamedContent = '';
                    this.toolCallCount = 0;
                    this.scrollToBottom();

                    try {
                        const resp = await fetch('/assistant/stream', {
                            method: 'POST',
                            headers: {
                                'Accept': 'text/event-stream',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            },
                            body: JSON.stringify({ conversation_id: convId }),
                        });

                        if (!resp.ok || !resp.body) {
                            this.streaming = false;
                            $wire.call('refreshMessages');
                            return;
                        }

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
                                            const txt = data.text || '';
                                            // Trim leading newlines on first chunk
                                            this.streamedContent += this.streamedContent === '' ? txt.replace(/^\n+/, '') : txt;
                                            this.scrollToBottom();
                                        } else if (evt === 'tool') {
                                            this.toolCallCount++;
                                            this.scrollToBottom();
                                        } else if (evt === 'pending_action') {
                                            this.streaming = false;
                                            await $wire.refreshMessages();
                                            this.streamedContent = '';
                                            this.toolCallCount = 0;
                                            this.scrollToBottom();
                                            return;
                                        }
                                    } catch {}
                                }
                            }
                        }
                    } catch (e) { console.error('Stream error:', e); }

                    // Keep streamed content visible while Livewire refreshes
                    this.streaming = false;
                    await $wire.refreshMessages();
                    // Now clear — Livewire has replaced the DOM with the final message
                    this.streamedContent = '';
                    this.toolCallCount = 0;
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
                    // Scroll on any Livewire update
                    Livewire.hook('morph.updated', () => this.scrollToBottom());
                    this.$nextTick(() => {
                        this.$refs.chatInput?.focus();
                        this.scrollToBottom();
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
