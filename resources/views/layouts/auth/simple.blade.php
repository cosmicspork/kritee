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

        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                <x-app-logo-icon class="size-10" />
                <span class="sr-only">{{ config('app.name', 'Kritee') }}</span>
            </a>

            <div class="card w-full max-w-sm border border-base-300 bg-base-100 shadow-xl">
                <div class="card-body gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        <x-toast />

        @livewireScriptConfig
    </body>
</html>
