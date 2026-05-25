@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-base-200 font-sans text-base-content antialiased">
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
    </body>
</html>
