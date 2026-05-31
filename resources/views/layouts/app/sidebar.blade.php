@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-base-200 font-sans text-base-content antialiased">
        {{-- wire:navigate strips data-theme off <html> (the server markup has none); re-apply it
             synchronously before the swapped-in body paints, or the chrome flashes the OS theme. --}}
        <script>window.applyTheme?.()</script>

        <x-nav sticky full-width>
            <x-slot:brand>
                <label for="main-drawer" class="btn btn-square btn-ghost me-1 lg:hidden">
                    <x-icon name="o-bars-3" class="h-5 w-5" />
                </label>
                <a href="{{ route('dashboard') }}" wire:navigate>
                    <x-app-logo />
                </a>
            </x-slot:brand>

            <x-slot:actions>
                <x-desktop-user-menu />
            </x-slot:actions>
        </x-nav>

        <x-main with-nav full-width>
            <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 border-e border-base-300">
                <x-menu activate-by-route>
                    <x-menu-title title="{{ __('Platform') }}" class="hidden-when-collapsed" />
                    <x-menu-item title="{{ __('Dashboard') }}" icon="o-home" route="dashboard" />
                    <x-menu-item title="{{ __('Clients') }}" icon="o-building-office-2" route="clients.index" />
                    <x-menu-item title="{{ __('Projects') }}" icon="o-rectangle-stack" route="projects.index" />
                    <x-menu-item title="{{ __('Tickets') }}" icon="o-view-columns" route="tickets.board" />
                    <x-menu-item title="{{ __('Time') }}" icon="o-clock" route="time.index" />
                    <x-menu-item title="{{ __('Expenses') }}" icon="o-banknotes" route="expenses.index" />
                    <x-menu-item title="{{ __('Invoices') }}" icon="o-document-text" route="invoices.index" />
                    <x-menu-item title="{{ __('Roadmaps') }}" icon="o-map" route="roadmaps.index" />
                </x-menu>

                <x-menu class="mt-auto">
                    <x-menu-item
                        title="{{ __('Repository') }}"
                        icon="o-code-bracket"
                        link="https://github.com/laravel/livewire-starter-kit"
                        external
                    />
                    <x-menu-item
                        title="{{ __('Documentation') }}"
                        icon="o-book-open"
                        link="https://laravel.com/docs/starter-kits#livewire"
                        external
                    />
                </x-menu>
            </x-slot:sidebar>

            <x-slot:content>
                {{ $slot }}
            </x-slot:content>
        </x-main>

        <x-toast />

        @livewireScriptConfig
    </body>
</html>
