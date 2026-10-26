<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{-- Social login buttons (filament-socialite renders them into this hook). --}}
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    {{-- Stays open once used, so validation errors and the throttle message remain visible. --}}
    <div x-data="{ open: @js($errors->isNotEmpty()) }">
        <button
            type="button"
            x-on:click="open = ! open"
            class="flex w-full items-center justify-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
            Mit E-Mail und Passwort anmelden
            <x-filament::icon
                icon="heroicon-m-chevron-down"
                class="h-4 w-4 transition"
                x-bind:class="{ 'rotate-180': open }"
            />
        </button>

        <div x-show="open" x-collapse x-cloak class="pt-6">
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

            <x-filament-panels::form id="form" wire:submit="authenticate">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>
    </div>
</x-filament-panels::page.simple>
