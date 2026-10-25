<x-filament-panels::page>
    <style>
        /* Spacing via margins: x-show resets inline display, so flex gaps would get lost. */
        .help-block { margin-top: 2rem; }
        .help-group { font-size: .8rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; opacity: .6; margin: 0 0 .75rem .25rem; }
        .help-topic + .help-topic { margin-top: .75rem; }
        .help-body p { margin-bottom: .75rem; }
        .help-body p:last-child { margin-bottom: 0; }
        .help-body ul, .help-body ol { margin: 0 0 .75rem 1.25rem; }
        .help-body ul { list-style: disc; }
        .help-body ol { list-style: decimal; }
        .help-body li { margin-bottom: .25rem; }
        .help-body h4 { font-weight: 600; margin: 1rem 0 .375rem; }
        .help-body code { font-size: .85em; padding: .05rem .3rem; border-radius: .25rem; background: rgb(0 0 0 / .06); }
        .dark .help-body code { background: rgb(255 255 255 / .1); }
        .help-body .help-note { padding: .5rem .75rem; border-left: 3px solid rgb(245 158 11); background: rgb(245 158 11 / .08); border-radius: .25rem; margin-bottom: .75rem; }
        .help-empty { padding: 1.5rem; text-align: center; opacity: .7; }
    </style>

    <div
        x-data="{
            query: '',
            matches(text) {
                const words = this.query.toLowerCase().split(/\s+/).filter(Boolean);
                return words.every((word) => text.includes(word));
            },
            get hasResults() {
                return [...this.$root.querySelectorAll('[data-help-search]')]
                    .some((el) => this.matches(el.dataset.helpSearch));
            },
        }"
        x-init="
            const id = window.location.hash.slice(1);
            if (id) {
                $nextTick(() => {
                    $dispatch('open-section', { id });
                    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }
        "
    >
        <x-filament::input.wrapper prefix-icon="tabler-search">
            <x-filament::input type="search" x-model.debounce.150ms="query" placeholder="Hilfe durchsuchen, z. B. „Schichtplan“ oder „Ferien“" />
        </x-filament::input.wrapper>

        @forelse ($this->groups() as $group => $topics)
            <div class="help-block" x-show="[...$el.querySelectorAll('[data-help-search]')].some((topic) => matches(topic.dataset.helpSearch))">
                <div class="help-group">{{ $group }}</div>

                @foreach ($topics as $entry)
                    @php($topic = $entry['topic'])
                    <div class="help-topic" x-show="matches($el.dataset.helpSearch)" data-help-search="{{ $entry['search'] }}" wire:key="help-{{ $topic->id }}">
                        <x-filament::section
                            :icon="$topic->icon"
                            collapsible
                            collapsed
                            persist-collapsed
                            :id="$topic->id">
                            <x-slot name="heading">{{ $topic->heading }}</x-slot>
                            <x-slot name="description">{{ $topic->description }}</x-slot>

                            <div class="help-body">{!! $entry['body'] !!}</div>
                        </x-filament::section>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="help-empty">Für deine Rolle gibt es noch keine Hilfethemen.</div>
        @endforelse

        <div class="help-empty" x-show="query && ! hasResults" x-cloak>
            Nichts gefunden. Wenn dir etwas fehlt, schreib an
            <x-filament::link href="mailto:support@nauren.de">support@nauren.de</x-filament::link>.
        </div>
    </div>
</x-filament-panels::page>
