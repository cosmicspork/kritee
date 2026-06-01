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

        <div class="mx-auto flex min-h-svh w-full max-w-4xl flex-col gap-8 p-6 md:p-10">
            <header class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-medium" wire:navigate>
                    <x-app-logo />
                </a>
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>

        @livewireScriptConfig
    </body>
</html>
