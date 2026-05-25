@props([
    'sidebar' => false,
])

<span {{ $attributes->class('flex items-center gap-2') }}>
    <span class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary text-primary-content">
        <x-app-logo-icon class="size-5 fill-current" />
    </span>
    <span class="text-base font-semibold leading-none">{{ config('app.name', 'Kriti') }}</span>
</span>
