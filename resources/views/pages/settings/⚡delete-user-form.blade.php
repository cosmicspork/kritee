<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h3 class="text-lg font-medium">{{ __('Delete account') }}</h3>
        <p class="text-base-content/60">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-button
        label="{{ __('Delete account') }}"
        class="btn-error"
        wire:click="$dispatch('open-delete-user-modal')"
        data-test="delete-user-button"
    />

    <livewire:pages::settings.delete-user-modal />
</section>
