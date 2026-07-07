<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Appearance settings') }}</h2>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div
            x-data="{
                mode: (localStorage.getItem('theme-mode') || 'system').replaceAll('&quot;', ''),
                apply() {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const dark = this.mode === 'dark' || (this.mode === 'system' && prefersDark);
                    document.documentElement.setAttribute('data-theme', dark ? 'irongall' : 'khata');
                    document.documentElement.classList.toggle('dark', dark);
                    document.documentElement.classList.toggle('light', !dark);
                },
                set(mode) {
                    this.mode = mode;
                    localStorage.setItem('theme-mode', mode);
                    this.apply();
                },
                init() {
                    window.matchMedia('(prefers-color-scheme: dark)')
                        .addEventListener('change', () => { if (this.mode === 'system') this.apply(); });
                },
            }"
            class="join"
        >
            <button type="button" class="btn join-item gap-2" :class="mode === 'light' && 'btn-active btn-primary'" @click="set('light')">
                <x-icon name="o-sun" class="h-4 w-4" /> {{ __('Light') }}
            </button>
            <button type="button" class="btn join-item gap-2" :class="mode === 'dark' && 'btn-active btn-primary'" @click="set('dark')">
                <x-icon name="o-moon" class="h-4 w-4" /> {{ __('Dark') }}
            </button>
            <button type="button" class="btn join-item gap-2" :class="mode === 'system' && 'btn-active btn-primary'" @click="set('system')">
                <x-icon name="o-computer-desktop" class="h-4 w-4" /> {{ __('System') }}
            </button>
        </div>
    </x-pages::settings.layout>
</section>
